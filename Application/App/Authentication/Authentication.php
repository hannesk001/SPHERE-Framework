<?php

namespace SPHERE\Application\App\Authentication;

use Exception;
use SPHERE\Application\App\ApplicationInterface;
use SPHERE\Application\App\Authentication\Factor\AuthenticatorApp;
use SPHERE\Application\App\Authentication\Factor\Credentials;
use SPHERE\Application\App\Authentication\Factor\Token;
use SPHERE\Application\App\Authentication\Factor\TokenOrAuthenticatorApp;
use SPHERE\Application\App\Authentication\Process\Service;
use SPHERE\Application\App\Authentication\Process\Service\Entity\Internal\Jwt;
use SPHERE\Application\App\Authentication\Process\SignIn;
use SPHERE\Application\App\Authentication\Process\SignOut;
use SPHERE\Application\App\Response\Code\Response200;
use SPHERE\Application\App\Response\Code\Response201;
use SPHERE\Application\App\Response\Code\Response400;
use SPHERE\Application\App\Response\Code\Response401;
use SPHERE\Application\App\Response\Code\Response405;
use SPHERE\Application\App\Response\Code\Response415;
use SPHERE\Application\App\Response\ResponseInterface;
use SPHERE\Application\App\ServiceInterface;
use SPHERE\Application\Platform\Gatekeeper\Authorization\Account\Account;
use SPHERE\Common\Main;
use SPHERE\System\App\App;
use SPHERE\System\Database\Link\Identifier;

class Authentication implements ApplicationInterface, ServiceInterface
{
    /**
     * @return void
     */
    public static function registerApplication(): void
    {
        Main::getDispatcher()::registerRoute(Main::getDispatcher()::createRoute(
            __NAMESPACE__ . '/status', __CLASS__ . '::authenticationStatus'
        ));

        Main::getDispatcher()::registerRoute(Main::getDispatcher()::createRoute(
            __NAMESPACE__ . '/access-token', __CLASS__ . '::createAccessToken'
        ));

        SignIn::registerModule();
        SignOut::registerModule();

        Credentials::registerModule();
        Token::registerModule();
        AuthenticatorApp::registerModule();
        TokenOrAuthenticatorApp::registerModule();
    }

    /**
     * @return Service
     */
    public static function useService(): Service
    {
        return new Service(new Identifier('Platform', 'App', 'Authentication'),
            __DIR__ . '/Process/Service/Entity', __NAMESPACE__ . '\Process\Service\Entity'
        );
    }

    /** @noinspection PhpUnused */
    public static function authenticationStatus(
        ?string $deviceFactor = null,
//        ?string $credentialIdentifier = null
    ): ResponseInterface
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return new Response405('Allow: GET', [
                'method' => $_SERVER['REQUEST_METHOD'],
            ]);
        }

        if (empty($deviceFactor)) {
            return new Response400('Device Factor not provided', [
                'deviceFactor' => $deviceFactor,
            ]);
        }

        $tblAccount = null;
        // TODO: is credentialIdentifier necessary?, first login app only deviceFactor required and known
//        $tblAccount = !empty($credentialIdentifier) ? Account::useService()->getAccountByUsername($credentialIdentifier) : null;

        if (($tblProcessList = self::useService()->getAllProcessByDeviceFactor($deviceFactor, $tblAccount ?: null))) {
            foreach ($tblProcessList as $tblProcess) {
                if ($tblProcess->getIsSolved() !== true) {
                    if (($tblFactor = $tblProcess->getTblFactor())
                        && ($context = $tblFactor->getContext())
                    ) {
                        return new Response401('Not signed in', $context);
                    }
                }
            }
        }

        if (($tblToken = self::useService()->getTokenByDeviceFactor($deviceFactor))) {
            if (time() > $tblToken->getAuthenticationTimeout()) {
                return self::useService()->sendAuthenticationTokenExpired($deviceFactor, $tblToken->getServiceTblAccount());
            }
            if (time() > $tblToken->getAccessTimeout()) {
                return new Response401('Access Token expired',
                    self::useService()->getLink('/app/authentication/access-token', 'POST', ['deviceFactor'], [], ['authenticationToken']));
            }

            return new Response200('Signed in');
        }

        // $deviceFactor is unknown
        if (($context = self::useService()->createNewProcess($deviceFactor, null))) {
            return new Response401('Not signed in', $context);
        }

        return new Response401('No sign in available', []);
    }

    /** @noinspection PhpUnused */
    public static function createAccessToken(
        ?string $deviceFactor = null,
    ): ResponseInterface
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return new Response405('Allowed: POST', [
                'method' => $_SERVER['REQUEST_METHOD'],
            ]);
        }
        $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
        if ($contentType !== 'application/json') {
            return new Response415('"Only JSON content is supported"', [
                'contentType' => $contentType,
            ]);
        }

        if (empty($deviceFactor)) {
            return new Response400('Device Factor not provided', [
                'deviceFactor' => $deviceFactor,
            ]);
        }

        // JSON content laden
        $data = json_decode(file_get_contents('php://input'), true);
        $authenticationToken = $data['authenticationToken'] ?? null;

        if (empty($authenticationToken)) {

            return new Response400('Authentication Token not provided');
        }

        try {
            $data = (new Jwt((new App())->getSecretAuthentication()))->decode($authenticationToken);

            if ($data == null) {
                return new Response401('Invalid signature');
            }

            $accountId = $data['accountId'] ?? null;
            $deviceFactor = $data['deviceFactor'] ?? null;
            $timeout = $data['timeout'] ?? null;
            if (empty($accountId) || empty($deviceFactor) || empty($timeout)) {
                return new Response401('Invalid Bearer token');
            }

            // bearer token is expired
            if (time() > $timeout
                || !($tblToken = self::useService()->getTokenByAuthenticationToken($authenticationToken))
                || !($tblAccount = $tblToken->getServiceTblAccount())
            ) {
                $tblAccount = Account::useService()->getAccountById($accountId);

                return self::useService()->sendAuthenticationTokenExpired($deviceFactor, $tblAccount ?: null);
            }

            $accessToken = self::useService()->createAccessToken($tblAccount, $deviceFactor);

            self::useService()->updateToken($tblToken, $accessToken);

            return new Response201([
                'accessToken' => $accessToken->getToken(),
            ]);

        } catch (Exception $e) {

            return new Response400($e->getMessage());
        }
    }
}

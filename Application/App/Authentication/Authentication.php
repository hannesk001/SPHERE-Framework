<?php

namespace SPHERE\Application\App\Authentication;

use Exception;
use SPHERE\Application\App\Authentication\Factor\AuthenticatorApp;
use SPHERE\Application\App\Authentication\Factor\Credentials;
use SPHERE\Application\App\Authentication\Factor\Token;
use SPHERE\Application\App\Authentication\Factor\TokenOrAuthenticatorApp;
use SPHERE\Application\App\Authentication\Process\Service;
use SPHERE\Application\App\Authentication\Process\Service\Entity\Internal\Jwt;
use SPHERE\Application\App\Authentication\Process\SignIn;
use SPHERE\Application\App\Authentication\Process\SignOut;
use SPHERE\Application\App\Response\Code\Response201;
use SPHERE\Application\App\Response\Code\Response400;
use SPHERE\Application\App\Response\Code\Response401;
use SPHERE\Application\App\Response\Code\Response405;
use SPHERE\Application\App\Response\Code\Response501;
use SPHERE\Application\App\Response\ResponseInterface;
use SPHERE\Application\App\ServiceInterface;
use SPHERE\Common\Main;
use SPHERE\System\App\App;
use SPHERE\System\Database\Link\Identifier;

class Authentication implements ServiceInterface
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
            __NAMESPACE__ . '/access-token', __CLASS__ . '::getAccessToken'
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
                        return new Response401('not signed in', $context);
                    }
                }
            }
        } else {
            $tblIdentification = self::useService()->getIdentificationByAccount($tblAccount ?: null);
            if (($tblStepList = self::useService()->getAllStepByIdentification($tblIdentification ?: null))) {
                $tblStep = $tblStepList[0];
                if (($tblFactor = $tblStep->getTblFactor())
                    && ($context = $tblFactor->getContext())
                ) {
                    // save process
                    self::useService()->createProcess($tblFactor, $deviceFactor, $tblAccount ?: null);

                    return new Response401('not signed in', $context);
                }
            }

            return new Response401('no sign in available', []);
        }

        return new Response501(null);
    }

    /** @noinspection PhpUnused */
    public static function getAccessToken(): ResponseInterface
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return new Response405('Allow: GET', [
                'method' => $_SERVER['REQUEST_METHOD'],
            ]);
        }

        $headers = null;
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = ['Authorization' => $_SERVER['HTTP_AUTHORIZATION']];
        }
        if (!isset($headers['Authorization']) || !preg_match("/^Bearer\s+(.*)$/", $headers['Authorization'], $matches)) {
            return new Response400('Incomplete authorization header', ['Authorization' => $headers['Authorization'] ?? null]);
        }
        $authenticationToken = $matches[1];

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
                // TODO: add link for new login
                return new Response401('Bearer token is expired');
            }

            $accessToken = self::useService()->createAccessToken($tblAccount, $deviceFactor);

            self::useService()->updateToken($tblToken, $accessToken);

            return new Response201([
                'accessToken' => $tblToken->getAccessToken(),
            ]);

        } catch (Exception $e) {

            return new Response400($e->getMessage());
        }
    }
}

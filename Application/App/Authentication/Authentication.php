<?php

namespace SPHERE\Application\App\Authentication;

use SPHERE\Application\App\Authentication\Factor\AuthenticatorApp;
use SPHERE\Application\App\Authentication\Factor\Credentials;
use SPHERE\Application\App\Authentication\Factor\Token;
use SPHERE\Application\App\Authentication\Process\Service;
use SPHERE\Application\App\Authentication\Process\SignIn;
use SPHERE\Application\App\Authentication\Process\SignOut;
use SPHERE\Application\App\Response\Code\Response400;
use SPHERE\Application\App\Response\Code\Response401;
use SPHERE\Application\App\Response\Code\Response405;
use SPHERE\Application\App\Response\Code\Response501;
use SPHERE\Application\App\Response\ResponseInterface;
use SPHERE\Application\App\ServiceInterface;
use SPHERE\Application\Platform\Gatekeeper\Authorization\Account\Account;
use SPHERE\Common\Main;
use SPHERE\System\Database\Link\Identifier;

/**
 *
 */
class Authentication implements ServiceInterface
{
    public static function registerApplication(): void
    {
        Main::getDispatcher()::registerRoute(Main::getDispatcher()::createRoute(
            __NAMESPACE__ . '/status', __CLASS__ . '::authenticationStatus'
        ));

        Main::getDispatcher()::registerRoute(Main::getDispatcher()::createRoute(
            __NAMESPACE__ . '/access-token', __CLASS__ . '::authenticationAccessToken'
        ));

        SignIn::registerModule();
        SignOut::registerModule();

        Credentials::registerModule();
        Token::registerModule();
        AuthenticatorApp::registerModule();
    }

    public static function useService(): Service
    {
        return new Service(new Identifier('Platform', 'App', 'Authentication'),
            __DIR__ . '/Process/Service/Entity', __NAMESPACE__ . '\Process\Service\Entity'
        );
    }

    /** @noinspection PhpUnused */
    public static function authenticationStatus(
        ?string $deviceFactor = null,
        ?string $credentialIdentifier = null
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

        $tblAccount = !empty($credentialIdentifier) ? Account::useService()->getAccountByUsername($credentialIdentifier) : null;

        // TODO: tblProcessList sortOrder?
        if (($tblProcessList = self::useService()->getAllProcessByDeviceFactor($deviceFactor, $tblAccount ?: null))) {
            foreach ($tblProcessList as $tblProcess) {
                if (!$tblProcess->getIsSolved()) {
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
    public static function authenticationAccessToken(
        ?string $deviceFactor = null,
        ?string $credentialIdentifier = null,
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

        if (empty($credentialIdentifier)) {
            return new Response400('Credentials not provided', [
                'credentialIdentifier' => $credentialIdentifier,
            ]);
        }

        $response = self::useService()->authenticateAuthenticationToken($deviceFactor, $credentialIdentifier);
        if ($response instanceof ResponseInterface) {
            return $response;
        }

        return new Response501(null);
    }
}

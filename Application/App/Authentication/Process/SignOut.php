<?php

namespace SPHERE\Application\App\Authentication\Process;


use SPHERE\Application\App\Authentication\Authentication;
use SPHERE\Application\App\ModuleInterface;
use SPHERE\Application\App\Response\Code\Response200;
use SPHERE\Application\App\Response\Code\Response400;
use SPHERE\Application\App\Response\ResponseInterface;
use SPHERE\Application\Platform\Gatekeeper\Authorization\Account\Account;
use SPHERE\Common\Main;

/**
 *
 */
class SignOut implements ModuleInterface
{

    public static function registerModule(): void
    {
        Main::getDispatcher()::registerRoute(Main::getDispatcher()::createRoute(
            __NAMESPACE__ . '/sign-out', __CLASS__ . '::handleRequest'
        ));
    }

    /**
     * @param string|null $deviceFactor
     * @param string|null $credentialIdentifier
     *
     * @return ResponseInterface
     */
    public static function handleRequest(
        ?string $deviceFactor = null,
        ?string $credentialIdentifier = null
    ): ResponseInterface {

        if (empty($deviceFactor)) {
            return new Response400('Device Factor not provided', [
                'deviceFactor' => $deviceFactor,
            ]);
        }
        if (empty($credentialIdentifier) || !($tblAccount = Account::useService()->getAccountByUsername($credentialIdentifier))) {
            return new Response400('Credential Identifier not provided', [
                'credentialIdentifier' => $credentialIdentifier,
            ]);
        }

        Authentication::useService()->signOut($deviceFactor, $tblAccount);

        return new Response200('Signed out');
    }
}

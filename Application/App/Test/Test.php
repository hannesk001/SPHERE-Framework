<?php

namespace SPHERE\Application\App\Test;

use SPHERE\Application\App\ApplicationInterface;
use SPHERE\Application\App\Authorization\Authorization;
use SPHERE\Application\App\ModuleInterface;
use SPHERE\Application\App\Response\Code\Response200;
use SPHERE\Application\App\Response\ResponseInterface;
use SPHERE\Application\Platform\Gatekeeper\Authorization\Account\Account;
use SPHERE\Common\Main;

class Test implements ApplicationInterface, ModuleInterface
{
    /**
     * @return void
     */
    public static function registerApplication(): void
    {
        self::registerModule();
    }

    /**
     * @return void
     */
    public static function registerModule(): void
    {
        Main::getDispatcher()::registerRoute(
            Main::getDispatcher()::createRoute(
                __NAMESPACE__ . '/person-name', __CLASS__ . '::handleRequest'
            )
        );
    }

    /**
     * @return ResponseInterface
     */
    public static function handleRequest(): ResponseInterface
    {
        $response = Authorization::checkAuthorization();
        // authorization failed
        if ($response instanceof ResponseInterface) {
            return $response;
        }

        $name = '';
        if (($tblPerson = Account::useService()->getPersonByLogin())) {
            $name = $tblPerson->getFullName();
        }

        return new Response200(['name' => $name]);
    }
}
<?php
namespace SPHERE\Application\Setting\Authorization\Account;

use SPHERE\Application\IModuleInterface;
use SPHERE\Common\Frontend\Icon\Repository\PersonKey;
use SPHERE\Common\Main;
use SPHERE\Common\Window\Navigation\Link;
use SPHERE\System\Database\Link\Identifier;

/**
 * Class Account
 *
 * @package SPHERE\Application\Setting\Authorization\Account
 */
class Account implements IModuleInterface
{

    public static function registerModule()
    {

        Main::getDisplay()->addModuleNavigation(
            new Link(new Link\Route(__NAMESPACE__), new Link\Name('Benutzerkonten'), new Link\Icon(new PersonKey()))
        );

        Main::getDispatcher()->registerRoute(
            Main::getDispatcher()->createRoute(__NAMESPACE__, __NAMESPACE__.'\Frontend::frontendAccount')
        );
    }

    /**
     * @return Service
     */
    public static function useService()
    {

        return new Service(new Identifier('Platform', 'Gatekeeper', 'Authorization', 'Account'),
            __DIR__.'/../../../Platform/Gatekeeper/Authorization/Account/Service/Entity',
            '\SPHERE\Application\Platform\Gatekeeper\Authorization\Account\Service\Entity'
        );
    }

    /**
     * @return Frontend
     */
    public static function useFrontend()
    {

        return new Frontend();
    }
}

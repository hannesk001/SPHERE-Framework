<?php
namespace SPHERE\Application\System\Gatekeeper;

use SPHERE\Application\IApplicationInterface;
use SPHERE\Application\System\Gatekeeper\Authentication\Authentication;
use SPHERE\Application\System\Gatekeeper\Authorization\Authorization;
use SPHERE\Common\Frontend\Icon\Repository\PersonKey;
use SPHERE\Common\Main;
use SPHERE\Common\Window\Navigation\Link;
use SPHERE\Common\Window\Stage;

/**
 * Class Gatekeeper
 *
 * @package SPHERE\Application\System\Gatekeeper
 */
class Gatekeeper implements IApplicationInterface
{

    public static function registerApplication()
    {

        Authorization::registerModule();
        Authentication::registerModule();

        Main::getDisplay()->addServiceNavigation(
            new Link( new Link\Route( __NAMESPACE__.'/Authorization' ), new Link\Name( 'Berechtigungen' ),
                new Link\Icon( new PersonKey() ) )
        );
        Main::getDispatcher()->registerRoute( Main::getDispatcher()->createRoute(
            __NAMESPACE__.'/Authorization', __CLASS__.'::frontendWelcome'
        ) );
    }

    /**
     * @return Stage
     */
    public function frontendWelcome()
    {

        $Stage = new Stage( 'Berechtigungen' );
        return $Stage;
    }
}

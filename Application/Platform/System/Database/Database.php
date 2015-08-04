<?php
namespace SPHERE\Application\Platform\System\Database;

use SPHERE\Application\IModuleInterface;
use SPHERE\Application\IServiceInterface;
use SPHERE\Common\Frontend\Icon\Repository\Ok;
use SPHERE\Common\Frontend\Icon\Repository\Warning;
use SPHERE\Common\Frontend\Link\Repository\External;
use SPHERE\Common\Frontend\Link\Repository\Standard;
use SPHERE\Common\Frontend\Table\Structure\Table;
use SPHERE\Common\Frontend\Table\Structure\TableBody;
use SPHERE\Common\Frontend\Table\Structure\TableColumn;
use SPHERE\Common\Frontend\Table\Structure\TableHead;
use SPHERE\Common\Frontend\Table\Structure\TableRow;
use SPHERE\Common\Frontend\Text\Repository\Danger;
use SPHERE\Common\Frontend\Text\Repository\Success;
use SPHERE\Common\Main;
use SPHERE\Common\Window\Navigation\Link;
use SPHERE\Common\Window\Stage;
use SPHERE\System\Database\Link\Identifier;
use SPHERE\System\Extension\Extension;

/**
 * Class Database
 *
 * @package SPHERE\Application\System\Platform\Database
 */
class Database extends Extension implements IModuleInterface
{

    public static function registerModule()
    {

        /**
         * Register Navigation
         */
        Main::getDisplay()->addModuleNavigation(
            new Link( new Link\Route( __NAMESPACE__ ), new Link\Name( 'Datenbank' ) )
        );
        /**
         * Register Route
         */
        Main::getDispatcher()->registerRoute(
            Main::getDispatcher()->createRoute( __NAMESPACE__,
                'Database::frontendStatus'
            )
        );
        Main::getDispatcher()->registerRoute(
            Main::getDispatcher()->createRoute( __NAMESPACE__.'/Setup/Simulation',
                __CLASS__.'::frontendSetup'
            )->setParameterDefault( 'Simulation', true )
        );
        Main::getDispatcher()->registerRoute(
            Main::getDispatcher()->createRoute( __NAMESPACE__.'/Setup/Execution',
                __CLASS__.'::frontendSetup'
            )->setParameterDefault( 'Simulation', false )
        );
    }

    /**
     * @return IServiceInterface
     */
    public static function useService()
    {
        // TODO: Implement useService() method.
    }

    public static function useFrontend()
    {
        // TODO: Implement useFrontend() method.
    }


    /**
     * @return Stage
     */
    public function frontendStatus()
    {

        $Stage = new Stage( 'Database', 'Status' );
        $this->menuButton( $Stage );
        $Configuration = parse_ini_file( __DIR__.'/../../../../System/Database/Configuration.ini', true );
        $Result = array();
        foreach ((array)$Configuration as $Service => $Parameter) {
            $Service = explode( ':', $Service );

            try {
                $Connection = new \SPHERE\System\Database\Database(
                    new Identifier(
                        $Service[0],
                        $Service[1],
                        ( isset( $Service[2] ) ? $Service[2] : null ),
                        ( isset( $Service[3] ) ? $Service[3] : null ),
                        ( isset( $Service[4] ) ? $Service[4] : null )
                    )
                );
                $Status = new Success( 'Verbunden', new Ok() );
            } catch( \Exception $E ) {
                $Status = new Danger( 'Fehler', new Warning() );
            }

            $Result[] = new TableRow( array(
                new TableColumn( $Status ),
                new TableColumn( $Service[0] ),
                new TableColumn( $Service[1] ),
                new TableColumn( ( isset( $Service[2] ) ? $Service[2] : null ) ),
                new TableColumn( ( isset( $Service[3] ) ? $Service[3] : null ) ),
                new TableColumn( ( isset( $Service[4] ) ? $Service[4] : null ) ),
                new TableColumn( $Parameter['Driver'] ),
                new TableColumn( $Parameter['Host'] ),
                new TableColumn( ( isset( $Parameter['Port'] ) ? $Parameter['Port'] : 'Default' ) ),
                new TableColumn( isset( $Connection ) ? $Connection->getDatabase() : '-NA-' )

            ) );
        }

        $Stage->setContent(
            new Table(
                new TableHead(
                    new TableRow( array(
                        new TableColumn( 'Status' ),
                        new TableColumn( 'Cluster' ),
                        new TableColumn( 'Application' ),
                        new TableColumn( 'Module' ),
                        new TableColumn( 'Service' ),
                        new TableColumn( 'Consumer' ),
                        new TableColumn( 'Driver' ),
                        new TableColumn( 'Server' ),
                        new TableColumn( 'Port' ),
                        new TableColumn( 'Database' )
                    ) )
                ),
                new TableBody(
                    $Result
                ), null, true
            )
        );

        return $Stage;
    }

    private function menuButton( Stage $Stage )
    {

        $Stage->addButton( new Standard( 'Status', new Link\Route( __NAMESPACE__ ), null,
            array(), 'Datenbankverbindungen'
        ) );
        $Stage->addButton( new Standard( 'Simulation', new Link\Route( __NAMESPACE__.'/Setup/Simulation' ), null,
            array(), 'Anzeige von Strukturänderungen'
        ) );
        $Stage->addButton( new Standard( 'Durchführung', new Link\Route( __NAMESPACE__.'/Setup/Execution' ), null,
            array(), 'Durchführen von Strukturänderungen und einspielen zugehöriger Daten'
        ) );
        $Stage->addButton( new External( 'phpMyAdmin', $this->getRequest()->getPathBase().'/UnitTest/Console/phpMyAdmin-4.3.12' ) );
    }

    /**
     * @param bool $Simulation
     *
     * @return Stage
     */
    public function frontendSetup( $Simulation = true )
    {

        $Stage = new Stage( 'Database', 'Setup' );
        $this->menuButton( $Stage );
        if ($Simulation) {

            $ClassList = get_declared_classes();
            array_walk( $ClassList, function ( &$Class ) {

                $Inspection = new \ReflectionClass( $Class );
                if ($Inspection->isInternal()) {
                    $Class = false;
                } else {
                    if ($Inspection->implementsInterface( '\SPHERE\Application\IModuleInterface' )) {
                        /** @var IModuleInterface $Class */
                        $Class = $Inspection->newInstance();
                        $Class = $Class->useService();
                        /** @var IServiceInterface $Class */
                        if ($Class instanceof IServiceInterface) {
                            $Class = $Class->setupService( true, false );
                        } else {
                            $Class = false;
                        }
                    } else {
                        $Class = false;
                    }
                }
            } );
            $ClassList = array_filter( $ClassList );

        } else {
            $ClassList = get_declared_classes();
            array_walk( $ClassList, function ( &$Class ) {

                $Inspection = new \ReflectionClass( $Class );
                if ($Inspection->isInternal()) {
                    $Class = false;
                } else {
                    if ($Inspection->implementsInterface( '\SPHERE\Application\IModuleInterface' )) {
                        /** @var IModuleInterface $Class */
                        $Class = $Inspection->newInstance();
                        $Class = $Class->useService();
                        /** @var IServiceInterface $Class */
                        if ($Class instanceof IServiceInterface) {
                            $Class = $Class->setupService( false, false );
                        } else {
                            $Class = false;
                        }
                    } else {
                        $Class = false;
                    }
                }
            } );
            $ClassList = array_filter( $ClassList );

            $DataList = get_declared_classes();
            array_walk( $DataList, function ( &$Class ) {

                $Inspection = new \ReflectionClass( $Class );
                if ($Inspection->isInternal()) {
                    $Class = false;
                } else {
                    if ($Inspection->implementsInterface( '\SPHERE\Application\IModuleInterface' )) {
                        /** @var IModuleInterface $Class */
                        $Class = $Inspection->newInstance();
                        $Class = $Class->useService();
                        /** @var IServiceInterface $Class */
                        if ($Class instanceof IServiceInterface) {
                            $Class = $Class->setupService( false, true );
                        } else {
                            $Class = false;
                        }
                    } else {
                        $Class = false;
                    }
                }
            } );
        }

        $Stage->setContent( implode( $ClassList ) );
        return $Stage;
    }
}

<?php
namespace SPHERE\Application\Education\Certificate\Setting;

use SPHERE\Application\Education\Certificate\Generator\Generator;
use SPHERE\Application\IModuleInterface;
use SPHERE\Common\Main;
use SPHERE\Common\Window\Navigation\Link;
use SPHERE\System\Extension\Extension;

class Setting extends Extension implements IModuleInterface
{

    public static function registerModule()
    {

        Main::getDisplay()->addModuleNavigation(
            new Link(new Link\Route(__NAMESPACE__), new Link\Name('Einstellungen'))
        );

        Main::getDispatcher()->registerRoute(Main::getDispatcher()->createRoute(
            __NAMESPACE__ . '\Template', __NAMESPACE__.'\Frontend::frontendSelectCertificate'
        ));
        Main::getDispatcher()->registerRoute(Main::getDispatcher()->createRoute(
            __NAMESPACE__.'\Configuration', __NAMESPACE__.'\Frontend::frontendCertificateSetting'
        ));
        Main::getDispatcher()->registerRoute(Main::getDispatcher()->createRoute(
            __NAMESPACE__.'\Approval', __NAMESPACE__.'\Frontend::frontendApproval'
        ));
        Main::getDispatcher()->registerRoute(Main::getDispatcher()->createRoute(
            __NAMESPACE__.'\Implement', __NAMESPACE__.'\Frontend::frontendImplement'
        ));
        Main::getDispatcher()->registerRoute(Main::getDispatcher()->createRoute(
            __NAMESPACE__.'\ImplementCertificate', __NAMESPACE__.'\Frontend::frontendImplementCertificate'
        ));
        Main::getDispatcher()->registerRoute(Main::getDispatcher()->createRoute(
            __NAMESPACE__, __NAMESPACE__.'\Frontend::frontendDashboard'
        ));
        Main::getDispatcher()->registerRoute(Main::getDispatcher()->createRoute(
            __NAMESPACE__.'\SkillCertificate', __NAMESPACE__.'\Frontend::frontendSkillCertificate'
        ));
        Main::getDispatcher()->registerRoute(Main::getDispatcher()->createRoute(
            __NAMESPACE__.'\SkillCertificate\Add', __NAMESPACE__.'\Frontend::frontendSkillCertificateAdd'
        ));
        Main::getDispatcher()->registerRoute(Main::getDispatcher()->createRoute(
            __NAMESPACE__.'\SkillCertificate\Sort', __NAMESPACE__.'\Frontend::frontendSkillCertificateSort'
        ));
        Main::getDispatcher()->registerRoute(Main::getDispatcher()->createRoute(
            __NAMESPACE__.'\SkillCertificate\Preview', __NAMESPACE__.'\Frontend::frontendSkillCertificatePreview'
        ));
    }

    /**
     * @return \SPHERE\Application\Education\Certificate\Generator\Service
     */
    public static function useService()
    {

        return Generator::useService();
    }

    /**
     * @return Frontend
     */
    public static function useFrontend()
    {

        return new Frontend();
    }
}

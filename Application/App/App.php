<?php

namespace SPHERE\Application\App;

use SPHERE\Application\App\Authentication\Authentication;
use SPHERE\Application\App\Test\Test;

/**
 *
 */
class App implements ClusterInterface
{
    public static function registerCluster(): void
    {
        Authentication::registerApplication();
        Test::registerApplication();
    }
}

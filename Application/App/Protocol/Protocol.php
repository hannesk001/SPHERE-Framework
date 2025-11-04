<?php

namespace SPHERE\Application\App\Protocol;

use SPHERE\Application\App\ServiceInterface;
use SPHERE\System\Database\Link\Identifier;

class Protocol implements ServiceInterface
{
    public static function useService(): Service
    {
        return new Service(new Identifier('Platform', 'App', 'Protocol'),
            __DIR__ . '/Service/Entity', __NAMESPACE__ . '\Service\Entity'
        );
    }
}
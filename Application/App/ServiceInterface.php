<?php

namespace SPHERE\Application\App;

use SPHERE\Application\IServiceInterface;

interface ServiceInterface
{
    /**
     * @return IServiceInterface
     */
    public static function useService(): IServiceInterface;
}
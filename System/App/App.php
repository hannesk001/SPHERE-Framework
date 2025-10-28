<?php

namespace SPHERE\System\App;

use SPHERE\System\Config\ConfigFactory;
use SPHERE\System\Config\Reader\IniReader;

class App
{
    private string $secret;

    function __construct()
    {
        $Configuration = (new ConfigFactory())
            ->createReader(__DIR__ . '/Configuration.ini', new IniReader())
            ->getConfig();

        $this->secret = ($Configuration->getContainer('APP'))->getContainer('Secret');
    }

    public function getSecret(): string
    {
        return $this->secret;
    }
}
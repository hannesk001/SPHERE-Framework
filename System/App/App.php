<?php

namespace SPHERE\System\App;

use SPHERE\System\Config\ConfigFactory;
use SPHERE\System\Config\Reader\IniReader;

class App
{
    private string $secretAuthentication;
    private string $secretAccess;

    function __construct()
    {
        $Configuration = (new ConfigFactory())
            ->createReader(__DIR__ . '/Configuration.ini', new IniReader())
            ->getConfig();

        $app = $Configuration->getContainer('APP');
        $this->secretAuthentication = $app->getContainer('SecretAuthentication');
        $this->secretAccess = $app->getContainer('SecretAccess');
    }

    public function getSecretAuthentication(): string
    {
        return $this->secretAuthentication;
    }

    public function getSecretAccess(): string
    {
        return $this->secretAccess;
    }
}
<?php

namespace SPHERE\Application\App\Authentication\Process\Service\Entity\Internal;

class Token
{
    private string $token;
    private int $timeout;

    /**
     * @param string $token
     * @param int $timeout
     */
    function __construct(string $token, int $timeout)
    {
        $this->token = $token;
        $this->timeout = $timeout;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }
}
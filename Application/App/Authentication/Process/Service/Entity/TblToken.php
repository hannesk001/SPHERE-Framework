<?php

namespace SPHERE\Application\App\Authentication\Process\Service\Entity;

use Doctrine\ORM\Mapping\Cache;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use SPHERE\Application\Platform\Gatekeeper\Authorization\Account\Account;
use SPHERE\Application\Platform\Gatekeeper\Authorization\Account\Service\Entity\TblAccount;
use SPHERE\System\Database\Fitting\Element;

/**
 * @Entity
 * @Table(name="tblToken")
 * @Cache(usage="READ_ONLY")
 */
class TblToken extends Element
{
    public const ATTR_DEVICE_FACTOR = 'DeviceFactor';
    public const ATTR_AUTHENTICATION_TOKEN = 'AuthenticationToken';
    public const ATTR_ACCESS_TOKEN = 'AccessToken';
    /**
     * @Column(type="bigint")
     */
    protected int $serviceTblAccount;
    /**
     * @Column(type="string")
     */
    protected string $DeviceFactor;
    /**
     * @Column(type="text", nullable=true)
     */
    protected ?string $AuthenticationToken;
    /**
     * @Column(type="integer")
     */
    protected ?int $AuthenticationTimeout;
    /**
     * @Column(type="text", nullable=true)
     */
    protected ?string $AccessToken;
    /**
     * @Column(type="integer")
     */
    protected ?int $AccessTimeout;

    public function getServiceTblAccount(): ?TblAccount
    {
        if (null === $this->serviceTblAccount) {
            return null;
        }
        return Account::useService()->getAccountById($this->serviceTblAccount);
    }

    public function setServiceTblAccount(?TblAccount $tblAccount): void
    {
        $this->serviceTblAccount = $tblAccount?->getId();
    }

    /**
     * @return string
     */
    public function getDeviceFactor(): string
    {
        return $this->DeviceFactor;
    }

    /**
     * @param string $deviceFactor
     *
     * @return void
     */
    public function setDeviceFactor(string $deviceFactor): void
    {
        $this->DeviceFactor = $deviceFactor;
    }

    public function getAuthenticationToken(): ?string
    {
        return $this->AuthenticationToken;
    }

    public function setAuthenticationToken(?string $authenticationToken): void
    {
        $this->AuthenticationToken = $authenticationToken;
    }

    public function getAuthenticationTimeout(): ?int
    {
        return $this->AuthenticationTimeout;
    }

    public function setAuthenticationTimeout(?int $authenticationTimeout): void
    {
        $this->AuthenticationTimeout = $authenticationTimeout;
    }


    public function getAccessToken(): ?string
    {
        return $this->AccessToken;
    }

    public function setAccessToken(?string $accessToken): void
    {
        $this->AccessToken = $accessToken;
    }

    public function getAccessTimeout(): ?int
    {
        return $this->AccessTimeout;
    }

    public function setAccessTimeout(?int $accessTimeout): void
    {
        $this->AccessTimeout = $accessTimeout;
    }
}

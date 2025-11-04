<?php

namespace SPHERE\Application\App\Protocol\Service\Entity;

use Doctrine\ORM\Mapping\Cache;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use SPHERE\Application\Platform\Gatekeeper\Authorization\Account\Service\Entity\TblAccount;
use SPHERE\System\Database\Fitting\Element;

/**
 * @Entity
 * @Table(name="tblRequest")
 * @Cache(usage="READ_ONLY")
 */
class TblRequest extends Element
{
    /**
     * @Column(type="bigint", nullable=true)
     */
    protected ?int $serviceTblAccount;
    /**
     * @Column(type="string")
     */
    protected string $DeviceFactor;
    /**
     * @Column(type="string")
     */
    protected string $Route;
    /**
     * @Column(type="string")
     */
    protected string $Method;
    /**
     * @Column(type="string")
     */
    protected ?string $Params;
    /**
     * @Column(type="string")
     */
    protected ?string $Headers;
    /**
     * @Column(type="string")
     */
    protected ?string $Data;


    public function setServiceTblAccount(?TblAccount $tblAccount): TblRequest
    {
        $this->serviceTblAccount = $tblAccount?->getId();
        return $this;
    }

    public function setDeviceFactor(string $DeviceFactor): TblRequest
    {
        $this->DeviceFactor = $DeviceFactor;
        return $this;
    }

    public function setRoute(string $Route): TblRequest
    {
        $this->Route = $Route;
        return $this;
    }

    public function setMethod(string $Method): TblRequest
    {
        $this->Method = $Method;
        return $this;
    }

    public function setParams(?array $params): TblRequest
    {
        $this->Params = empty($params) ? null : json_encode($params, true);
        return $this;
    }

    public function setHeaders(?array $headers): TblRequest
    {
        $this->Headers = empty($headers) ? null : json_encode($headers, true);
        return $this;
    }

    public function setData(?array $data): TblRequest
    {
        $this->Data =  empty($data) ? null : json_encode($data, true);
        return $this;
    }
}
<?php

namespace SPHERE\Application\App\Protocol\Service\Entity;

use Doctrine\ORM\Mapping\Cache;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use SPHERE\System\Database\Fitting\Element;

/**
 * @Entity
 * @Table(name="tblResponse")
 * @Cache(usage="READ_ONLY")
 */
class TblResponse extends Element
{
    /**
     * @Column(type="bigint")
     */
    protected int $tblRequest;
    /**
     * @Column(type="string")
     */
    protected string $Code;
    /**
     * @Column(type="string")
     */
    protected ?string $Content;

    /**
     * @param TblRequest $tblRequest
     *
     * @return $this
     */
    public function setTblRequest(TblRequest $tblRequest): TblResponse
    {
        $this->tblRequest = $tblRequest->getId();
        return $this;
    }

    /**
     * @param string $Code
     *
     * @return $this
     */
    public function setCode(string $Code): TblResponse
    {
        $this->Code = $Code;
        return $this;
    }

    /**
     * @param string|null $Content
     *
     * @return $this
     */
    public function setContent(?string $Content): TblResponse
    {
        $this->Content = $Content;
        return $this;
    }
}
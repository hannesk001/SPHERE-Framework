<?php
namespace SPHERE\Application\People\Meta\Student\Service\Entity;

use Doctrine\ORM\Mapping\Cache;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use SPHERE\Application\Corporation\Company\Company;
use SPHERE\Application\Corporation\Company\Service\Entity\TblCompany;
use SPHERE\System\Database\Fitting\Element;

/**
 * @Entity
 * @Table(name="tblStudentTransferLeave")
 * @Cache(usage="READ_ONLY")
 */
class TblStudentTransferLeave extends Element
{

    const SERVICE_TBL_COMPANY = 'serviceTblCompany';

    /**
     * @Column(type="bigint")
     */
    protected $serviceTblCompany;
    /**
     * @Column(type="datetime")
     */
    protected $LeaveDate;
    /**
     * @Column(type="text")
     */
    protected $Remark;

    /**
     * @return string
     */
    public function getLeaveDate()
    {

        if (null === $this->LeaveDate) {
            return false;
        }
        /** @var \DateTime $LeaveDate */
        $LeaveDate = $this->LeaveDate;
        if ($LeaveDate instanceof \DateTime) {
            return $LeaveDate->format('d.m.Y');
        } else {
            return (string)$LeaveDate;
        }
    }

    /**
     * @param null|\DateTime $LeaveDate
     */
    public function setLeaveDate(\DateTime $LeaveDate = null)
    {

        $this->LeaveDate = $LeaveDate;
    }

    /**
     * @return string
     */
    public function getRemark()
    {

        return $this->Remark;
    }

    /**
     * @param string $Remark
     */
    public function setRemark($Remark)
    {

        $this->Remark = $Remark;
    }

    /**
     * @return bool|TblCompany
     */
    public function getServiceTblCompany()
    {

        if (null === $this->serviceTblCompany) {
            return false;
        } else {
            return Company::useService()->getCompanyById($this->serviceTblCompany);
        }
    }

    /**
     * @param TblCompany|null $tblCompany
     */
    public function setServiceTblCompany(TblCompany $tblCompany = null)
    {

        $this->serviceTblCompany = ( null === $tblCompany ? null : $tblCompany->getId() );
    }
}

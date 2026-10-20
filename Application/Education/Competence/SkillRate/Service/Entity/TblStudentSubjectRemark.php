<?php

namespace SPHERE\Application\Education\Competence\SkillRate\Service\Entity;

use Doctrine\ORM\Mapping\Cache;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use SPHERE\Application\Education\Certificate\Prepare\Prepare;
use SPHERE\Application\Education\Certificate\Prepare\Service\Entity\TblPrepareCertificate;
use SPHERE\Application\Education\Lesson\Subject\Service\Entity\TblSubject;
use SPHERE\Application\Education\Lesson\Subject\Subject;
use SPHERE\Application\People\Person\Person;
use SPHERE\Application\People\Person\Service\Entity\TblPerson;
use SPHERE\System\Database\Fitting\Element;

/**
 * @Entity()
 * @Table(name="tblCompetenceStudentSubjectRemark")
 * @Cache(usage="READ_ONLY")
 */
class TblStudentSubjectRemark extends Element
{
    const string SERVICE_TBL_PERSON = 'serviceTblPerson';
    const string SERVICE_TBL_SUBJECT = 'serviceTblSubject';
    const string SERVICE_TBL_PREPARE_CERTIFICATE= 'serviceTblPrepareCertificate';

    /**
     * @Column(type="bigint")
     */
    protected ?int $serviceTblPrepareCertificate = null;
    /**
     * @Column(type="bigint")
     */
    protected int $serviceTblPerson;
    /**
     * @Column(type="bigint")
     */
    protected ?int $serviceTblSubject = null;
    /**
     * @Column(type="string")
     */
    protected string $Remark;
    /**
     * @Column(type="bigint")
     */
    protected ?int $serviceTblPersonTeacher = null;

    /**
     * @return false|TblPrepareCertificate
     */
    public function getServiceTblPrepareCertificate(): false|TblPrepareCertificate
    {
        return $this->serviceTblPrepareCertificate
            ? Prepare::useService()->getPrepareById($this->serviceTblPrepareCertificate)
            : false;
    }

    /**
     * @param TblPrepareCertificate|null $tblPrepareCertificate
     *
     * @return void
     */
    public function setServiceTblPrepareCertificate(?TblPrepareCertificate $tblPrepareCertificate): void
    {
        $this->serviceTblPrepareCertificate = $tblPrepareCertificate?->getId();
    }

    /**
    * @return false|TblPerson
    */
    public function getServiceTblPerson(): false|TblPerson
    {
        return Person::useService()->getPersonById($this->serviceTblPerson);
    }

    /**
     * @param TblPerson $tblPerson
     */
    public function setServiceTblPerson(TblPerson $tblPerson): void
    {
        $this->serviceTblPerson = $tblPerson->getId();
    }

    /**
     * @return false|TblSubject
     */
    public function getServiceTblSubject(): false|TblSubject
    {
        return $this->serviceTblSubject ? Subject::useService()->getSubjectById($this->serviceTblSubject) : false;
    }

    /**
     * @param TblSubject|null $tblSubject
     */
    public function setServiceTblSubject(?TblSubject $tblSubject): void
    {
        $this->serviceTblSubject = $tblSubject?->getId();
    }

    /**
     * @return string
     */
    public function getRemark(): string
    {
        return $this->Remark;
    }

    /**
     * @param string $Remark
     *
     * @return void
     */
    public function setRemark(string $Remark): void
    {
        $this->Remark = $Remark;
    }

    /**
     * @return false|TblPerson|null
     */
    public function getServiceTblPersonTeacher(): false|TblPerson|null
    {
        return $this->serviceTblPersonTeacher ? Person::useService()->getPersonById($this->serviceTblPersonTeacher) : null;
    }

    /**
     * @param TblPerson|null $tblPerson
     */
    public function setServiceTblPersonTeacher(?TblPerson $tblPerson): void
    {
        $this->serviceTblPersonTeacher = $tblPerson?->getId();
    }
}
<?php

namespace SPHERE\Application\Education\Competence\SkillRate\Service\Entity;

use Doctrine\ORM\Mapping\Cache;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use SPHERE\Application\Education\Competence\SkillRate\SkillRate;
use SPHERE\System\Database\Fitting\Element;

/**
 * @Entity()
 * @Table(name="tblCompetenceStudentSkillCertificate")
 * @Cache(usage="READ_ONLY")
 */
class TblStudentSkillCertificate extends Element
{
    const string TBL_STUDENT_SKILL = 'tblCompetenceStudentSkill';

    /**
     * @Column(type="bigint")
     */
    protected int $tblCompetenceStudentSkill;

    /**
     * @return false|TblStudentSkill
     * @noinspection PhpUnused
     */
    public function getTblStudentSkill(): false|TblStudentSkill
    {
        return SkillRate::useService()->getStudentSkillById($this->tblCompetenceStudentSkill);
    }

    /**
     * @param TblStudentSkill $tblStudentSkill
     *
     * @return void
     */
    public function setTblStudentSkill(TblStudentSkill $tblStudentSkill): void
    {
        $this->tblCompetenceStudentSkill = $tblStudentSkill->getId();
    }
}
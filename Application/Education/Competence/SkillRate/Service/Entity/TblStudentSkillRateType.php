<?php

namespace SPHERE\Application\Education\Competence\SkillRate\Service\Entity;

use Doctrine\ORM\Mapping\Cache;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use SPHERE\System\Database\Fitting\Element;

/**
 * @Entity()
 * @Table(name="tblCompetenceStudentSkillRateType")
 * @Cache(usage="READ_ONLY")
 */
class TblStudentSkillRateType extends Element
{
    const string ATTR_IDENTIFIER = 'Identifier';
    const string IDENTIFIER_SUBJECT_RATE = 'SUBJECT_RATE';
    const string IDENTIFIER_SUBJECT_CERTIFICATE = 'SUBJECT_CERTIFICATE';
    const string IDENTIFIER_INTERDISCIPLINARY_RATE = 'INTERDISCIPLINARY_RATE';
    const string IDENTIFIER_INTERDISCIPLINARY_CERTIFICATE = 'INTERDISCIPLINARY_CERTIFICATE';


    /**
     * @Column(type="string")
     */
    protected string $Name = '';
    /**
     * @Column(type="string")
     */
    protected string $Identifier = '';

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->Name;
    }

    /**
     * @param string $Name
     */
    public function setName(string $Name): void
    {
        $this->Name = $Name;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->Identifier;
    }

    /**
     * @param string $Identifier
     */
    public function setIdentifier(string $Identifier): void
    {
        $this->Identifier = $Identifier;
    }
}
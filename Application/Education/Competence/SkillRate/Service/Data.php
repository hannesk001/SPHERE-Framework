<?php

namespace SPHERE\Application\Education\Competence\SkillRate\Service;

use DateTime;
use SPHERE\Application\Education\Certificate\Prepare\Service\Entity\TblPrepareCertificate;
use SPHERE\Application\Education\Competence\ScoreType\Service\Entity\TblScoreType;
use SPHERE\Application\Education\Competence\ScoreType\Service\Entity\TblScoreTypeItem;
use SPHERE\Application\Education\Competence\SkillGrid\Service\Entity\TblSkill;
use SPHERE\Application\Education\Competence\SkillRate\Service\Entity\TblStudentSkill;
use SPHERE\Application\Education\Competence\SkillRate\Service\Entity\TblStudentSkillRate;
use SPHERE\Application\Education\Competence\SkillRate\Service\Entity\TblStudentSkillRateType;
use SPHERE\Application\Education\Lesson\Subject\Service\Entity\TblSubject;
use SPHERE\Application\Education\Lesson\Subject\Subject;
use SPHERE\Application\Education\Lesson\Term\Service\Entity\TblYear;
use SPHERE\Application\People\Person\Service\Entity\TblPerson;
use SPHERE\Application\Platform\System\Protocol\Protocol;
use SPHERE\System\Database\Binding\AbstractData;
use SPHERE\System\Database\Fitting\Element;

class Data extends AbstractData
{
    /**
     * @return void
     */
    public function setupDatabaseContent(): void
    {
        $this->createStudentSkillRateType('Fach Kompetenz-Bewertung', TblStudentSkillRateType::IDENTIFIER_SUBJECT_RATE);
        $this->createStudentSkillRateType('Fach Zeugnis-Bewertung', TblStudentSkillRateType::IDENTIFIER_SUBJECT_CERTIFICATE);
        $this->createStudentSkillRateType('Fächerübergreifende Kompetenz-Bewertung durch Fachlehrer', TblStudentSkillRateType::IDENTIFIER_INTERDISCIPLINARY_RATE);
        $this->createStudentSkillRateType('Fächerübergreifende Zeugnis-Bewertung durch Klassenlehrer', TblStudentSkillRateType::IDENTIFIER_INTERDISCIPLINARY_CERTIFICATE);

        // TODO Remove after DB-Update auf LIVE-Servern
        $this->migrateStudentSkillRateType();
    }

    /**
     * @param $id
     *
     * @return TblStudentSkill|false
     */
    public function getStudentSkillById($id): false|TblStudentSkill
    {
        return $this->getCachedEntityById(__METHOD__, $this->getEntityManager(), 'TblStudentSkill', $id);
    }

    /**
     * @param TblPerson $tblPerson
     * @param TblYear $tblYear
     * @param TblSkill $tblSkill
     *
     * @return false|TblStudentSkill
     */
    public function getStudentSkillBy(TblPerson $tblPerson, TblYear $tblYear, TblSkill $tblSkill): false|TblStudentSkill
    {
        return $this->getCachedEntityBy(__METHOD__, $this->getEntityManager(), 'TblStudentSkill', [
            TblStudentSkill::SERVICE_TBL_PERSON => $tblPerson->getId(),
            TblStudentSkill::SERVICE_TBL_YEAR => $tblYear->getId(),
            TblStudentSkill::SERVICE_TBL_SKILL => $tblSkill->getId(),
        ]);
    }

    /**
     * @param TblPerson $tblPerson
     * @param TblYear $tblYear
     * @param TblSubject|null $tblSubject
     * @param string $skillName
     *
     * @return false|TblStudentSkill
     */
    public function getStudentSkillBySkillName(TblPerson $tblPerson, TblYear $tblYear, ?TblSubject $tblSubject, string $skillName): false|TblStudentSkill
    {
        return $this->getCachedEntityBy(__METHOD__, $this->getEntityManager(), 'TblStudentSkill', [
            TblStudentSkill::SERVICE_TBL_PERSON => $tblPerson->getId(),
            TblStudentSkill::SERVICE_TBL_YEAR => $tblYear->getId(),
            TblStudentSkill::SERVICE_TBL_SUBJECT => $tblSubject?->getId(),
            TblStudentSkill::ATTR_SKILL => $skillName,
        ]);
    }

    /**
     * @param TblPerson $tblPerson
     * @param TblYear $tblYear
     * @param TblSubject|null $tblSubject
     *
     * @return TblStudentSkill[]
     */
    public function getStudentSkillListByPersonAndYear(TblPerson $tblPerson, TblYear $tblYear, ?TblSubject $tblSubject): array
    {
        return $this->getCachedEntityListBy(__METHOD__, $this->getEntityManager(), 'TblStudentSkill', [
            TblStudentSkill::SERVICE_TBL_PERSON => $tblPerson->getId(),
            TblStudentSkill::SERVICE_TBL_YEAR => $tblYear->getId(),
            TblStudentSkill::SERVICE_TBL_SUBJECT => $tblSubject?->getId()
        ], [Element::ENTITY_CREATE => self::ORDER_ASC]) ?: [];
    }

    /**
     * @param TblPerson $tblPerson
     * @param TblYear $tblYear
     * @param TblSubject|null $tblSubject
     * @param TblSkill|null $tblSkill
     * @param TblPerson|null $tblPersonTeacher
     * @param string|null $skillArea
     * @param string|null $skillLevel
     * @param string $skill
     * @param bool|null $isAverage
     * @param TblScoreType|null $tblScoreType
     *
     * @return TblStudentSkill
     */
    public function createStudentSkill(TblPerson $tblPerson, TblYear $tblYear, ?TblSubject $tblSubject, ?TblSkill $tblSkill,
        ?TblPerson $tblPersonTeacher, ?string $skillArea, ?string $skillLevel, string $skill,
        ?bool $isAverage = null, ?TblScoreType $tblScoreType = null): TblStudentSkill
    {
        $manager = $this->getEntityManager();

        $entity = new TblStudentSkill();
        $entity->setServiceTblPerson($tblPerson);
        $entity->setServiceTblYear($tblYear);
        $entity->setServiceTblSubject($tblSubject);
        $entity->setServiceTblPersonTeacher($tblPersonTeacher);
        $entity->setServiceTblSkill($tblSkill);
        $entity->setSkillArea($skillArea);
        $entity->setSkillLevel($skillLevel);
        $entity->setSkill($skill);
        $entity->setIsAverage($isAverage);
        $entity->setServiceTblScoreType($tblScoreType);

        $manager->saveEntity($entity);
        Protocol::useService()->createInsertEntry($this->getConnection()->getDatabase(), $entity);

        return $entity;
    }

    /**
     * @param TblStudentSkill $tblStudentSkill
     * @param TblPerson|null $tblPersonTeacher
     * @param string|null $skillArea
     * @param string|null $skillLevel
     * @param string $skill
     * @param bool|null $isAverage
     * @param TblScoreType|null $tblScoreType
     *
     * @return bool
     */
    public function updateStudentSkill(TblStudentSkill $tblStudentSkill, ?TblPerson $tblPersonTeacher,
        ?string $skillArea, ?string $skillLevel, string $skill,
        ?bool $isAverage = null, ?TblScoreType $tblScoreType = null): bool
    {
        $Manager = $this->getEntityManager();
        /** @var TblStudentSkill $Entity */
        $Entity = $Manager->getEntityById('TblStudentSkill', $tblStudentSkill->getId());
        $Protocol = clone $Entity;
        if (null !== $Entity) {
            if ($skillArea !== null) {
                $Entity->setSkillArea($skillArea);
            }
            $Entity->setSkillLevel($skillLevel ?: null);
            $Entity->setSkill($skill);
            $Entity->setServiceTblPersonTeacher($tblPersonTeacher);
            $Entity->setIsAverage($isAverage);
            $Entity->setServiceTblScoreType($tblScoreType);

            $Manager->saveEntity($Entity);
            Protocol::useService()->createUpdateEntry($this->getConnection()->getDatabase(), $Protocol, $Entity);

            return true;
        }

        return false;
    }

    /**
     * @param $id
     *
     * @return TblStudentSkillRate|false
     */
    public function getStudentSkillRateById($id): false|TblStudentSkillRate
    {
        return $this->getCachedEntityById(__METHOD__, $this->getEntityManager(), 'TblStudentSkillRate', $id);
    }

    public function getStudentSkillRateBy(
        TblStudentSkill $tblStudentSkill, TblStudentSkillRateType $tblStudentSkillRateType, ?TblPrepareCertificate $tblPrepareCertificate
    ): false|TblStudentSkillRate {
        return $this->getCachedEntityBy(__METHOD__, $this->getEntityManager(), 'TblStudentSkillRate', [
            TblStudentSkillRate::TBL_STUDENT_SKILL => $tblStudentSkill->getId(),
            TblStudentSkillRate::TBL_STUDENT_SKILL_RATE_TYPE => $tblStudentSkillRateType->getId(),
            TblStudentSkillRate::SERVICE_TBL_PREPARE_CERTIFICATE => $tblPrepareCertificate?->getId()
        ]);
    }

    /**
     * @param TblStudentSkill $tblStudentSkill
     * @param TblStudentSkillRateType $tblStudentSkillRateType
     * @param TblSubject|null $tblSubjectForSkillRate
     *
     * @return TblStudentSkillRate[]
     */
    public function getStudentSkillRateListBy(
        TblStudentSkill $tblStudentSkill, TblStudentSkillRateType $tblStudentSkillRateType, ?TblSubject $tblSubjectForSkillRate
    ): array {
        return $this->getCachedEntityListBy(__METHOD__, $this->getEntityManager(), 'TblStudentSkillRate',
            [
                TblStudentSkillRate::TBL_STUDENT_SKILL => $tblStudentSkill->getId(),
                TblStudentSkillRate::TBL_STUDENT_SKILL_RATE_TYPE => $tblStudentSkillRateType,
                TblStudentSkillRate::SERVICE_TBL_SUBJECT => $tblSubjectForSkillRate?->getId()
            ],
            [TblStudentSkillRate::ATTR_DATE => self::ORDER_ASC]) ?: [];
    }

    /**
     * @param TblStudentSkill $tblStudentSkill
     *
     * @return TblSubject[]
     */
    public function getSubjectListForStudentSkillRateInterdisciplinary(TblStudentSkill $tblStudentSkill): array
    {
        $Manager = $this->getEntityManager();
        $queryBuilder = $Manager->getQueryBuilder();

        $query = $queryBuilder->select('t.serviceTblSubject')
            ->from(TblStudentSkillRate::class, 't')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq('t.tblCompetenceStudentSkill', '?1'),
                    $queryBuilder->expr()->eq('t.tblCompetenceStudentSkillRateType', '?2'),
                )
            )
            ->setParameter(1, $tblStudentSkill->getId())
            ->setParameter(2, $this->getStudentSkillRateTypeByIdentifier(TblStudentSkillRateType::IDENTIFIER_INTERDISCIPLINARY_RATE))
            ->distinct()
            ->groupBy('t.serviceTblSubject')
            ->getQuery();

        $resultList = $query->getResult();

        $tblSubjectList = [];
        if (is_array($resultList)) {
            foreach ($resultList as $item) {
                if (($tblSubject = Subject::useService()->getSubjectById($item['serviceTblSubject']))) {
                    $tblSubjectList[] = $tblSubject;
                }
            }
        }

        return $tblSubjectList;
    }

    /**
     * @param TblStudentSkill $tblStudentSkill
     * @param TblStudentSkillRateType $tblStudentSkillRateType
     * @param TblPerson|null $tblPersonTeacher
     * @param DateTime $dateTime
     * @param string|null $comment
     * @param string $rate
     * @param TblScoreTypeItem|null $tblScoreTypeItem
     * @param TblSubject|null $tblSubject
     *
     * @return TblStudentSkillRate
     */
    public function createStudentSkillRate(TblStudentSkill $tblStudentSkill, TblStudentSkillRateType $tblStudentSkillRateType,
        ?TblPerson $tblPersonTeacher, DateTime $dateTime, ?string $comment, string $rate, ?TblScoreTypeItem $tblScoreTypeItem, ?TblSubject $tblSubject = null)
    : TblStudentSkillRate {
        $manager = $this->getEntityManager();

        $entity = new TblStudentSkillRate();
        $entity->setTblStudentSkill($tblStudentSkill);
        $entity->setTblStudentSkillRateType($tblStudentSkillRateType);
        $entity->setDate($dateTime);
        $entity->setComment($comment);
        $entity->setRate($rate);
        $entity->setServiceTblScoreTypeItem($tblScoreTypeItem);
        $entity->setServiceTblPersonTeacher($tblPersonTeacher);
        $entity->setServiceTblSubject($tblSubject);

        $manager->saveEntity($entity);
        Protocol::useService()->createInsertEntry($this->getConnection()->getDatabase(), $entity);

        return $entity;
    }

    /**
     * @param TblStudentSkillRate $tblStudentSkillRate
     * @param TblPerson|null $tblPersonTeacher
     * @param DateTime $dateTime
     * @param string|null $comment
     * @param string $rate
     * @param TblScoreTypeItem|null $tblScoreTypeItem
     *
     * @return bool
     */
    public function updateStudentSkillRate(TblStudentSkillRate $tblStudentSkillRate,
        ?TblPerson $tblPersonTeacher, DateTime $dateTime, ?string $comment, string $rate, ?TblScoreTypeItem $tblScoreTypeItem): bool
    {
        $manager = $this->getEntityManager();
        /** @var TblStudentSkillRate $entity */
        $entity = $manager->getEntityById('TblStudentSkillRate', $tblStudentSkillRate->getId());
        $protocol = clone $entity;
        if (null !== $entity) {
            $entity->setDate($dateTime);
            $entity->setComment($comment);
            $entity->setRate($rate);
            $entity->setServiceTblScoreTypeItem($tblScoreTypeItem);
            $entity->setServiceTblPersonTeacher($tblPersonTeacher);

            $manager->saveEntity($entity);
            Protocol::useService()->createUpdateEntry($this->getConnection()->getDatabase(), $protocol, $entity);

            return true;
        }

        return false;
    }

    /**
     * @param TblStudentSkillRate $tblStudentSkillRate
     *
     * @return bool
     */
    public function deleteStudentSkillRate(TblStudentSkillRate $tblStudentSkillRate): bool
    {
        $Manager = $this->getEntityManager();

        /** @var TblStudentSkillRate $Entity */
        $Entity = $Manager->getEntityById('TblStudentSkillRate', $tblStudentSkillRate->getId());
        if (null !== $Entity) {
            Protocol::useService()->createDeleteEntry($this->getConnection()->getDatabase(), $Entity);
            $Manager->killEntity($Entity);

            return true;
        }

        return false;
    }

    /**
     * @param array $tblEntityList
     *
     * @return bool
     */
    public function createEntityListBulk(array $tblEntityList): bool
    {
        $Manager = $this->getConnection()->getEntityManager();

        foreach ($tblEntityList as $tblEntity) {
            $Manager->bulkSaveEntity($tblEntity);
            Protocol::useService()->createInsertEntry($this->getConnection()->getDatabase(), $tblEntity, true);
        }

        $Manager->flushCache();
        Protocol::useService()->flushBulkEntries();

        return true;
    }

    /**
     * @param array $tblStudentSkillRateList
     */
    public function updateStudentSkillRateListBulk(array $tblStudentSkillRateList): void
    {
        $Manager = $this->getConnection()->getEntityManager();
        /** @var TblStudentSkillRate $Entity */
        foreach ($tblStudentSkillRateList as $tblStudentSkillRate) {
            $Entity = $Manager->getEntityById('TblStudentSkillRate', $tblStudentSkillRate->getId());
            $Protocol = clone $Entity;
            if (null !== $Entity) {
                $Entity->setRate($tblStudentSkillRate->getRate());
                $Entity->setServiceTblScoreTypeItem($tblStudentSkillRate->getServiceTblScoreTypeItem() ?: null);
                $Entity->setServiceTblPersonTeacher($tblStudentSkillRate->getServiceTblPersonTeacher());

                $Manager->bulkSaveEntity($Entity);
                Protocol::useService()->createUpdateEntry($this->getConnection()->getDatabase(), $Protocol, $Entity, true);
            }
        }

        $Manager->flushCache();
        Protocol::useService()->flushBulkEntries();
    }

    /**
     * @param array $tblEntityList
     *
     * @return bool
     */
    public function deleteEntityListBulk(array $tblEntityList): bool
    {
        $Manager = $this->getConnection()->getEntityManager();

        /** @var Element $tblElement */
        foreach ($tblEntityList as $tblElement) {

            /** @var Element $Entity */
            $Entity = $Manager->getEntityById($tblElement->getEntityShortName(), $tblElement->getId());

            Protocol::useService()->createDeleteEntry($this->getConnection()->getDatabase(), $Entity, true);
            $Manager->bulkKillEntity($Entity);
        }

        $Manager->flushCache();
        Protocol::useService()->flushBulkEntries();

        return true;
    }

    /**
     * @param $id
     *
     * @return TblStudentSkillRateType|false
     */
    public function getStudentSkillRateTypeById($id): false|TblStudentSkillRateType
    {
        return $this->getCachedEntityById(__METHOD__, $this->getEntityManager(), 'TblStudentSkillRateType', $id);
    }

    /**
     * @param $identifier
     *
     * @return TblStudentSkillRateType|false
     */
    public function getStudentSkillRateTypeByIdentifier($identifier): false|TblStudentSkillRateType
    {
        return $this->getCachedEntityBy(__METHOD__, $this->getEntityManager(), 'TblStudentSkillRateType',
            [TblStudentSkillRateType::ATTR_IDENTIFIER => $identifier]);
    }

    /**
     * @param string $name
     * @param string $identifier
     *
     * @return TblStudentSkillRateType
     */
    public function createStudentSkillRateType(string $name, string $identifier): TblStudentSkillRateType
    {
        $Manager = $this->getEntityManager();
        $identifier = strtoupper($identifier);
        $Entity = $Manager->getEntity('TblStudentSkillRateType')->findOneBy(array(TblStudentSkillRateType::ATTR_IDENTIFIER => $identifier));
        if (null === $Entity) {
            $Entity = new TblStudentSkillRateType();
            $Entity->setName($name);
            $Entity->setIdentifier($identifier);

            $Manager->saveEntity($Entity);
            Protocol::useService()->createInsertEntry($this->getConnection()->getDatabase(), $Entity);
        }

        return $Entity;
    }

    /**
     * @return void
     */
    public function migrateStudentSkillRateType(): void
    {
        $Manager = $this->getConnection()->getEntityManager();
        $tblStudentSkillRateTypeSubject = $this->getStudentSkillRateTypeByIdentifier(TblStudentSkillRateType::IDENTIFIER_SUBJECT_RATE);
        $tblStudentSkillRateTypeInterdisciplinary = $this->getStudentSkillRateTypeByIdentifier(TblStudentSkillRateType::IDENTIFIER_INTERDISCIPLINARY_RATE);

        if ($tblStudentSkillRateTypeSubject
            && $tblStudentSkillRateTypeInterdisciplinary
            && ($list = $this->getCachedEntityListBy(__METHOD__, $this->getEntityManager(), 'TblStudentSkillRate',
                [TblStudentSkillRate::TBL_STUDENT_SKILL_RATE_TYPE => null]))
        ) {
            /** @var TblStudentSkillRate $tblStudentSkillRate */
            foreach ($list as $tblStudentSkillRate) {
                if ($tblStudentSkillRate->getServiceTblSubject()) {
                    $tblStudentSkillRateType = $tblStudentSkillRateTypeInterdisciplinary;
                } else {
                    $tblStudentSkillRateType = $tblStudentSkillRateTypeSubject;
                }

                // Update
                /** @var TblStudentSkillRate $Entity */
                $Entity = $Manager->getEntityById('TblStudentSkillRate', $tblStudentSkillRate->getId());
                $Protocol = clone $Entity;
                if (null !== $Entity) {
                    $Entity->setTblStudentSkillRateType($tblStudentSkillRateType);

                    $Manager->bulkSaveEntity($Entity);
                    Protocol::useService()->createUpdateEntry($this->getConnection()->getDatabase(), $Protocol, $Entity, true);
                }
            }

            $Manager->flushCache();
            Protocol::useService()->flushBulkEntries();
        }
    }
}
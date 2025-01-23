<?php

namespace SPHERE\Application\Api\Education\Certificate\Generator\Repository;

use SPHERE\Application\Api\Education\Certificate\Generator\Certificate;
use SPHERE\Application\Education\Certificate\Generator\Repository\Element;
use SPHERE\Application\Education\Certificate\Generator\Repository\Page;
use SPHERE\Application\Education\Certificate\Generator\Repository\Section;
use SPHERE\Application\Education\Certificate\Generator\Repository\Slice;
use SPHERE\Application\Education\Graduation\Grade\Grade;
use SPHERE\Application\Education\Lesson\Subject\Subject;
use SPHERE\Application\People\Person\Service\Entity\TblPerson;

class SkillCertificate extends Certificate
{

    /**
     * @return array
     */
    public function selectValuesTransfer()
    {
        return array(
            1 => "wird versetzt",
            2 => "wird nicht versetzt"
        );
    }

    /**
     * @param TblPerson|null $tblPerson
     * @return Page
     * @internal param bool $IsSample
     *
     */
    public function buildPages(TblPerson $tblPerson = null)
    {

        $personId = $tblPerson ? $tblPerson->getId() : 0;

        $Header = $this->getHead($this->isSample());

        return (new Page())
            ->addSlice(
                $Header
            )
            ->addSlice($this->getSchoolName($personId))
            ->addSlice($this->getCertificateHead('Kompetenz-Zeugnis der Grundschule'))
            ->addSlice($this->getDivisionAndYear($personId))
            ->addSlice($this->getStudentName($personId))
            ->addSlice($this->getSubject(null))
            ->addSlice($this->getSubject('DEU'))
            ->addSlice($this->getSubject('MA'))
            ->addSlice($this->getDescriptionHead($personId, true))
            ->addSlice($this->getDescriptionContent($personId, '35px', '5px'))
            ->addSlice($this->getDateLine($personId, '10px'))
            ->addSlice($this->getSignPart($personId, true))
            ->addSlice($this->getParentSign('30px'))
            ->addSlice($this->getInfo('2px',
                'Für die Einschätzung der fachlichen Kompetenzen gilt folgende Skala:',
                '1 - übertrifft die Anforderung - liegt deutlich über den Regelanforderungen und jahrgangsgemäßen Erwartungen',
                '...'
            ));
    }

    private function getSubject(?string $acronym): Slice
    {
        $tblSubject = Subject::useService()->getSubjectByAcronym($acronym);
        $skills = Grade::useFrontend()->getSkills($tblSubject ?: null);

        $slice = new Slice();
        $slice
            ->styleMarginTop('20px')
            ->styleBorderTop()
            ->styleBorderLeft()
            ->styleBorderRight()
            ->addElement((new Element())
                ->setContent($tblSubject ? $tblSubject->getName(): 'Überfachliche Kompetenzen')
                ->styleTextSize('16px')
                ->stylePaddingTop('10px')
                ->stylePaddingBottom('10px')
                ->stylePaddingLeft('5px')
                ->styleTextBold()
                ->styleBorderBottom()
            );

        $dataList = array();
        foreach ($skills as $skill) {
            $category = $skill['Category'];
            if (!isset($dataList[$category])) {
                $dataList[$category ?? 'Ohne Kategorie'] = array();
            }

            $dataList[$category][] = $skill['Skill'];
        }

        foreach ($dataList as $category => $skills) {
            $sectionList = array();
            if ($category) {
                $section = new Section();
                $section->addElementColumn((new Element())
                    ->setContent($category)
                    ->styleTextBold()
                    ->stylePaddingLeft('5px')
                    ->styleBorderBottom()
                );
                $sectionList[] = $section;
            }

            foreach ($skills as $skill) {
                $sectionSkill = new Section();
                $sectionSkill
                    ->addElementColumn((new Element())
                        ->setContent($skill)
                        ->stylePaddingLeft('5px')
                        ->styleBorderBottom()
                        ->styleTextSize('12px')
                    , '80%');

                $value = rand(20, 96);
                $elementLeft = (new Element())
                    ->setContent($value . '%')
                    ->styleTextSize('12px')
                    ->stylePaddingLeft('5px')
                    ->styleBackgroundColor('lightblue')
                    ->styleBorderLeft()
                    ->styleBorderBottom();
                $elementRight = (new Element())
                    ->setContent('&nbsp;')
                    ->styleTextSize('12px')
                    ->styleBorderLeft()
                    ->styleBorderBottom();

                if (strlen($skill) > 100) {
                    $elementLeft->styleHeight('29.3px');
                    $elementRight->styleHeight('29.3px');
                }
                $slicePercent = new Slice();
                $slicePercent
                    ->addSection((new Section())
                        ->addElementColumn($elementLeft, $value . '%')
                        ->addElementColumn($elementRight)
                    );

                $sectionSkill->addSliceColumn($slicePercent);

                $sectionList[] = $sectionSkill;
            }

            $slice->addSectionList($sectionList);
        }


        return $slice;
    }

//    private function getHeaderSubject(string $subject)
//    {
//        $slice = new Slice();
//        $slice->addSection((new Section())
//            ->addElementColumn((new Element())
//                ->setContent($subject)
//                ->styleTextBold()
//            , '70%')
//            ->addSliceColumn($this->getSliceScoreHeader())
//        );
//
//        return $slice
//            ->styleBorderTop()
//            ->styleBorderLeft()
//            ->styleBorderRight()
//            ;
//    }
//
//    private function getSliceScoreHeader()
//    {
//        $slice = new Slice();
//        $scoreTypes = Grade::useFrontend()->getSkillScoreType();
//        foreach ($scoreTypes as $type) {
//
//        }
//
//        return $slice;
//    }
}
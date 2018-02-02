<?php

namespace SPHERE\Application\Api\Education\Certificate\Generator\Repository\EZSH;

use SPHERE\Application\Api\Education\Certificate\Generator\Certificate;
use SPHERE\Application\Education\Certificate\Generator\Generator;
use SPHERE\Application\Education\Certificate\Generator\Repository\Element;
use SPHERE\Application\Education\Certificate\Generator\Repository\Section;
use SPHERE\Application\Education\Certificate\Generator\Repository\Slice;
use SPHERE\Application\People\Meta\Student\Service\Entity\TblStudentSubject;
use SPHERE\Application\People\Meta\Student\Student;
use SPHERE\Application\People\Person\Person;
use SPHERE\Application\Setting\Consumer\Consumer;

/**
 * Class EzshStyle
 * @package SPHERE\Application\Api\Education\Certificate\Generator\Repository\EZSH
 */
abstract class EzshStyle extends Certificate
{

    const TEXT_SIZE = '10pt';
    const FONT_FAMILY = 'Calluna Sans Regular';

    /**
     * @return Slice
     */
    public function getEZSHSample()
    {

        if ($this->isSample()) {
            $Header = (new Slice)->addSection((new Section())
                ->addElementColumn((new Element\Sample())
                    ->styleTextSize('30px')
                    ->stylePaddingTop('20px')
                    ->styleHeight('110px')
                )
            );

        } else {
            $Header = (new Slice)->addSection((new Section())
                ->addElementColumn((new Element())
                    ->setContent('&nbsp;')
                    ->styleHeight('110px')
                )
            );
        }
        return $Header;
    }

    /**
     * @param string $ContentHead
     * @param string $ContentSchool
     *
     * @return Section[]
     */
    public function getEZSHHeadLine($ContentHead = '', $ContentSchool = '')
    {

        $SectionList[] = (new Section())->addElementColumn((new Element())
            ->setContent($ContentHead)
            ->styleTextSize('21pt')
            ->styleTextBold()
            ->styleFontFamily(self::FONT_FAMILY)
        );
        $SectionList[] = (new Section())->addElementColumn((new Element())
            ->setContent($ContentSchool)
            ->styleTextSize('12pt')
            ->styleTextBold()
            ->stylePaddingTop()
            ->styleFontFamily(self::FONT_FAMILY)
        );

        return $SectionList;
    }

    /**
     * @param $personId
     *
     * @return Section
     */
    public function getEZSHName($personId)
    {

        $Section = new Section();
        $Section->addElementColumn((new Element())
            ->setContent('Name')
            ->styleFontFamily(self::FONT_FAMILY)
            , '15%')
            ->addElementColumn((new Element())
                ->setContent('{{ Content.P'.$personId.'.Person.Data.Name.First }}
                              {{ Content.P'.$personId.'.Person.Data.Name.Last }}')
                ->styleBorderBottom('1px', '#BBB')
                ->stylePaddingLeft('7px')
                ->styleFontFamily(self::FONT_FAMILY)
                , '66%')
            ->addElementColumn((new Element())
                , '19%');
        return $Section;
    }

    /**
     * @param int    $personId
     * @param string $YearString
     *
     * @return Section
     */
    public function getEZSHDivisionAndYear($personId, $YearString = 'Schuljahr')
    {

        $Section = (new Section());
        $Section
            ->addElementColumn((new Element())
                ->setContent($YearString)
                ->styleFontFamily(self::FONT_FAMILY)
                , '15%')
            ->addElementColumn((new Element())
                ->setContent('{{ Content.P'.$personId.'.Division.Data.Year }}')
                ->styleBorderBottom('1px', '#BBB')
                ->styleAlignCenter()
                ->styleFontFamily(self::FONT_FAMILY)
                , '12%')
            ->addElementColumn((new Element())
                , '25%')
            ->addElementColumn((new Element())
                ->setContent('Klasse')
                ->styleFontFamily(self::FONT_FAMILY)
                , '8%')
            ->addElementColumn((new Element())
                ->setContent('{{ Content.P'.$personId.'.Division.Data.Level.Name }}')
                ->styleBorderBottom('1px', '#BBB')
                ->styleAlignCenter()
                ->styleFontFamily(self::FONT_FAMILY)
                , '21%')
            ->addElementColumn((new Element())
                , '19%');

        return $Section;
    }

    /**
     * @param $personId
     *
     * @return Section
     */
    public function getEZSHExtendedName($personId)
    {

        $Section = new Section();
        $Section->addElementColumn((new Element())
            ->setContent('Beiblatt zum Zeugnis für:')
            ->styleFontFamily(self::FONT_FAMILY)
            , '24%')
            ->addElementColumn((new Element())
                ->setContent('{{ Content.P'.$personId.'.Person.Data.Name.First }}
                              {{ Content.P'.$personId.'.Person.Data.Name.Last }}')
                ->styleAlignCenter()
                ->styleBorderBottom()
                ->stylePaddingRight('120px')
                ->stylePaddingLeft('7px')
                ->styleFontFamily(self::FONT_FAMILY)
                , '76%');
        return $Section;
    }

    /**
     * @param $personId
     *
     * @return Section
     */
    public function getEZSHNameExtraPaper($personId)
    {

        $Section = new Section();
        $Section->addElementColumn((new Element())
            ->setContent('Beiblatt zum Zeugnis für:')
            ->styleFontFamily(self::FONT_FAMILY)
            , '21%')
            ->addElementColumn((new Element())
                ->setContent('{{ Content.P'.$personId.'.Person.Data.Name.First }}
                              {{ Content.P'.$personId.'.Person.Data.Name.Last }}')
                ->styleBorderBottom()
                ->styleAlignCenter()
                ->styleFontFamily(self::FONT_FAMILY)
                , '79%');
        return $Section;
    }

    /**
     * @param $personId
     *
     * @return Slice
     */
    protected function getEZSHHeadGrade($personId)
    {

        $GradeSlice = (new Slice());


        $tblCertificateGradeAll = Generator::useService()->getCertificateGradeAll($this->getCertificateEntity());
        $GradeStructure = array();
        if (!empty($tblCertificateGradeAll)) {
            foreach ($tblCertificateGradeAll as $tblCertificateGrade) {
                $tblGradeType = $tblCertificateGrade->getServiceTblGradeType();

                $GradeStructure[$tblCertificateGrade->getRanking()][$tblCertificateGrade->getLane()]['GradeAcronym']
                    = $tblGradeType->getCode();
                $GradeStructure[$tblCertificateGrade->getRanking()][$tblCertificateGrade->getLane()]['GradeName']
                    = $tblGradeType->getName();
            }
        }

        // Shrink Lanes
        $LaneCounter = array(1 => 0, 2 => 0);
        $GradeLayout = array();
        if ($GradeStructure) {
            ksort($GradeStructure);
            foreach ($GradeStructure as $GradeList) {
                ksort($GradeList);
                foreach ($GradeList as $Lane => $Grade) {
                    $GradeLayout[$LaneCounter[$Lane]][$Lane] = $Grade;
                    $LaneCounter[$Lane]++;
                }
            }
            $GradeStructure = $GradeLayout;

            foreach ($GradeStructure as $GradeList) {
                // Sort Lane-Ranking (1,2...)
                ksort($GradeList);

                $GradeSection = (new Section());

                if (count($GradeList) == 1 && isset($GradeList[2])) {
                    $GradeSection->addElementColumn((new Element()), 'auto');
                }

                foreach ($GradeList as $Lane => $Grade) {

                    if ($Lane > 1) {
                        $GradeSection->addElementColumn((new Element())
                            , '4%');
                    }
                    $GradeSection->addElementColumn((new Element())
                        ->setContent($Grade['GradeName'])
                        ->stylePaddingTop()
                        ->styleMarginTop('10px')
                        ->styleFontFamily(self::FONT_FAMILY)
                        , '39%');
                    $GradeSection->addElementColumn((new Element())
                        ->setContent('{% if(Content.P'.$personId.'.Input["'.$Grade['GradeAcronym'].'"] is not empty) %}
                                         {{ Content.P'.$personId.'.Input["'.$Grade['GradeAcronym'].'"] }}
                                     {% else %}
                                         &ndash;
                                     {% endif %}')
                        ->styleAlignCenter()
                        ->styleBackgroundColor('#BBB')
                        ->stylePaddingTop()
                        ->stylePaddingBottom()
                        ->styleMarginTop('10px')
                        ->styleFontFamily(self::FONT_FAMILY)
                        , '9%');
                }

                if (count($GradeList) == 1 && isset($GradeList[1])) {
                    $GradeSection->addElementColumn((new Element()), '52%');
                }

                $GradeSlice->addSection($GradeSection);
            }
        }

        return $GradeSlice;
    }

    /**
     * @param $personId
     * @param bool|true $isSlice
     * @param array $languagesWithStartLevel
     * @param bool $isTitle
     * @param bool $showThirdForeignLanguage
     *
     * @return Section[]|Slice
     */
    protected function getEZSHSubjectLanes(
        $personId,
        $isSlice = true,
        $languagesWithStartLevel = array(),
        $isTitle = true,
        $showThirdForeignLanguage = false
    ) {

        $tblPerson = Person::useService()->getPersonById($personId);

        $SubjectSlice = (new Slice());

        $tblCertificateSubjectAll = Generator::useService()->getCertificateSubjectAll($this->getCertificateEntity());
        $tblGradeList = $this->getGrade();

        $SectionList = array();

        if (!empty($tblCertificateSubjectAll)) {
            $SubjectStructure = array();
            foreach ($tblCertificateSubjectAll as $tblCertificateSubject) {
                $tblSubject = $tblCertificateSubject->getServiceTblSubject();
                if ($tblSubject) {
                    // Grade Exists? => Add Subject to Certificate
                    if (isset($tblGradeList['Data'][$tblSubject->getAcronym()])) {
                        $SubjectStructure[$tblCertificateSubject->getRanking()][$tblCertificateSubject->getLane()]['SubjectAcronym']
                            = $tblSubject->getAcronym();
                        $SubjectStructure[$tblCertificateSubject->getRanking()][$tblCertificateSubject->getLane()]['SubjectName']
                            = $tblSubject->getName();
                    } else {
                        // Grade Missing, But Subject Essential => Add Subject to Certificate
                        if ($tblCertificateSubject->isEssential()) {
                            $SubjectStructure[$tblCertificateSubject->getRanking()][$tblCertificateSubject->getLane()]['SubjectAcronym']
                                = $tblSubject->getAcronym();
                            $SubjectStructure[$tblCertificateSubject->getRanking()][$tblCertificateSubject->getLane()]['SubjectName']
                                = $tblSubject->getName();
                        }
                    }
                }
            }

            // add SecondLanguageField, Fach wird aus der Schüleraktte des Schülers ermittelt
            $tblSecondForeignLanguage = false;
            $tblThirdForeignLanguage = false;
            if (!empty($languagesWithStartLevel)) {
                if (isset($languagesWithStartLevel['Lane']) && isset($languagesWithStartLevel['Rank'])) {
                    $SubjectStructure[$languagesWithStartLevel['Rank']]
                    [$languagesWithStartLevel['Lane']]['SubjectAcronym'] = 'Empty';
                    $SubjectStructure[$languagesWithStartLevel['Rank']]
                    [$languagesWithStartLevel['Lane']]['SubjectName'] = '&nbsp;';
                    if ($tblPerson
                        && ($tblStudent = Student::useService()->getStudentByPerson($tblPerson))
                    ) {
                        if (($tblStudentSubjectType = Student::useService()->getStudentSubjectTypeByIdentifier('FOREIGN_LANGUAGE'))
                            && ($tblStudentSubjectList = Student::useService()->getStudentSubjectAllByStudentAndSubjectType($tblStudent,
                                $tblStudentSubjectType))
                        ) {

                            /** @var TblStudentSubject $tblStudentSubject */
                            foreach ($tblStudentSubjectList as $tblStudentSubject) {
                                if ($tblStudentSubject->getTblStudentSubjectRanking()
                                    && ($tblSubjectForeignLanguage = $tblStudentSubject->getServiceTblSubject())
                                ) {
                                    if ($tblStudentSubject->getTblStudentSubjectRanking()->getIdentifier() == '2') {
                                        $tblSecondForeignLanguage = $tblSubjectForeignLanguage;
                                    } elseif ($tblStudentSubject->getTblStudentSubjectRanking()->getIdentifier() == '3') {
                                        $tblThirdForeignLanguage = $tblSubjectForeignLanguage;
                                    }
                                }
                            }

                            if ($tblThirdForeignLanguage && $showThirdForeignLanguage) {
                                $tblSecondForeignLanguage = $tblThirdForeignLanguage;
                            }
                            if ($tblSecondForeignLanguage) {
                                $SubjectStructure[$languagesWithStartLevel['Rank']]
                                [$languagesWithStartLevel['Lane']]['SubjectAcronym'] = $tblSecondForeignLanguage->getAcronym();
                                $SubjectStructure[$languagesWithStartLevel['Rank']]
                                [$languagesWithStartLevel['Lane']]['SubjectName'] = $tblSecondForeignLanguage->getName();
                            }
                        }
                    }
                }
            }

            // Shrink Lanes
            $LaneCounter = array(1 => 0, 2 => 0);
            $SubjectLayout = array();
            ksort($SubjectStructure);
            foreach ($SubjectStructure as $SubjectList) {
                ksort($SubjectList);
                foreach ($SubjectList as $Lane => $Subject) {
                    $SubjectLayout[$LaneCounter[$Lane]][$Lane] = $Subject;
                    $LaneCounter[$Lane]++;
                }
            }
            $SubjectStructure = $SubjectLayout;

            // headline for grades
            $HeaderSection = (new Section());
            $HeaderSection->addElementColumn((new Element())
                ->setContent('LEISTUNGEN in den einzelnen Fächern')
                ->styleTextSize('10pt')
                ->styleTextBold()
                ->stylePaddingTop('10px')
                ->stylePaddingBottom(($isTitle ? '0px': '26px'))
                ->styleFontFamily(self::FONT_FAMILY)
            );
            $SectionList[] = $HeaderSection;
            $SubjectSlice->addSection($HeaderSection);
            if($isTitle){
                $HeaderSectionTwo = (new Section());
                $HeaderSectionTwo->addElementColumn((new Element())
                    ->setContent('Pflichtbereich:')
                    ->styleTextSize('10pt')
                    ->styleTextBold()
                    ->stylePaddingTop('10px')
                    ->styleFontFamily(self::FONT_FAMILY)
                );
                $SectionList[] = $HeaderSectionTwo;
                $SubjectSlice->addSection($HeaderSectionTwo);
            }

            $hasAdditionalLine = false;
            $isShrinkMarginTop = false;

            $subjectWidth = 30;
            $gradeWidth = 17;
            $TextSize = '14px';
            $TextSizeSmall = '8.5px';
            $paddingTopShrinking = '7.5px';
            $paddingBottomShrinking = '7px';

            $count = 0;
            foreach ($SubjectStructure as $SubjectList) {
                $count++;
                // Sort Lane-Ranking (1,2...)
                ksort($SubjectList);

                $SubjectSection = (new Section());

                if (count($SubjectList) == 1 && isset($SubjectList[2])) {
                    $SubjectSection->addElementColumn((new Element()), 'auto');
                }

                foreach ($SubjectList as $Lane => $Subject) {
                    // 2. Fremdsprache ab Klassenstufe
                    if (isset($languagesWithStartLevel['Lane']) && isset($languagesWithStartLevel['Rank'])
                        && $languagesWithStartLevel['Lane'] == $Lane && $languagesWithStartLevel['Rank'] == $count
                    ) {
                        $hasAdditionalLine['Lane'] = $Lane;
                        $hasAdditionalLine['Ranking'] = 2;
                        $hasAdditionalLine['SubjectAcronym'] = $tblSecondForeignLanguage
                            ? $tblSecondForeignLanguage->getAcronym() : 'Empty';
                    }

                    if ($Lane > 1) {
                        $SubjectSection->addElementColumn((new Element())
                            , '6%');
                    }
                    if ($hasAdditionalLine && $Lane == $hasAdditionalLine['Lane']) {
                        $SubjectSection->addElementColumn((new Element())
                            ->setContent($Subject['SubjectName'])
                            ->stylePaddingTop()
                            ->stylePaddingBottom('0px')
                            ->styleMarginBottom('0px')
                            ->styleBorderBottom('1px', '#BBB')
                            ->styleMarginTop('10px')
                            ->styleTextSize($TextSize)
                            ->styleFontFamily(self::FONT_FAMILY)
                            , (string)($subjectWidth - 2) . '%');
                        $SubjectSection->addElementColumn((new Element()), '2%');
                    } elseif (strlen($Subject['SubjectName']) > 27) {
                        $SubjectSection->addElementColumn((new Element())
                            ->setContent($Subject['SubjectName'])
                            ->stylePaddingTop()
                            ->styleMarginTop('5px')
                            ->styleTextSize($TextSize)
                            ->styleFontFamily(self::FONT_FAMILY)
                            , (string)$subjectWidth . '%');
                    }elseif ($isShrinkMarginTop) {
                        $SubjectSection->addElementColumn((new Element())
                            ->setContent($Subject['SubjectName'])
                            ->stylePaddingTop()
                            ->styleMarginTop('0px')
                            ->styleTextSize($TextSize)
                            ->styleFontFamily(self::FONT_FAMILY)
                            , (string)$subjectWidth . '%');
                    } else {
                        $SubjectSection->addElementColumn((new Element())
                            ->setContent($Subject['SubjectName'])
                            ->stylePaddingTop()
                            ->styleMarginTop('10px')
                            ->styleTextSize($TextSize)
                            ->styleFontFamily(self::FONT_FAMILY)
                            , (string)$subjectWidth . '%');
                    }

                    $SubjectSection->addElementColumn((new Element())
                        ->setContent('{% if(Content.P' . $personId . '.Grade.Data["' . $Subject['SubjectAcronym'] . '"] is not empty) %}
                                             {{ Content.P' . $personId . '.Grade.Data["' . $Subject['SubjectAcronym'] . '"] }}
                                         {% else %}
                                             &ndash;
                                         {% endif %}')
                        ->styleAlignCenter()
                        ->styleBackgroundColor('#E6E6E6')
                        ->styleMarginTop('12px')
                        ->stylePaddingTop(
                            '{% if((Content.P' . $personId . '.Grade.Data.IsShrinkSize["' . $Subject['SubjectAcronym'] . '"] is not empty)
                                and (Content.P' . $personId . '.Grade.Data["' . $Subject['SubjectAcronym'] . '"] is not empty)
                            ) %}
                                ' . $paddingTopShrinking . ' 
                             {% else %}
                                 4px
                             {% endif %}'
                        )
                        ->stylePaddingBottom(
                            '{% if((Content.P' . $personId . '.Grade.Data.IsShrinkSize["' . $Subject['SubjectAcronym'] . '"] is not empty)
                                and (Content.P' . $personId . '.Grade.Data["' . $Subject['SubjectAcronym'] . '"] is not empty)
                            ) %}
                               ' . $paddingBottomShrinking . ' 
                             {% else %}
                                 4px
                             {% endif %}'
                        )
                        ->styleMarginTop($isShrinkMarginTop ? '0px' : '10px')
                        ->styleTextSize(
                            '{% if((Content.P' . $personId . '.Grade.Data.IsShrinkSize["' . $Subject['SubjectAcronym'] . '"] is not empty)
                                and (Content.P' . $personId . '.Grade.Data["' . $Subject['SubjectAcronym'] . '"] is not empty)
                            ) %}
                                 ' . $TextSizeSmall . '
                             {% else %}
                                 ' . $TextSize . '
                             {% endif %}'
                        )
                        ->styleFontFamily(self::FONT_FAMILY)
                        , (string)$gradeWidth . '%');

                    if ($isShrinkMarginTop && $Lane == 2) {
                        $isShrinkMarginTop = false;
                    }
                }

                if (count($SubjectList) == 1 && isset($SubjectList[1])) {
                    $SubjectSection->addElementColumn((new Element()), '53%');
                    $isShrinkMarginTop = false;
                }

                $SubjectSlice->addSection($SubjectSection);
                $SectionList[] = $SubjectSection;

                if ($hasAdditionalLine) {
                    $SubjectSection = (new Section());

                    if ($hasAdditionalLine['Lane'] == 2) {
                        $SubjectSection->addElementColumn((new Element()), '52%');
                    }
                    $SubjectSection->addElementColumn((new Element())
                        ->setContent($hasAdditionalLine['Ranking'] . '. Fremdsprache (ab Klassenstufe ' .
                            '{% if(Content.P' . $personId . '.Subject.Level["' . $hasAdditionalLine['SubjectAcronym'] . '"] is not empty) %}
                                     {{ Content.P' . $personId . '.Subject.Level["' . $hasAdditionalLine['SubjectAcronym'] . '"] }})
                                 {% else %}
                                    &nbsp;)
                                 {% endif %}'
                            )
                        ->stylePaddingTop('0px')
                        ->stylePaddingBottom('0px')
                        ->styleMarginTop('-6px')
                        ->styleMarginBottom('0px')
                        ->styleTextSize('9px')
                        ->styleFontFamily(self::FONT_FAMILY)
                        , (string)$subjectWidth . '%');

                    if ($hasAdditionalLine['Lane'] == 1) {
                        $SubjectSection->addElementColumn((new Element()), '53%');
                    }

                    $hasAdditionalLine = false;

                    // es wird abstand gelassen, einkommentieren für keinen extra Abstand der nächsten Zeile
//                    $isShrinkMarginTop = true;

                    $SubjectSlice->addSection($SubjectSection);
                    $SectionList[] = $SubjectSection;
                }

            }
        }

        if ($isSlice) {
            return $SubjectSlice;
        } else {
            return $SectionList;
        }
    }

    /**
     * @param $personId
     * @param string $TextSize
     * @param bool $isProfile
     *
     * @return Slice
     */
    public function getEZSHObligation($personId, $TextSize = '14px', $isProfile = false)
    {

        $tblPerson = Person::useService()->getPersonById($personId);

        $marginTop = '3px';

        $slice = new Slice();
        $sectionList = array();

        $elementObligationName = false;
        $elementObligationGrade = false;
        $elementForeignLanguageName = false;
        $elementForeignLanguageGrade = false;

        $subjectWidth = 83;
        $gradeWidth = 17;
        $paddingTopShrinking = '6px';
        $paddingBottomShrinking = '6px';

        // Zeugnisnoten im Wortlaut auf Abschlusszeugnissen --> breiter Zensurenfelder
        if (($tblCertificate = $this->getCertificateEntity())
            && ($tblCertificateType = $tblCertificate->getTblCertificateType())
            && ($tblCertificateType->getIdentifier() == 'DIPLOMA')
            && ($tblSetting = \SPHERE\Application\Setting\Consumer\Consumer::useService()->getSetting(
                'Education', 'Certificate', 'Prepare', 'IsGradeVerbalOnDiploma'))
            && $tblSetting->getValue()
        ) {
            $TextSizeSmall = '13px';
        } else {
            $TextSizeSmall = '11px';
        }

        if ($tblPerson
            && ($tblStudent = Student::useService()->getStudentByPerson($tblPerson))
        ) {
            $Level = 'false';
            if ($isProfile) {
                // Profil
                if (($tblStudentSubjectType = Student::useService()->getStudentSubjectTypeByIdentifier('PROFILE'))
                    && ($tblStudentSubjectList = Student::useService()->getStudentSubjectAllByStudentAndSubjectType($tblStudent,
                        $tblStudentSubjectType))
                ) {
                    /** @var TblStudentSubject $tblStudentSubject */
                    $tblStudentSubject = current($tblStudentSubjectList);
                    if (($tblSubjectProfile = $tblStudentSubject->getServiceTblSubject())) {
                        $tblSubject = $tblSubjectProfile;

                        if (($tblSetting = Consumer::useService()->getSetting('Api', 'Education', 'Certificate', 'ProfileAcronym'))
                            && ($value = $tblSetting->getValue())
                        ) {
                            $subjectAcronymForGrade = $value;
                        } else {
                            $subjectAcronymForGrade = $tblSubject->getAcronym();
                        }

                        $elementObligationName = new Element();
                        $elementObligationName
                            ->setContent('
                            {% if(Content.P' . $personId . '.Student.ProfileEZSH["' . $tblSubject->getAcronym() . '"] is not empty) %}
                                 {{ Content.P' . $personId . '.Student.ProfileEZSH["' . $tblSubject->getAcronym() . '"].Name' . ' }}
                            {% else %}
                                 &nbsp;
                            {% endif %}')
                            ->stylePaddingTop('0px')
                            ->stylePaddingBottom('0px')
                            ->styleMarginTop('7px')
                            ->styleTextSize($TextSize)
                            ->styleFontFamily(self::FONT_FAMILY);

                        $elementObligationGrade = new Element();
                        $elementObligationGrade
                            ->setContent('
                            {% if(Content.P' . $personId . '.Grade.Data["' . $subjectAcronymForGrade . '"] is not empty) %}
                                {{ Content.P' . $personId . '.Grade.Data["' . $subjectAcronymForGrade . '"] }}
                            {% else %}
                                &ndash;
                            {% endif %}')
                            ->styleAlignCenter()
                            ->styleBackgroundColor('#E6E6E6')
                            ->stylePaddingTop(
                                '{% if(Content.P' . $personId . '.Grade.Data.IsShrinkSize["' . $subjectAcronymForGrade . '"] is not empty) %}
                                 ' . $paddingTopShrinking . ' 
                             {% else %}
                                 4px
                             {% endif %}'
                            )
                            ->stylePaddingBottom(
                                '{% if(Content.P' . $personId . '.Grade.Data.IsShrinkSize["' . $subjectAcronymForGrade . '"] is not empty) %}
                                  ' . $paddingBottomShrinking . ' 
                             {% else %}
                                 4px
                             {% endif %}'
                            )
                            ->styleTextSize(
                                '{% if(Content.P' . $personId . '.Grade.Data.IsShrinkSize["' . $subjectAcronymForGrade . '"] is not empty) %}
                                 ' . $TextSizeSmall . '
                             {% else %}
                                 ' . $TextSize . '
                             {% endif %}'
                            )
                            ->styleMarginTop($marginTop)
                            ->styleFontFamily(self::FONT_FAMILY);
                    }
                }
            } else {
                // Neigungskurs
                if (($tblStudentSubjectType = Student::useService()->getStudentSubjectTypeByIdentifier('ORIENTATION'))
                    && ($tblSubjectList = Student::useService()->getStudentSubjectAllByStudentAndSubjectType($tblStudent,
                        $tblStudentSubjectType))
                ) {
                    /** @var TblStudentSubject $tblStudentSubject */
                    $tblStudentSubject = current($tblSubjectList);
                    if (($tblSubject = $tblStudentSubject->getServiceTblSubject())) {

                        if (($tblSetting = Consumer::useService()->getSetting('Api', 'Education', 'Certificate',
                                'OrientationAcronym'))
                            && ($value = $tblSetting->getValue())
                        ) {
                            $subjectAcronymForGrade = $value;
                        } else {
                            $subjectAcronymForGrade = $tblSubject->getAcronym();
                        }

                        $elementObligationName = new Element();
                        $elementObligationName
                            ->setContent('
                            {% if(Content.P' . $personId . '.Student.Orientation["' . $tblSubject->getAcronym() . '"] is not empty) %}
                                 {{ Content.P' . $personId . '.Student.Orientation["' . $tblSubject->getAcronym() . '"].Name' . ' }}
                            {% else %}
                                 &nbsp;
                            {% endif %}')
                            ->stylePaddingTop('0px')
                            ->stylePaddingBottom('0px')
                            ->styleMarginTop('7px')
                            ->styleTextSize($TextSize)
                            ->styleFontFamily(self::FONT_FAMILY);

                        $elementObligationGrade = new Element();
                        $elementObligationGrade
                            ->setContent('
                            {% if(Content.P' . $personId . '.Grade.Data["' . $subjectAcronymForGrade . '"] is not empty) %}
                                {{ Content.P' . $personId . '.Grade.Data["' . $subjectAcronymForGrade . '"] }}
                            {% else %}
                                &ndash;
                            {% endif %}')
                            ->styleAlignCenter()
                            ->styleBackgroundColor('#E6E6E6')
                            ->stylePaddingTop(
                                '{% if(Content.P' . $personId . '.Grade.Data.IsShrinkSize["' . $subjectAcronymForGrade . '"] is not empty) %}
                                 ' . $paddingTopShrinking . ' 
                             {% else %}
                                 4px
                             {% endif %}'
                            )
                            ->stylePaddingBottom(
                                '{% if(Content.P' . $personId . '.Grade.Data.IsShrinkSize["' . $subjectAcronymForGrade . '"] is not empty) %}
                                  ' . $paddingBottomShrinking . ' 
                             {% else %}
                                 4px
                             {% endif %}'
                            )
                            ->styleTextSize(
                                '{% if(Content.P' . $personId . '.Grade.Data.IsShrinkSize["' . $subjectAcronymForGrade . '"] is not empty) %}
                                 ' . $TextSizeSmall . '
                             {% else %}
                                 ' . $TextSize . '
                             {% endif %}'
                            )
                            ->styleMarginTop($marginTop)
                            ->styleFontFamily(self::FONT_FAMILY);
                    }
                }

                // 2. Fremdsprache
                if (($tblStudentSubjectType = Student::useService()->getStudentSubjectTypeByIdentifier('FOREIGN_LANGUAGE'))
                    && ($tblStudentSubjectList = Student::useService()->getStudentSubjectAllByStudentAndSubjectType($tblStudent,
                        $tblStudentSubjectType))
                ) {
                    /** @var TblStudentSubject $tblStudentSubject */
                    foreach ($tblStudentSubjectList as $tblStudentSubject) {
                        if ($tblStudentSubject->getTblStudentSubjectRanking()
                            && $tblStudentSubject->getTblStudentSubjectRanking()->getIdentifier() == '2'
                            && ($tblSubject = $tblStudentSubject->getServiceTblSubject())
                        ) {

                            if (($tblLevelFrom = $tblStudentSubject->getServiceTblLevelFrom())) {
                                $Level = $tblLevelFrom->getName();
                                if (!$Level) {
                                    $Level = 'false';
                                }
                            }

                            $elementForeignLanguageName = new Element();
                            $elementForeignLanguageName
                                ->setContent('
                            {% if(Content.P' . $personId . '.Student.ForeignLanguage["' . $tblSubject->getAcronym() . '"] is not empty) %}
                                 {{ Content.P' . $personId . '.Student.ForeignLanguage["' . $tblSubject->getAcronym() . '"].Name' . ' }}
                            {% else %}
                                 &nbsp;
                            {% endif %}')
                                ->stylePaddingTop('0px')
                                ->stylePaddingBottom('0px')
                                ->styleMarginTop('7px')
                                ->styleTextSize($TextSize)
                                ->styleFontFamily(self::FONT_FAMILY);

                            $elementForeignLanguageGrade = new Element();
                            $elementForeignLanguageGrade
                                ->setContent('
                            {% if(Content.P' . $personId . '.Grade.Data["' . $tblSubject->getAcronym() . '"] is not empty) %}
                                {{ Content.P' . $personId . '.Grade.Data["' . $tblSubject->getAcronym() . '"] }}
                            {% else %}
                                &ndash;
                            {% endif %}')
                                ->styleAlignCenter()
                                ->styleBackgroundColor('#E6E6E6')
                                ->stylePaddingTop(
                                    '{% if(Content.P' . $personId . '.Grade.Data.IsShrinkSize["' . $tblSubject->getAcronym() . '"] is not empty) %}
                                 ' . $paddingTopShrinking . ' 
                             {% else %}
                                 4px
                             {% endif %}'
                                )
                                ->stylePaddingBottom(
                                    '{% if(Content.P' . $personId . '.Grade.Data.IsShrinkSize["' . $tblSubject->getAcronym() . '"] is not empty) %}
                                  ' . $paddingBottomShrinking . ' 
                             {% else %}
                                 4px
                             {% endif %}'
                                )
                                ->styleTextSize(
                                    '{% if(Content.P' . $personId . '.Grade.Data.IsShrinkSize["' . $tblSubject->getAcronym() . '"] is not empty) %}
                                 ' . $TextSizeSmall . '
                             {% else %}
                                 ' . $TextSize . '
                             {% endif %}'
                                )
                                ->styleMarginTop($marginTop)
                                ->styleFontFamily(self::FONT_FAMILY);
                        }
                    }
                }
            }

            // aktuell immer anzeigen
//            if ($elementOrientationName || $elementForeignLanguageName) {
            $section = new Section();
            if($isProfile){
                $section
                    ->addElementColumn((new Element())
                        ->setContent('Wahlpflichtbereich mit informatorischer Bildung ab Klasse 8:')
                        ->styleTextBold()
                        ->styleMarginTop('10px')
                        ->styleTextSize($TextSize)
                        ->styleFontFamily(self::FONT_FAMILY)
                    );
            } else {
                $section
                    ->addElementColumn((new Element())
                        ->setContent('Wahlpflichtbereich:')
                        ->styleTextBold()
                        ->styleMarginTop('10px')
                        ->styleTextSize($TextSize)
                        ->styleFontFamily(self::FONT_FAMILY)
                    );
            }

            $sectionList[] = $section;
//            }

            if ($elementObligationName) {
                $section = new Section();
                $section
                    ->addElementColumn($elementObligationName, (string)$subjectWidth.'%')
                    ->addElementColumn($elementObligationGrade, (string)$gradeWidth.'%');
                $sectionList[] = $section;

                $section = new Section();
                if($isProfile) {
                    $section
                        ->addElementColumn((new Element())
                            ->styleBorderTop('1px', '#BBB')
                            ->stylePaddingTop()
                            ->stylePaddingBottom('10px')
                            ->styleFontFamily(self::FONT_FAMILY)
                            , (string)($subjectWidth - 2) . '%')
                        ->addElementColumn((new Element()), (string)($gradeWidth + 2) . '%');
                    $sectionList[] = $section;
                } else {
                    $section
                        ->addElementColumn((new Element())
                            ->setContent('2. Fremdsprache ab Klasse 6 / <u>Neigungskurs ab Klasse 7</u>')
                            ->styleBorderTop('1px', '#BBB')
                            ->styleMarginTop('0px')
                            ->stylePaddingTop()
                            ->styleTextSize('13px')
                            ->styleFontFamily(self::FONT_FAMILY)
                            , (string)($subjectWidth - 2) . '%')
                        ->addElementColumn((new Element()), (string)($gradeWidth + 2) . '%');
                    $sectionList[] = $section;
                }
            } elseif ($elementForeignLanguageName) {
                $section = new Section();
                $section
                    ->addElementColumn($elementForeignLanguageName, (string)$subjectWidth.'%')
                    ->addElementColumn($elementForeignLanguageGrade, (string)$gradeWidth.'%');
                $sectionList[] = $section;


                $section = new Section();
                //Wahlfach Fremdsprache finden
                if($isProfile) {
                    $section
                        ->addElementColumn((new Element())
                            ->styleBorderTop('1px', '#BBB')
                            ->stylePaddingTop()
                            ->stylePaddingBottom('10px')
                            ->styleFontFamily(self::FONT_FAMILY)
                            , (string)($subjectWidth - 2) . '%')
                        ->addElementColumn((new Element()), (string)($gradeWidth + 2) . '%');
                    $sectionList[] = $section;
                } else {
                    $section
                        ->addElementColumn((new Element())
                            ->setContent(' <u>2. Fremdsprache ab Klasse 
                        {% if ' . $Level . ' == false %}
                            6
                        {% else %}
                            ' . $Level . '
                        {% endif %}
                        </u> / Neigungskurs ab Klasse 7')
                            ->styleBorderTop('1px', '#BBB')
                            ->stylePaddingTop()
                            ->styleTextSize('13px')
                            ->styleFontFamily(self::FONT_FAMILY)
                            , (string)($subjectWidth - 2) . '%')
                        ->addElementColumn((new Element()), (string)($gradeWidth + 2) . '%');
                    $sectionList[] = $section;
                }
            } else {
                $elementName = (new Element())
                    ->setContent('---')
                    ->styleMarginTop($marginTop)
                    ->styleTextSize($TextSize)
                    ->styleFontFamily(self::FONT_FAMILY);

                $elementGrade = (new Element())
                    ->setContent('&ndash;')
                    ->styleAlignCenter()
                    ->styleBackgroundColor('#E6E6E6')
                    ->stylePaddingTop('4px')
                    ->stylePaddingBottom('4px')
                    ->styleTextSize($TextSize)
                    ->styleMarginTop($marginTop)
                    ->styleFontFamily(self::FONT_FAMILY);

                $section = new Section();
                $section
                    ->addElementColumn($elementName
                        , '82%')
                    ->addElementColumn((new Element())
                        , '1%')
                    ->addElementColumn($elementGrade
                        , '17%');
                $sectionList[] = $section;
                $section = new Section();
                if($isProfile) {
                    $section
                        ->addElementColumn((new Element())
                            ->styleBorderTop('1px', '#BBB')
                            ->stylePaddingTop()
                            ->stylePaddingBottom('10px')
                            , '80%')
                        ->addElementColumn((new Element())
                            , '20%');
                    $sectionList[] = $section;
                } else {
                    $section
                        ->addElementColumn((new Element())
                            ->setContent('2. Fremdsprache ab Klasse'
                                . '{% if ' . $Level . ' == false %}
                            6
                        {% else %}
                            ' . $Level . '
                        {% endif %}'
                                . '/ Neigungskurs ab Klasse 7 test 2')
                            ->styleBorderTop('1px', '#BBB')
                            ->stylePaddingTop()
                            ->styleTextSize('13px')
                            ->styleFontFamily(self::FONT_FAMILY)
                            , '80%')
                        ->addElementColumn((new Element())
                            , '20%');
                    $sectionList[] = $section;
                }
            }
        } else {

            $section = new Section();
            if($isProfile){
                $section
                    ->addElementColumn((new Element())
                        ->setContent('Wahlpflichtbereich mit informatorischer Bildung ab Klasse 8:')
                        ->styleTextBold()
                        ->styleMarginTop('10px')
                        ->styleTextSize($TextSize)
                        ->styleFontFamily(self::FONT_FAMILY)
                    );
            } else {
                $section
                    ->addElementColumn((new Element())
                        ->setContent('Wahlpflichtbereich:')
                        ->styleTextBold()
                        ->styleMarginTop('10px')
                        ->styleTextSize($TextSize)
                        ->styleFontFamily(self::FONT_FAMILY)
                    );
            }
            $sectionList[] = $section;

            $elementName = (new Element())
                ->setContent('---')
                ->styleMarginTop($marginTop)
                ->styleTextSize($TextSize)
                ->styleFontFamily(self::FONT_FAMILY);

            $elementGrade = (new Element())
                ->setContent('&ndash;')
                ->styleAlignCenter()
                ->styleBackgroundColor('#E6E6E6')
                ->stylePaddingTop('4px')
                ->stylePaddingBottom('4px')
                ->styleTextSize($TextSize)
                ->styleMarginTop($marginTop)
                ->styleFontFamily(self::FONT_FAMILY);

            $section = new Section();
            $section
                ->addElementColumn($elementName
                    , '82%')
                ->addElementColumn((new Element())
                    , '2%')
                ->addElementColumn($elementGrade
                    , '17%');
            $sectionList[] = $section;
            $section = new Section();
            if($isProfile){
                $section
                    ->addElementColumn((new Element())
                        ->styleBorderTop('1px', '#BBB')
                        ->stylePaddingTop()
                        ->stylePaddingBottom('10px')
                        , '80%')
                    ->addElementColumn((new Element())
                        , '20%');
                $sectionList[] = $section;
            } else {
                $section = new Section();
                $section
                    ->addElementColumn((new Element())
                        ->setContent('2. Fremdsprache ab Klasse 6 / Neigungskurs ab Klasse 7')
                        ->styleBorderTop('1px', '#BBB')
                        ->stylePaddingTop()
                        ->styleTextSize('13px')
                        ->styleFontFamily(self::FONT_FAMILY)
                        , '80%')
                    ->addElementColumn((new Element())
                        , '20%');
                $sectionList[] = $section;
            }
        }

        return empty($sectionList) ? (new Slice())->styleHeight('60px') : $slice->addSectionList($sectionList);
    }

    /**
     * @param int $personId
     *
     * @return Section[]
     */
    public function getEZSHPerformanceGroup($personId)
    {

        $SectionList = array();
        $Section = new Section();
        $Section->addElementColumn((new Element())
            ->setContent('{% if(Content.P'.$personId.'.Input.PerformanceGroup is not empty) %}
                    {{ Content.P'.$personId.'.Input.PerformanceGroup }}
                {% else %}
                    &nbsp;
                {% endif %}')
            ->styleBorderBottom('1px', '#BBB')
            ->styleFontFamily(self::FONT_FAMILY)
            , '46%')
            ->addSliceColumn(
                $this->setCheckBox('{% if(Content.P'.$personId.'.Input.PerformanceGroup is not empty) %}
                    X
                {% else %}
                    &nbsp;
                {% endif %}')
                    ->styleMarginTop('-4px')
                , '5%'
            )
            ->addElementColumn((new Element())
                ->setContent('teilgenommen')
                ->styleFontFamily(self::FONT_FAMILY)
                , '22%'
            )
            ->addSliceColumn(
                $this->setCheckBox('{% if(Content.P'.$personId.'.Input.PerformanceGroup is not empty) %}
                    &nbsp;
                {% else %}
                    X
                {% endif %}')
                    ->styleMarginTop('-4px')
                , '5%'
            )
            ->addElementColumn((new Element())
                ->setContent('nicht teilgenommen')
                ->styleFontFamily(self::FONT_FAMILY)
                , '22%'
            );
        $SectionList[] = $Section;
        $Section = new Section();
        $Section->addElementColumn((new Element())
            ->setContent('Leistungsgruppe')
            ->styleFontFamily(self::FONT_FAMILY)
        );
        $SectionList[] = $Section;

        return $SectionList;
    }

    /**
     * @param int    $personId
     * @param string $Height
     *
     * @return Section[]
     */
    public function getEZSHRating($personId, $Height = '575px')
    {
        $SectionList = array();
        $Section = new Section();
        $Section->addElementColumn(((new Element())
            ->setContent('EINSCHÄTZUNG')
            ->styleTextSize('10pt')
            ->styleTextBold()
            ->stylePaddingBottom('4px')
            ->styleFontFamily(self::FONT_FAMILY)
        )
        );
        $SectionList[] = $Section;
        $Section = new Section();
        $Section->addElementColumn((new Element())
            ->setContent('{% if(Content.P'.$personId.'.Input.Rating is not empty) %}
                    {{ Content.P'.$personId.'.Input.Rating|nl2br }}
                {% else %}
                    ---
                {% endif %}')
            ->styleHeight($Height)
            ->styleFontFamily(self::FONT_FAMILY)
        );
        $SectionList[] = $Section;
        return $SectionList;
    }

    /**
     * @param int    $personId
     * @param string $Height
     *
     * @return Section[]
     */
    public function getEZSHRemark($personId, $Height = '170px')
    {
        $SectionList = array();
        $Section = new Section();
        $Section->addElementColumn((new Element())
            ->setContent('BEMERKUNGEN')
            ->styleTextSize('10pt')
            ->styleTextBold()
            ->stylePaddingBottom('4px')
            ->styleFontFamily(self::FONT_FAMILY)
        );
        $SectionList[] = $Section;
        $Section = new Section();
        $Section->addElementColumn((new Element())
            ->setContent('{% if(Content.P'.$personId.'.Input.RemarkWithoutTeam is not empty) %}
                    {{ Content.P'.$personId.'.Input.RemarkWithoutTeam|nl2br }}
                {% else %}
                    ---
                {% endif %}')
//            ->styleAlignJustify()
            ->styleHeight($Height)
            ->styleFontFamily(self::FONT_FAMILY)
        );
        $SectionList[] = $Section;
        return $SectionList;
    }

    /**
     * @param $personId
     *
     * @return Section[]
     */
    public function getEZSHMissing($personId)
    {

        $SectionList = array();
        $Section = new Section();
        $Section->addElementColumn((new Element())
            ->setContent('FEHLTAGE')
            ->styleTextBold()
            ->stylePaddingBottom('5px')
            ->styleFontFamily(self::FONT_FAMILY)
        );
        $SectionList[] = $Section;

        $Section = new Section();
        $Section->addElementColumn((new Element())
            ->setContent('entschuldigt')
            ->styleFontFamily(self::FONT_FAMILY)
            , '16%')
            ->addElementColumn((new Element())
                ->setContent('{% if(Content.P'.$personId.'.Input.Missing is not empty) %}
                {{ Content.P'.$personId.'.Input.Missing }}
            {% else %}
                &nbsp;
            {% endif %}')
                ->styleAlignCenter()
                ->styleBorderBottom('1px', '#BBB')
                ->styleFontFamily(self::FONT_FAMILY)
                , '18%')
            ->addElementColumn((new Element())
                , '15%')
            ->addElementColumn((new Element())
                ->setContent('unentschuldigt')
                ->styleFontFamily(self::FONT_FAMILY)
                , '18%')
            ->addElementColumn((new Element())
                ->setContent('{% if(Content.P'.$personId.'.Input.Bad.Missing is not empty) %}
                &nbsp;{{ Content.P'.$personId.'.Input.Bad.Missing }}
            {% else %}
                &nbsp;
            {% endif %}')
                ->styleAlignCenter()
                ->styleBorderBottom('1px', '#BBB')
                ->styleFontFamily(self::FONT_FAMILY)
                , '18%')
            ->addElementColumn((new Element())
                , '15%');
        $SectionList[] = $Section;
        return $SectionList;
    }

    /**
     * @param $personId
     *
     * @return Section
     */
    public function getEZSHTransfer($personId)
    {
        $Section = new Section();
        $Section->addElementColumn((new Element())
            ->setContent('Versetzungsvermerk:')
            ->styleFontFamily(self::FONT_FAMILY)
            , '21%')
            ->addElementColumn((new Element())
                ->setContent('{% if(Content.P'.$personId.'.Input.Transfer) %}
                                        {{ Content.P'.$personId.'.Input.Transfer }}
                                    {% else %}
                                          &nbsp;
                                    {% endif %}')
                ->styleBorderBottom('1px')
                ->stylePaddingLeft('7px')
                ->styleFontFamily(self::FONT_FAMILY)
                , '58%')
            ->addElementColumn((new Element())
                , '20%');
        return $Section;
    }

    /**
     * @param $personId
     *
     * @return Section[]
     */
    public function getEZSHDateSign($personId)
    {

        $SectionList = array();
        $Section = new Section();
        $Section->addElementColumn((new Element())
            ->setContent('{% if(Content.P'.$personId.'.Input.Date is not empty) %}
                    {{ Content.P'.$personId.'.Input.Date }}
                {% else %}
                    &nbsp;
                {% endif %}')
            ->styleBorderBottom('1px', '#BBB')
            ->styleAlignCenter()
            ->styleFontFamily(self::FONT_FAMILY)
            , '15%')
            ->addElementColumn((new Element())
                , '10%')
            ->addElementColumn((new Element())
                ->setContent('&nbsp;')
                ->styleBorderBottom('1px', '#BBB')
                ->styleFontFamily(self::FONT_FAMILY)
                , '30%')
            ->addElementColumn((new Element())
                , '10%')
            ->addElementColumn((new Element())
                ->setContent('&nbsp;')
                ->styleBorderBottom('1px', '#BBB')
                ->styleFontFamily(self::FONT_FAMILY)
                , '30%')
            ->addElementColumn((new Element())
                , '5%');
        $SectionList[] = $Section;
        $Section = new Section();
        $Section->addElementColumn((new Element())
            ->setContent('Datum')
            ->styleFontFamily(self::FONT_FAMILY)
            , '25%'
        );
        $Section->addElementColumn((new Element())
            ->setContent('{% if(Content.P'.$personId.'.DivisionTeacher.Description is not empty) %}
                    {{ Content.P'.$personId.'.DivisionTeacher.Description }}
                {% else %}
                    Klassenlehrer(in)
                {% endif %}')
            ->styleFontFamily(self::FONT_FAMILY)
            , '40%'
        );
        $Section->addElementColumn((new Element())
            ->setContent('Für den Schulträger')
            ->styleFontFamily(self::FONT_FAMILY)
            , '35%'
        );
        $SectionList[] = $Section;
        return $SectionList;
    }

    /**
     * @return Section[]
     */
    public function getEZSHCustody()
    {

        $SectionList = array();
        $SectionList[] = (new Section())->addElementColumn((new Element())
            ->setContent('Zur Kenntnis genommen:')
            ->styleFontFamily(self::FONT_FAMILY)
            , '30%')
            ->addElementColumn((new Element())
                ->setContent('&nbsp;')
                ->styleBorderBottom('1px', '#BBB')
                ->styleFontFamily(self::FONT_FAMILY)
                , '65%')
            ->addElementColumn((new Element())
                ->setContent('&nbsp;')
                ->styleFontFamily(self::FONT_FAMILY)
                , '5%');

        $SectionList[] = (new Section())
            ->addElementColumn((new Element())
                , '30%'
            )
            ->addElementColumn((new Element())
                ->setContent('Personensorgeberechtigte(r) ')
                ->styleFontFamily(self::FONT_FAMILY)
                , '70%'
            );

        return $SectionList;
    }

    /**
     * @param bool $isExtend
     *
     * @return Section[]
     */
    public function getEZSHGradeInfo($isExtend = true)
    {
        $SectionList = array();
        $Section = new Section();
        $Section->addElementColumn((new Element())
            ->setContent('Noten: 1 = sehr gut, 2 = gut, 3 = befriedigend, 4 = ausreichend, 5 = mangelhalft, 6 = ungenügend')
            ->styleTextSize('8pt')
            ->stylePaddingBottom(($isExtend ? '0px' : '12px' ))
            ->styleFontFamily(self::FONT_FAMILY)
        );
        $SectionList[] = $Section;
        if($isExtend){
            $Section = new Section();
            $Section->addElementColumn((new Element())
                ->setContent('* Bei Belegung der zweiten abschlussorientierten Fremdsprache entfällt der Neigungskurs (§ 18 SOMIA)')
                ->styleTextSize('8pt')
                ->stylePaddingTop()
                ->styleFontFamily(self::FONT_FAMILY)
            );
            $SectionList[] = $Section;
        }
        return $SectionList;
    }

    /**
     * @param int    $personId
     * @param string $Height
     *
     * @return Section[]
     */
    public function getEZSHArrangement($personId, $Height = '100px')
    {
        $SectionList = array();
        $Section = new Section();
        $Section->addElementColumn(((new Element())
            ->setContent('Besonderes Engagement an den Zinzendorfschulen')
            ->styleTextSize('14px')
            ->styleMarginTop('20px')
            ->styleTextBold()
            ->stylePaddingBottom('4px')
            ->styleFontFamily(self::FONT_FAMILY)
        )
        );
        $SectionList[] = $Section;
        $Section = new Section();
        $Section->addElementColumn((new Element())
            ->setContent('{% if(Content.P'.$personId.'.Input.Arrangement is not empty) %}
                    {{ Content.P'.$personId.'.Input.Arrangement|nl2br }}
                {% else %}
                    ---
                {% endif %}')
            ->styleHeight($Height)
            ->styleFontFamily(self::FONT_FAMILY)
        );
        $SectionList[] = $Section;
        return $SectionList;
    }
}
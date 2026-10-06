<?php

namespace SPHERE\Application\Education\Competence\SkillRate;

use SPHERE\Application\Api\Education\Competence\ApiSkillCertificate;
use SPHERE\Application\Education\Certificate\Prepare\Prepare;
use SPHERE\Application\Education\Certificate\Prepare\Service\Entity\TblPrepareCertificate;
use SPHERE\Application\Education\Competence\ScoreType\Service\Entity\TblScoreType;
use SPHERE\Application\Education\Competence\SkillGrid\SkillGrid;
use SPHERE\Application\Education\Competence\SkillRate\Service\Entity\TblStudentSkill;
use SPHERE\Application\Education\Graduation\Grade\Grade;
use SPHERE\Application\Education\Graduation\Gradebook\MinimumGradeCount\SelectBoxItem;
use SPHERE\Application\Education\Lesson\DivisionCourse\DivisionCourse;
use SPHERE\Application\Education\Lesson\DivisionCourse\Service\Entity\TblDivisionCourse;
use SPHERE\Common\Frontend\Form\Repository\Field\RadioBox;
use SPHERE\Common\Frontend\Form\Repository\Field\SelectBox;
use SPHERE\Common\Frontend\Form\Repository\Field\TextField;
use SPHERE\Common\Frontend\Form\Structure\Form;
use SPHERE\Common\Frontend\Form\Structure\FormColumn;
use SPHERE\Common\Frontend\Form\Structure\FormGroup;
use SPHERE\Common\Frontend\Form\Structure\FormRow;
use SPHERE\Common\Frontend\Icon\Repository\Exclamation;
use SPHERE\Common\Frontend\Icon\Repository\Save;
use SPHERE\Common\Frontend\Link\Repository\Primary;
use SPHERE\Common\Frontend\Message\Repository\Danger;
use SPHERE\Common\Frontend\Message\Repository\Warning;

class FrontendPrepareCertificate extends FrontendDivisionCourse
{
    /**
     * @param TblDivisionCourse $tblDivisionCourse
     * @param TblPrepareCertificate $tblPrepareCertificate
     * @param string $Route
     * @param $NextSkillId
     *
     * @return string
     */
    public function loadPrepareCompetenceContent(TblDivisionCourse $tblDivisionCourse, TblPrepareCertificate $tblPrepareCertificate, string $Route, $NextSkillId): string
    {
        $content = '';
        if ($NextSkillId) {
            $global = $this->getGlobal();
            $global->POST['Data']['Id'] = $NextSkillId;
            $global->savePost();

            $content = $this->loadEditPrepareInterdisciplinaryContent($tblDivisionCourse->getId(), $tblPrepareCertificate->getId(), $Route, ['Id' => $NextSkillId]);
        }

        // erstmal nur von Kompetenzraster möglich, todo eigene sind ja individuell
        $skillList = SkillRate::useService()->getSkillListByDivisionCourse($tblDivisionCourse);
        $list = [];
        foreach ($skillList as $tblSkill) {
            $list[] = new SelectBoxItem($tblSkill->getId(), $tblSkill->getDisplayName());
        }

        return (new Form(new FormGroup([
            new FormRow(
                new FormColumn(
                    (new SelectBox("Data[Id]", "Kompetenz wählen", ['{{ Name }}' => $list], null, false, null))
                        ->ajaxPipelineOnChange(ApiSkillCertificate::pipelineLoadEditDivisionCourseSkillRateContent(
                            $tblDivisionCourse->getId(), $tblPrepareCertificate->getId(), $Route))
                )
            ),
            new FormRow(
                new FormColumn(
                    ApiSkillCertificate::receiverBlock($content, 'SkillRateContent')
                )
            )
        ])))->disableSubmitAction();
    }

    /**
     * @param $DivisionCourseId
     * @param $PrepareCertificateId
     * @param $Route
     * @param null $Data
     * @param null $ErrorList
     *
     * @return string
     */
    public function loadEditPrepareInterdisciplinaryContent($DivisionCourseId, $PrepareCertificateId, $Route, $Data = null, $ErrorList = null): string
    {
        if ($Data === null || empty($Data['Id'])) {
            return new Warning("Bitte wählen Sie zunächst eine Kompetenz aus.", new Exclamation());
        }

        if (!($tblDivisionCourse = DivisionCourse::useService()->getDivisionCourseById($DivisionCourseId))) {
            return new Danger('Kurs nicht gefunden.', new Exclamation());
        }

        $gradeFrontend = Grade::useFrontend();
        $tblPersonList = false;

        $integrationList =[];
        $pictureList = [];
        $courseList = [];
        $studentSkillList = [];
        $scoreTypeList = [];
        $tblSkill = false;
        if (($tblYear = $tblDivisionCourse->getServiceTblYear())
            && ($tblSkill = SkillGrid::useService()->getSkillById($Data['Id']))
            && ($tblPersonList = $tblDivisionCourse->getStudentsWithSubCourses())
        ) {
            foreach ($tblPersonList as $tblPerson) {
                if (($virtualStudentSkill = SkillRate::useService()->getVirtualStudentSkillBySkillName($tblPerson, $tblYear, $tblSkill))) {
                    $studentSkillList[$tblPerson->getId()] = $virtualStudentSkill;

                    $tblScoreTypeStudent = $virtualStudentSkill->getServiceTblScoreType();
                    $scoreTypeList[$tblScoreTypeStudent ? $tblScoreTypeStudent->getId() : -1] = $tblScoreTypeStudent;

                    // Schüler-Informationen
                    Grade::useService()->setStudentInfo($tblPerson, $tblYear, $integrationList, $pictureList, $courseList);
                }
            }
        }
        $hasPicture = !empty($pictureList);
        $hasIntegration = !empty($integrationList);
        $hasCourse = !empty($courseList);
        $headerList = $gradeFrontend->getGradeBookPreHeaderList($hasPicture, $hasIntegration, $hasCourse);
        $headerList['SkillRates'] = $gradeFrontend->getTableColumnHead('Bewertungen in den einzelnen Fächern');
        $headerList['Average'] = $gradeFrontend->getTableColumnHead('&#216;');

        $tblScoreType = null;
        $isDiverseScoreType = false;
        if (count($scoreTypeList) == 0) {
            $headerList['Percent'] = $gradeFrontend->getTableColumnHead('Prozent');
        } elseif (count($scoreTypeList) == 1) {
            $tblScoreType = current($scoreTypeList);
            /** @var TblScoreType $tblScoreType */
            if ($tblScoreType) {
                foreach ($tblScoreType->getScoreTypeItems() as $tblScoreTypeItem) {
                    $headerList['ScoreTypeId_' . $tblScoreTypeItem->getId()] = $gradeFrontend->getTableColumnHead($tblScoreTypeItem->getName());
                }
                // erforderlich fürs Entfernen der Radiooption, wenn einmal gesetzt
                $headerList['ScoreTypeId_0'] = $gradeFrontend->getTableColumnHead('Keine Bewertung');
            } else {
                $headerList['Percent'] = $gradeFrontend->getTableColumnHead('Prozent');
            }
        } else {
            $isDiverseScoreType = true;
            $headerList['Diverse'] = $gradeFrontend->getTableColumnHead('Bewertung');
        }

        $hasProposalGrades = false;
        $count = 0;
        $bodyList = [];
        if ($tblPersonList
            && ($tblPrepareCertificate = Prepare::useService()->getPrepareById($PrepareCertificateId))
        ) {
            foreach ($tblPersonList as $tblPerson) {
                if (isset($studentSkillList[$tblPerson->getId()])) {
                    // Schüler-Informationen
                    Grade::useService()->setStudentInfo($tblPerson, $tblYear, $integrationList, $pictureList, $courseList);

                    $bodyList[$tblPerson->getId()] = $gradeFrontend->getGradeBookPreBodyList($tblPerson, ++$count,
                        $hasPicture, $hasIntegration, $hasCourse,
                        $pictureList, $integrationList, $courseList);

                    $tblStudentSkillRate = false;
                    $virtualStudentSkill = $studentSkillList[$tblPerson->getId()];
                    $averageArray['Value'] = '';
                    if ($virtualStudentSkill instanceof TblStudentSkill) {
                        $averageArray = SkillRate::useService()->getStudentSkillRateLastOrAverageValueForInterdisciplinaryOverAllSubjects($virtualStudentSkill);
                        $bodyList[$tblPerson->getId()]['SkillRates'] = $gradeFrontend->getTableColumnBody(
                            implode(', ', SkillRate::useService()->getStudentSkillRateListForInterdisciplinary($virtualStudentSkill))
                        );

                        $bodyList[$tblPerson->getId()]['Average'] = $gradeFrontend->getTableColumnBody($averageArray['Display']);

                        $tblStudentSkillRate = SkillRate::useService()->getStudentSkillRateForCertificateBy($virtualStudentSkill, $tblPrepareCertificate);

                        $inputKey = 'StudentSkillId_' . $virtualStudentSkill->getId();
                    } else {
                        $bodyList[$tblPerson->getId()]['SkillRates'] = $gradeFrontend->getTableColumnBody('&nbsp;');
                        $bodyList[$tblPerson->getId()]['Average'] = $gradeFrontend->getTableColumnBody('&nbsp;');

                        $inputKey = 'SkillId_' . $virtualStudentSkill->getId();
                    }

                    $isGradeProposal = false;
                    // gespeicherte Bewertung
                    if ($tblStudentSkillRate) {
                        $global = $this->getGlobal();
                        if (($tblStudentSkillRateScoreTypeItem = $tblStudentSkillRate->getServiceTblScoreTypeItem())) {
                            $global->POST['Data']['ScoreTypeSkills'][$tblPerson->getId()][$inputKey] = $tblStudentSkillRateScoreTypeItem->getId();
                        } else {
                            $global->POST['Data']['PercentSkills'][$tblPerson->getId()][$inputKey] = $tblStudentSkillRate->getRate();

                        }
                        $global->savePost();
                    // Bewertungsvorschlag eintragen
                    } elseif ($averageArray['Value'] !== '') {
                        $isGradeProposal = true;
                        $hasProposalGrades = true;
                        $global = $this->getGlobal();
                        $proposalValue = round($averageArray['Value']);
                        if (isset($studentSkillList[$tblPerson->getId()])
                            && ($tblScoreTypeStudent = $studentSkillList[$tblPerson->getId()]->getServiceTblScoreType())
                        ) {
                            // findet ScoreTypeItem anhand des Zahlenwertes
                            $tempList = array_filter($tblScoreTypeStudent->getScoreTypeItems(), fn($e) => $e->getValue() == $proposalValue);
                            $temp = reset($tempList);
                            if ($temp) {
                                $global->POST['Data']['ScoreTypeSkills'][$tblPerson->getId()][$inputKey] = $temp->getId();
                            }
                        } else {
                            $global->POST['Data']['PercentSkills'][$tblPerson->getId()][$inputKey] = $proposalValue;
                        }
                        $global->savePost();
                    }

                    if ($tblScoreType) {
                        $identifier = "Data[ScoreTypeSkills][{$tblPerson->getId()}][$inputKey]";
                        foreach ($tblScoreType->getScoreTypeItems() as $tblScoreTypeItem) {
                            $input = new RadioBox($identifier, '&nbsp;', $tblScoreTypeItem->getId());
                            $bodyList[$tblPerson->getId()]['ScoreTypeId_' . $tblScoreTypeItem->getId()] = $gradeFrontend->getTableColumnBody($input);
                        }
                        // erforderlich fürs Entfernen der Radiooption, wenn einmal gesetzt
                        $input = new RadioBox($identifier, '&nbsp;', 0);
                        $bodyList[$tblPerson->getId()]['ScoreTypeId_0'] = $gradeFrontend->getTableColumnBody($input);
                    } elseif ($isDiverseScoreType
                        && isset($studentSkillList[$tblPerson->getId()])
                        && ($tblScoreTypeStudent = $studentSkillList[$tblPerson->getId()]->getServiceTblScoreType())
                    ) {
                        // Divers (Schülerabhängig)
                        $identifier = "Data[ScoreTypeSkills][{$tblPerson->getId()}][$inputKey]";
                        $input = new SelectBox($identifier, '', ['{{ Name }}' => $tblScoreTypeStudent->getScoreTypeItems()], null, true, null);
                        if ($isGradeProposal) {
                            $input->setPrefixValue('Vorschlag');
                        }
                        $bodyList[$tblPerson->getId()]['Diverse'] = $gradeFrontend->getTableColumnBody($input);
                    } else {
                        // Prozent
                        $identifier = "Data[PercentSkills][{$tblPerson->getId()}][$inputKey]";
                        $input = new TextField($identifier);
                        if ($isGradeProposal) {
                            $input->setPrefixValue('Vorschlag');
                        }

                        // Anzeige Fehlermeldung
                        if (isset($ErrorList[$identifier])) {
                            $input->setError($ErrorList[$identifier]['Message']);
                        }

                        $bodyList[$tblPerson->getId()][$isDiverseScoreType ? 'Diverse' : 'Percent'] = $gradeFrontend->getTableColumnBody($input);
                    }
                }
            }
        }

        if (!empty($bodyList)) {
            $nextSkillId = $tblSkill
                ? $this->getNextId(SkillRate::useService()->getSkillListByDivisionCourse($tblDivisionCourse), $tblSkill->getId())
                : null;

            return ($hasProposalGrades ? new Warning('Es wurden noch nicht alle Bewertungsvorschläge gespeichert.', new Exclamation()) : '')
                . $gradeFrontend->getTableCustom($headerList, $bodyList)
                . ($ErrorList ? new Danger("Die Daten wurden nicht gespeichert. Bitte beachten Sie die Fehlermeldungen weiter oben.") : '')
                . (new Primary('Speichern', ApiSkillCertificate::getEndpoint(), new Save()))
                    ->ajaxPipelineOnClick(ApiSkillCertificate::pipelineSaveEditDivisionCourseSkillRate(
                        $DivisionCourseId, $PrepareCertificateId, $Route, $nextSkillId))
//                . (new Standard('Abbrechen', '/Education/Competence/SkillRate', new Disable()))
//                    ->ajaxPipelineOnClick(ApiSkillRate::pipelineLoadViewDivisionCourseContent(
//                        $DivisionCourseId, $SubjectId, $SelectedYearId, $IsInterdisciplinary ? 'true' : 'false'))
                ;
        }

        return new Warning('Keine Schüler für die ausgewählten Kompetenz gefunden.', new Exclamation());
    }

    private function getNextId(array $array, int $id): ?int
    {
        $array = array_values($array);
        $ids   = array_map(fn($e) => $e->getId(), $array);
        $index = array_search($id, $ids);

        return $index !== false ? ($ids[$index + 1] ?? null) : null;
    }
}
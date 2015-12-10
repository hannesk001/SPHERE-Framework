<?php

namespace SPHERE\Application\Education\Graduation\Gradebook;

use SPHERE\Application\Education\Graduation\Gradebook\Service\Entity\TblGrade;
use SPHERE\Application\Education\Graduation\Gradebook\Service\Entity\TblGradeType;
use SPHERE\Application\Education\Graduation\Gradebook\Service\Entity\TblScoreCondition;
use SPHERE\Application\Education\Graduation\Gradebook\Service\Entity\TblScoreConditionGroupList;
use SPHERE\Application\Education\Graduation\Gradebook\Service\Entity\TblScoreGroup;
use SPHERE\Application\Education\Graduation\Gradebook\Service\Entity\TblScoreGroupGradeTypeList;
use SPHERE\Application\Education\Graduation\Gradebook\Service\Entity\TblTest;
use SPHERE\Application\Education\Lesson\Division\Division;
use SPHERE\Application\Education\Lesson\Division\Service\Entity\TblDivision;
use SPHERE\Application\Education\Lesson\Subject\Service\Entity\TblSubject;
use SPHERE\Application\Education\Lesson\Subject\Subject;
use SPHERE\Application\Education\Lesson\Term\Term;
use SPHERE\Application\People\Person\Person;
use SPHERE\Application\Platform\Gatekeeper\Authorization\Account\Account;
use SPHERE\Common\Frontend\Form\Repository\Button\Primary;
use SPHERE\Common\Frontend\Form\Repository\Field\CheckBox;
use SPHERE\Common\Frontend\Form\Repository\Field\DatePicker;
use SPHERE\Common\Frontend\Form\Repository\Field\NumberField;
use SPHERE\Common\Frontend\Form\Repository\Field\SelectBox;
use SPHERE\Common\Frontend\Form\Repository\Field\TextField;
use SPHERE\Common\Frontend\Form\Structure\Form;
use SPHERE\Common\Frontend\Form\Structure\FormColumn;
use SPHERE\Common\Frontend\Form\Structure\FormGroup;
use SPHERE\Common\Frontend\Form\Structure\FormRow;
use SPHERE\Common\Frontend\Icon\Repository\Calendar;
use SPHERE\Common\Frontend\Icon\Repository\ChevronLeft;
use SPHERE\Common\Frontend\Icon\Repository\Edit;
use SPHERE\Common\Frontend\Icon\Repository\Listing;
use SPHERE\Common\Frontend\Icon\Repository\ListingTable;
use SPHERE\Common\Frontend\Icon\Repository\Minus;
use SPHERE\Common\Frontend\Icon\Repository\Pencil;
use SPHERE\Common\Frontend\Icon\Repository\Plus;
use SPHERE\Common\Frontend\Icon\Repository\PlusSign;
use SPHERE\Common\Frontend\Icon\Repository\Quantity;
use SPHERE\Common\Frontend\Icon\Repository\Save;
use SPHERE\Common\Frontend\Icon\Repository\Select;
use SPHERE\Common\Frontend\IFrontendInterface;
use SPHERE\Common\Frontend\Layout\Repository\Container;
use SPHERE\Common\Frontend\Layout\Repository\Header;
use SPHERE\Common\Frontend\Layout\Repository\Panel;
use SPHERE\Common\Frontend\Layout\Repository\Title;
use SPHERE\Common\Frontend\Layout\Repository\Well;
use SPHERE\Common\Frontend\Layout\Structure\Layout;
use SPHERE\Common\Frontend\Layout\Structure\LayoutColumn;
use SPHERE\Common\Frontend\Layout\Structure\LayoutGroup;
use SPHERE\Common\Frontend\Layout\Structure\LayoutRow;
use SPHERE\Common\Frontend\Link\Repository\Standard;
use SPHERE\Common\Frontend\Message\Repository\Warning;
use SPHERE\Common\Frontend\Table\Structure\TableData;
use SPHERE\Common\Frontend\Text\Repository\Bold;
use SPHERE\Common\Frontend\Text\Repository\Muted;
use SPHERE\Common\Frontend\Text\Repository\Small;
use SPHERE\Common\Window\Redirect;
use SPHERE\Common\Window\Stage;
use SPHERE\System\Extension\Extension;
use SPHERE\System\Extension\Repository\Debugger;

/**
 * Class Frontend
 * @package SPHERE\Application\Education\Graduation\Gradebook
 */
class Frontend extends Extension implements IFrontendInterface
{

    /**
     * @param null $GradeType
     * @return Stage
     */
    public function frontendGradeType($GradeType = null)
    {

        $Stage = new Stage('Zensuren-Typ', 'Übersicht');

        $tblGradeTypeAll = Gradebook::useService()->getGradeTypeAllWhereTest();
        if ($tblGradeTypeAll) {
            foreach ($tblGradeTypeAll as $tblGradeType) {
                $tblGradeType->DisplayName = $tblGradeType->getIsHighlighted()
                    ? new Bold($tblGradeType->getName()) : $tblGradeType->getName();
                $tblGradeType->DisplayCode = $tblGradeType->getIsHighlighted()
                    ? new Bold($tblGradeType->getCode()) : $tblGradeType->getCode();
                $tblGradeType->Option = new Standard('', '/Education/Graduation/Gradebook/GradeType/Edit',
                    new Edit(),
                    array(
                        'Id' => $tblGradeType->getId()
                    ),
                    'Zensuren-Typ bearbeiten'
                );
            }
        }

        $Form = $this->formGradeType()
            ->appendFormButton(new Primary('Speichern', new Save()))
            ->setConfirm('Eventuelle Änderungen wurden noch nicht gespeichert');

        $Stage->setContent(
            new Layout(array(
                new LayoutGroup(array(
                    new LayoutRow(array(
                        new LayoutColumn(array(
                            new TableData($tblGradeTypeAll, null, array(
                                'DisplayName' => 'Name',
                                'DisplayCode' => 'Abk&uuml;rzung',
                                'Description' => 'Beschreibung',
                                'Option' => 'Option'
                            ))
                        ))
                    ))
                ), new Title(new ListingTable() . ' Übersicht')),
                new LayoutGroup(array(
                    new LayoutRow(array(
                        new LayoutColumn(
                            new Well(Gradebook::useService()->createGradeTypeWhereTest($Form, $GradeType))
                        )
                    ))
                ), new Title(new PlusSign() . ' Hinzufügen'))
            ))
        );

        return $Stage;
    }

    /**
     * @param null $Id
     * @param $GradeType
     * @param bool $IsOpen
     * @return Stage
     */
    public function frontendEditGradeType($Id = null, $GradeType = null, $IsOpen = false)
    {
        $Stage = new Stage('Zensuren-Typ', 'Bearbeiten');
        $Stage->addButton(
            new Standard('Zur&uuml;ck', '/Education/Graduation/Gradebook/GradeType', new ChevronLeft(),
                array('IsOpen' => $IsOpen))
        );

        $tblGradeType = Gradebook::useService()->getGradeTypeById($Id);
        if ($tblGradeType) {
            $Global = $this->getGlobal();
            if (!$Global->POST) {
                $Global->POST['GradeType']['Name'] = $tblGradeType->getName();
                $Global->POST['GradeType']['Code'] = $tblGradeType->getCode();
                $Global->POST['GradeType']['IsHighlighted'] = $tblGradeType->getIsHighlighted();
                $Global->POST['GradeType']['Description'] = $tblGradeType->getDescription();
                $Global->savePost();
            }

            $Form = $this->formGradeType()
                ->appendFormButton(new Primary('Speichern', new Save()))
                ->setConfirm('Eventuelle Änderungen wurden noch nicht gespeichert');
            $Stage->setContent(
                new Layout(array(
                    new LayoutGroup(array(
                        new LayoutRow(array(
                            new LayoutColumn(
                                new Panel(
                                    'Zensuren-Typ',
                                    $tblGradeType->getName() . ' (' . $tblGradeType->getCode() . ')' .
                                    ($tblGradeType->getDescription() !== '' ? '&nbsp;&nbsp;'
                                        . new Muted(new Small(new Small($tblGradeType->getDescription()))) : ''),
                                    Panel::PANEL_TYPE_INFO
                                )
                            ),
                        ))
                    )),
                    new LayoutGroup(array(
                        new LayoutRow(array(
                            new LayoutColumn(
                                new Well(Gradebook::useService()->updateGradeType($Form, $Id, $GradeType, $IsOpen))
                            ),
                        ))
                    ), new Title(new Edit() . ' Bearbeiten'))
                ))
            );

            return $Stage;
        } else {
            return new Stage('Zensuren-Typ nicht gefunden')
            . new Redirect('/Education/Graduation/Gradebook/GradeType', 2, array('IsOpen' => $IsOpen));
        }
    }

    private function formGradeType()
    {
        return new Form(new FormGroup(array(
            new FormRow(array(
                new FormColumn(
                    new TextField('GradeType[Name]', 'Leistungskontrolle', 'Name'), 9
                ),
                new FormColumn(
                    new TextField('GradeType[Code]', 'LK', 'Abk&uuml;rzung'), 3
                ),
            )),
            new FormRow(array(
                new FormColumn(
                    new TextField('GradeType[Description]', '', 'Beschreibung'), 12
                ),
                new FormColumn(
                    new CheckBox('GradeType[IsHighlighted]', 'Fett markiert', 1), 2
                )
            )),
        )));
    }

    /**
     * @return Stage
     */
    public function frontendGradeBook()
    {
        $Stage = new Stage('Notenbuch', 'Auswahl');

        $tblPerson = false;
        $tblPersonAllByAccount = Account::useService()->getPersonAllByAccount(Account::useService()->getAccountBySession());
        if ($tblPersonAllByAccount) {
            $tblPerson = $tblPersonAllByAccount[0];
        }

        // ToDo JohK ausbauen
        //teacher1 82
        //teacher2 83
        //teacher3 84
        $tblPerson = Person::useService()->getPersonById(82);

        $isKlassenLehrer = false;

        $divisionSubjectTable = array();
        $divisionSubjectList = array();

        if ($tblPerson) {
            $tblSubjectTeacherAllByTeacher = Division::useService()->getSubjectTeacherAllByTeacher($tblPerson);
            if ($tblSubjectTeacherAllByTeacher) {
                foreach ($tblSubjectTeacherAllByTeacher as $tblSubjectTeacher) {
                    $tblDivisionSubject = $tblSubjectTeacher->getTblDivisionSubject();
                    if ($tblDivisionSubject->getTblSubjectGroup()) {
                        $divisionSubjectList[$tblDivisionSubject->getTblDivision()->getId()]
                        [$tblDivisionSubject->getServiceTblSubject()->getId()]
                        [$tblDivisionSubject->getTblSubjectGroup()->getId()]
                            = $tblDivisionSubject->getId();
                    } else {
                        $tblDivisionSubjectAllWhereSubjectGroupByDivisionAndSubject
                            = Division::useService()->getDivisionSubjectAllWhereSubjectGroupByDivisionAndSubject(
                            $tblDivisionSubject->getTblDivision(),
                            $tblSubjectTeacher->getTblDivisionSubject()->getServiceTblSubject()
                        );
                        if ($tblDivisionSubjectAllWhereSubjectGroupByDivisionAndSubject) {
                            foreach ($tblDivisionSubjectAllWhereSubjectGroupByDivisionAndSubject as $item) {
                                $divisionSubjectList[$tblDivisionSubject->getTblDivision()->getId()]
                                [$tblDivisionSubject->getServiceTblSubject()->getId()]
                                [$item->getTblSubjectGroup()->getId()]
                                    = $item->getId();
                            }
                        } else {
                            $divisionSubjectList[$tblDivisionSubject->getTblDivision()->getId()]
                            [$tblSubjectTeacher->getTblDivisionSubject()->getServiceTblSubject()->getId()]
                                = $tblSubjectTeacher->getTblDivisionSubject()->getId();
                        }
                    }
                }
            }
        } elseif ($isKlassenLehrer) {
            // ToDo JohK KlassenLehrer

        }

        if (!empty($divisionSubjectList)) {
            foreach ($divisionSubjectList as $divisionId => $subjectList) {
                $tblDivision = Division::useService()->getDivisionById($divisionId);
                foreach ($subjectList as $subjectId => $value) {
                    $tblSubject = Subject::useService()->getSubjectById($subjectId);
                    if (is_array($value)) {
                        foreach ($value as $subjectGroupId => $subValue) {
                            $item = Division::useService()->getSubjectGroupById($subjectGroupId);
                            $divisionSubjectTable[] = array(
                                'Year' => $tblDivision->getServiceTblYear()->getName(),
                                'Type' => $tblDivision->getTblLevel()->getServiceTblType()->getName(),
                                'Division' => $tblDivision->getTblLevel()->getName() . $tblDivision->getName(),
                                'Subject' => $tblSubject->getName(),
                                'SubjectGroup' => $item->getName(),
                                'Option' => new Standard(
                                    '', '/Education/Graduation/Gradebook/Gradebook/Selected', new Select(), array(
                                    'DivisionSubjectId' => $subValue
                                ),
                                    'Auswählen'
                                )
                            );
                        }
                    } else {
                        $divisionSubjectTable[] = array(
                            'Year' => $tblDivision->getServiceTblYear()->getName(),
                            'Type' => $tblDivision->getTblLevel()->getServiceTblType()->getName(),
                            'Division' => $tblDivision->getTblLevel()->getName() . $tblDivision->getName(),
                            'Subject' => $tblSubject->getName(),
                            'SubjectGroup' => '',
                            'Option' => new Standard(
                                '', '/Education/Graduation/Gradebook/Gradebook/Selected', new Select(), array(
                                'DivisionSubjectId' => $value
                            ),
                                'Auswählen'
                            )
                        );
                    }
                }
            }
        }

        $Stage->setContent(
            new Form(array(
                new FormGroup(array(
                    new FormRow(array(
                        new FormColumn(array(
                            new TableData($divisionSubjectTable, null, array(
                                'Year' => 'Schuljahr',
                                'Type' => 'Schulart',
                                'Division' => 'Klasse',
                                'Subject' => 'Fach',
                                'SubjectGroup' => 'Gruppe',
                                'Option' => ''
                            ))
                        ))
                    ))
                ))
            ))
        );

        return $Stage;
    }

    /**
     * @param null $DivisionSubjectId
     * @param null $ScoreConditionId
     * @param null $Select
     * @return Stage|string
     */
    public function frontendSelectedGradeBook($DivisionSubjectId = null, $ScoreConditionId = null, $Select = null)
    {
        $Stage = new Stage('Notenbuch');

        if ($DivisionSubjectId === null || !($tblDivisionSubject = Division::useService()->getDivisionSubjectById($DivisionSubjectId))) {
            return $Stage . new Warning('Notenbuch nicht gefunden.') . new Redirect('/Education/Graduation/Gradebook/Gradebook',
                2);
        }

        $tblScoreConditionAll = Gradebook::useService()->getScoreConditionAll();

        $tblDivision = $tblDivisionSubject->getTblDivision();
        $tblScoreCondition = new TblScoreCondition();
        $grades = array();
        $rowList = array();
        if ($ScoreConditionId !== null) {

            $Global = $this->getGlobal();
            if (!$Global->POST) {
                $Global->POST['Select']['ScoreCondition'] = $ScoreConditionId;
                $Global->savePost();
            }

            $tblScoreCondition = Gradebook::useService()->getScoreConditionById($ScoreConditionId);
            $tblYear = $tblDivision->getServiceTblYear();
            $tblPeriodList = Term::useService()->getPeriodAllByYear($tblYear);
            $tblTestType = Gradebook::useService()->getTestTypeByIdentifier('TEST');

            if ($tblDivisionSubject->getTblSubjectGroup()) {
                $tblStudentList = Division::useService()->getSubjectStudentByDivisionSubject($tblDivisionSubject);
                if ($tblStudentList) {
                    foreach ($tblStudentList as $tblSubjectStudent) {
                        $grades[$tblSubjectStudent->getServiceTblPerson()->getId()] = Gradebook::useService()->getGradesByStudent(
                            $tblSubjectStudent->getServiceTblPerson(),
                            $tblDivision,
                            $tblDivisionSubject->getServiceTblSubject(),
                            $tblTestType
                        );
                    }
                }
            } else {
                $tblStudentList = Division::useService()->getStudentAllByDivision($tblDivision);
                if ($tblStudentList) {
                    foreach ($tblStudentList as $tblPerson) {
                        $grades[$tblPerson->getId()] = Gradebook::useService()->getGradesByStudent(
                            $tblPerson,
                            $tblDivision,
                            $tblDivisionSubject->getServiceTblSubject(),
                            $tblTestType
                        );
                    }
                }
            }

            $gradePositions = array();
            $columnList[] = new LayoutColumn(new Title(new Bold('Schüler')), 2);
            if ($tblPeriodList) {
                $width = floor(10 / count($tblPeriodList));
                foreach ($tblPeriodList as $tblPeriod) {
                    $columnList[] = new LayoutColumn(
                        new Title(new Bold($tblPeriod->getName()))
                        , $width
                    );
                }
                $rowList[] = new LayoutRow($columnList);
                $columnList = array();
                $columnList[] = new LayoutColumn(new Header(' '), 2);
                $columnSecondList[] = new LayoutColumn(new Header(' '), 2);
                foreach ($tblPeriodList as $tblPeriod) {
                    $tblTestList = Gradebook::useService()->getTestAllByTypeAndDivisionAndSubjectAndPeriodAndSubjectGroup(
                        $tblTestType,
                        $tblDivision,
                        $tblDivisionSubject->getServiceTblSubject(),
                        $tblPeriod,
                        $tblDivisionSubject->getServiceTblSubject() ? $tblDivisionSubject->getTblSubjectGroup() : null
                    );
                    if ($tblTestList) {
                        $columnSubList = array();
                        $columnSecondSubList = array();
                        $pos = 0;
                        foreach ($tblTestList as $tblTest) {
                            $gradePositions[$tblPeriod->getId()][$pos++] = $tblTest->getId();
                            $columnSubList[] = new LayoutColumn(
                                new Header(
                                    $tblTest->getTblGradeType()->getIsHighlighted()
                                        ? new Bold($tblTest->getTblGradeType()->getCode()) : $tblTest->getTblGradeType()->getCode())
                                , 1);
                            $date = $tblTest->getDate();
                            if (strlen($date) > 6) {
                                $date = substr($date, 0, 6);
                            }
                            $columnSecondSubList[] = new LayoutColumn(
                                new Header(
                                    $tblTest->getTblGradeType()->getIsHighlighted()
                                        ? new Bold($date) : $date)
                                , 1);
                        }
                        $columnSubList[] = new LayoutColumn(new Header(new Bold('&#216;')), 1);
                        $columnList[] = new LayoutColumn(new Layout(new LayoutGroup(new LayoutRow($columnSubList))),
                            $width);
                        $columnSecondList[] = new LayoutColumn(new Layout(new LayoutGroup(new LayoutRow($columnSecondSubList))),
                            $width);
                    } else {
                        $columnList[] = new LayoutColumn(new Header(' '), $width);
                        $columnSecondList[] = new LayoutColumn(new Header(' '), $width);
                    }
                }
                $rowList[] = new LayoutRow($columnSecondList);
                $rowList[] = new LayoutRow($columnList);

                if (!empty($grades)) {
                    foreach ($grades as $personId => $gradeList) {
                        $tblPerson = Person::useService()->getPersonById($personId);
                        $columnList = array();
                        $totalAverage = '';
//                            Gradebook::useService()->calcStudentGrade($tblPerson,
//                            $tblDivisionSubject->getServiceTblSubject(),
//                            $tblScoreCondition, null, $tblDivision);
                        $columnList[] = new LayoutColumn(
                            new Container($tblPerson->getFirstName() . ' ' . $tblPerson->getFirstName()
                                . ' ' . new Bold('&#216; ' . $totalAverage))
                            , 2);
                        foreach ($tblPeriodList as $tblPeriod) {
                            $columnSubList = array();
                            if (isset($gradePositions[$tblPeriod->getId()])) {
                                foreach ($gradePositions[$tblPeriod->getId()] as $pos => $testId) {
                                    $hasFound = false;
                                    /** @var TblGrade $grade */
                                    foreach ($gradeList as $grade) {
                                        if ($testId === $grade->getTblTest()->getId()) {
                                            $columnSubList[] = new LayoutColumn(
                                                new Container($grade->getTblGradeType()->getIsHighlighted()
                                                    ? new Bold($grade->getGrade()) : $grade->getGrade())
                                                , 1);
                                            $hasFound = true;
                                            break;
                                        }
                                    }
                                    if (!$hasFound) {
                                        $columnSubList[] = new LayoutColumn(
                                            new Container(' '), 1
                                        );
                                    }
                                }
                            } else {
                                $columnSubList[] = new LayoutColumn(
                                    new Container(' '), 12
                                );
                            }

                            /*
                             * Calc Average
                             */
//                            $average = Gradebook::useService()->calcStudentGrade($tblPerson, $tblDivisionSubject->getServiceTblSubject(),
//                                $tblScoreCondition, $tblPeriod);
//                            $columnSubList[] = new LayoutColumn(new Container(new Bold($average)), 1);

                            $columnList[] = new LayoutColumn(new Layout(new LayoutGroup(new LayoutRow($columnSubList))),
                                $width);
                        }
                        $rowList[] = new LayoutRow($columnList);
                    }
                }
            }
        }

        $Stage->setContent(
            new Layout(array(
                new LayoutGroup(array(
                    new LayoutRow(array(
                        new LayoutColumn(array(
                            new Panel(
                                'Fach-Klasse',
                                'Klasse ' . $tblDivision->getTblLevel()->getName() . $tblDivision->getName() . ' - ' .
                                $tblDivisionSubject->getServiceTblSubject()->getName() .
                                ($tblDivisionSubject->getTblSubjectGroup() ? new Small(
                                    ' (Gruppe: ' . $tblDivisionSubject->getTblSubjectGroup()->getName() . ')') : ''),
                                Panel::PANEL_TYPE_INFO
                            )
                        ), $ScoreConditionId !== null ? 6 : 12),
                        ($ScoreConditionId !== null ? new LayoutColumn(new Panel(
                            'Berechnungsvorschrift',
                            $tblScoreCondition->getName(),
                            Panel::PANEL_TYPE_INFO
                        ), 6) : null),
                        new LayoutColumn(array(
                            new Well(Gradebook::useService()->getGradeBook(
                                new Form(new FormGroup(array(
                                    new FormRow(array(
                                        new FormColumn(
                                            new SelectBox('Select[ScoreCondition]', 'Berechnungsvorschrift',
                                                array(
                                                    '{{ Name }}' => $tblScoreConditionAll
                                                )),
                                            12
                                        ),
                                    )),
                                )), new Primary('Auswählen', new Select()))
                                , $tblDivisionSubject->getId(), $Select))
                        )),
                    )),
                ))
            ))
            . ($ScoreConditionId !== null ? new Layout(new LayoutGroup($rowList)) : '')
        );

        return $Stage;
    }

    /**
     * @return Stage
     */
    public
    function frontendTest()
    {

        $Stage = new Stage('Leistungsermittlung', 'Auswahl');

        $tblPerson = false;
        $tblPersonAllByAccount = Account::useService()->getPersonAllByAccount(Account::useService()->getAccountBySession());
        if ($tblPersonAllByAccount) {
            $tblPerson = $tblPersonAllByAccount[0];
        }

        // ToDo JohK ausbauen
        //teacher1 82
        //teacher2 83
        //teacher3 84
        $tblPerson = Person::useService()->getPersonById(82);

        $isKlassenLehrer = false;

        $divisionSubjectTable = array();
        $divisionSubjectList = array();

//        if ($isSchulleitung) {
//            $tblDivisionAll = Division::useService()->getDivisionAll();
//            if ($tblDivisionAll) {
//                foreach ($tblDivisionAll as $tblDivision) {
//                    $tblDivisionSubjectAllByDivision = Division::useService()->getDivisionSubjectByDivision($tblDivision);
//                    if ($tblDivisionSubjectAllByDivision) {
//                        foreach ($tblDivisionSubjectAllByDivision as $tblDivisionSubject) {
//                            if ($tblDivisionSubject->getTblSubjectGroup()) {
//                                $divisionSubjectList[$tblDivisionSubject->getTblDivision()->getId()]
//                                [$tblDivisionSubject->getServiceTblSubject()->getId()]
//                                [$tblDivisionSubject->getTblSubjectGroup()->getId()]
//                                    = $tblDivisionSubject->getId();
//                            } else {
//                                $tblDivisionSubjectAllWhereSubjectGroupByDivisionAndSubject
//                                    = Division::useService()->getDivisionSubjectAllWhereSubjectGroupByDivisionAndSubject(
//                                    $tblDivisionSubject->getTblDivision(),
//                                    $tblDivisionSubject->getServiceTblSubject()
//                                );
//                                if ($tblDivisionSubjectAllWhereSubjectGroupByDivisionAndSubject) {
//                                    foreach ($tblDivisionSubjectAllWhereSubjectGroupByDivisionAndSubject as $item) {
//                                        $divisionSubjectList[$tblDivisionSubject->getTblDivision()->getId()]
//                                        [$tblDivisionSubject->getServiceTblSubject()->getId()]
//                                        [$item->getTblSubjectGroup()->getId()]
//                                            = $item->getId();
//                                    }
//                                } else {
//                                    $divisionSubjectList[$tblDivisionSubject->getTblDivision()->getId()]
//                                    [$tblDivisionSubject->getServiceTblSubject()->getId()]
//                                        = $tblDivisionSubject->getId();
//                                }
//                            }
//                        }
//                    }
//                }
//            }
//        } else
        if ($tblPerson) {
            $tblSubjectTeacherAllByTeacher = Division::useService()->getSubjectTeacherAllByTeacher($tblPerson);
            if ($tblSubjectTeacherAllByTeacher) {
                foreach ($tblSubjectTeacherAllByTeacher as $tblSubjectTeacher) {
                    $tblDivisionSubject = $tblSubjectTeacher->getTblDivisionSubject();
                    if ($tblDivisionSubject->getTblSubjectGroup()) {
                        $divisionSubjectList[$tblDivisionSubject->getTblDivision()->getId()]
                        [$tblDivisionSubject->getServiceTblSubject()->getId()]
                        [$tblDivisionSubject->getTblSubjectGroup()->getId()]
                            = $tblDivisionSubject->getId();
                    } else {
                        $tblDivisionSubjectAllWhereSubjectGroupByDivisionAndSubject
                            = Division::useService()->getDivisionSubjectAllWhereSubjectGroupByDivisionAndSubject(
                            $tblDivisionSubject->getTblDivision(),
                            $tblSubjectTeacher->getTblDivisionSubject()->getServiceTblSubject()
                        );
                        if ($tblDivisionSubjectAllWhereSubjectGroupByDivisionAndSubject) {
                            foreach ($tblDivisionSubjectAllWhereSubjectGroupByDivisionAndSubject as $item) {
                                $divisionSubjectList[$tblDivisionSubject->getTblDivision()->getId()]
                                [$tblDivisionSubject->getServiceTblSubject()->getId()]
                                [$item->getTblSubjectGroup()->getId()]
                                    = $item->getId();
                            }
                        } else {
                            $divisionSubjectList[$tblDivisionSubject->getTblDivision()->getId()]
                            [$tblSubjectTeacher->getTblDivisionSubject()->getServiceTblSubject()->getId()]
                                = $tblSubjectTeacher->getTblDivisionSubject()->getId();
                        }
                    }
                }
            }
        } elseif ($isKlassenLehrer) {
            // ToDo JohK KlassenLehrer

        }

        if (!empty($divisionSubjectList)) {
            foreach ($divisionSubjectList as $divisionId => $subjectList) {
                $tblDivision = Division::useService()->getDivisionById($divisionId);
                foreach ($subjectList as $subjectId => $value) {
                    $tblSubject = Subject::useService()->getSubjectById($subjectId);
                    if (is_array($value)) {
                        foreach ($value as $subjectGroupId => $subValue) {
                            $item = Division::useService()->getSubjectGroupById($subjectGroupId);
                            $divisionSubjectTable[] = array(
                                'Year' => $tblDivision->getServiceTblYear()->getName(),
                                'Type' => $tblDivision->getTblLevel()->getServiceTblType()->getName(),
                                'Division' => $tblDivision->getTblLevel()->getName() . $tblDivision->getName(),
                                'Subject' => $tblSubject->getName(),
                                'SubjectGroup' => $item->getName(),
                                'Option' => new Standard(
                                    '', '/Education/Graduation/Gradebook/Test/Selected', new Select(), array(
                                    'DivisionSubjectId' => $subValue
                                ),
                                    'Auswählen'
                                )
                            );
                        }
                    } else {
                        $divisionSubjectTable[] = array(
                            'Year' => $tblDivision->getServiceTblYear()->getName(),
                            'Type' => $tblDivision->getTblLevel()->getServiceTblType()->getName(),
                            'Division' => $tblDivision->getTblLevel()->getName() . $tblDivision->getName(),
                            'Subject' => $tblSubject->getName(),
                            'SubjectGroup' => '',
                            'Option' => new Standard(
                                '', '/Education/Graduation/Gradebook/Test/Selected', new Select(), array(
                                'DivisionSubjectId' => $value
                            ),
                                'Auswählen'
                            )
                        );
                    }
                }
            }
        }

        $Stage->setContent(
            new Form(array(
                new FormGroup(array(
                    new FormRow(array(
                        new FormColumn(array(
                            new TableData($divisionSubjectTable, null, array(
                                'Year' => 'Schuljahr',
                                'Type' => 'Schulart',
                                'Division' => 'Klasse',
                                'Subject' => 'Fach',
                                'SubjectGroup' => 'Gruppe',
                                'Option' => ''
                            ))
                        ))
                    ))
                ))
            ))
        );

        return $Stage;
    }

    /**
     * @param null $DivisionSubjectId
     * @param null $Test
     * @return Stage
     */
    public
    function frontendTestSelected(
        $DivisionSubjectId = null,
        $Test = null
    ) {

        $Stage = new Stage('Leistungsermittlung', 'Übersicht');
        $Stage->addButton(new Standard('Zurück', '/Education/Graduation/Gradebook/Test', new ChevronLeft()));

        $tblDivisionSubject = Division::useService()->getDivisionSubjectById($DivisionSubjectId);
        $tblDivision = $tblDivisionSubject->getTblDivision();
        $tblTestType = Gradebook::useService()->getTestTypeByIdentifier('TEST');

        $tblTestList = Gradebook::useService()->getTestAllByTypeAndDivisionAndSubjectAndPeriodAndSubjectGroup(
            $tblTestType,
            $tblDivision,
            $tblDivisionSubject->getServiceTblSubject(),
            null,
            $tblDivisionSubject->getTblSubjectGroup() ? $tblDivisionSubject->getTblSubjectGroup() : null
        );
        if ($tblTestList) {
            array_walk($tblTestList, function (TblTest &$tblTest) {
                $tblDivision = $tblTest->getServiceTblDivision();
                if ($tblDivision) {
                    $tblTest->Division = $tblDivision->getServiceTblYear()->getName() . ' - ' .
                        $tblDivision->getTblLevel()->getServiceTblType()->getName() . ' - ' .
                        $tblDivision->getTblLevel()->getName() . $tblDivision->getName();
                } else {
                    $tblTest->Division = '';
                }
                $tblTest->Subject = $tblTest->getServiceTblSubject()->getName();
                $tblTest->Period = $tblTest->getServiceTblPeriod()->getName();
                $tblTest->GradeType = $tblTest->getTblGradeType()->getName();
                $tblTest->Option = (new Standard('', '/Education/Graduation/Gradebook/Test/Edit', new Pencil(),
                        array('Id' => $tblTest->getId()), 'Bearbeiten'))
                    . (new Standard('', '/Education/Graduation/Gradebook/Test/Grade/Edit', new Listing(),
                        array('Id' => $tblTest->getId()), 'Zensuren bearbeiten'));
            });
        } else {
            $tblTestList = array();
        }

        $Form = $this->formTest()
            ->appendFormButton(new Primary('Speichern', new Save()))
            ->setConfirm('Eventuelle Änderungen wurden noch nicht gespeichert');

        $Stage->setContent(
            new Layout(array(
                new LayoutGroup(array(
                    new LayoutRow(array(
                        new LayoutColumn(array(
                            new Panel(
                                'Fach-Klasse',
                                'Klasse ' . $tblDivision->getTblLevel()->getName() . $tblDivision->getName() . ' - ' .
                                $tblDivisionSubject->getServiceTblSubject()->getName() .
                                ($tblDivisionSubject->getTblSubjectGroup() ? new Small(
                                    ' (Gruppe: ' . $tblDivisionSubject->getTblSubjectGroup()->getName() . ')') : ''),
                                Panel::PANEL_TYPE_INFO
                            )
                        ))
                    ))
                )),
                new LayoutGroup(array(
                    new LayoutRow(array(
                        new LayoutColumn(array(
                            new TableData($tblTestList, null, array(
                                'Division' => 'Klasse',
                                'Subject' => 'Fach',
                                'Period' => 'Zeitraum',
                                'GradeType' => 'Zensuren-Typ',
                                'Description' => 'Beschreibung',
                                'Date' => 'Datum',
                                'CorrectionDate' => 'Korrekturdatum',
                                'ReturnDate' => 'R&uuml;ckgabedatum',
                                'Option' => 'Option'
                            ))
                        ))
                    ))
                ), new Title(new ListingTable() . ' Übersicht')),
                new LayoutGroup(array(
                    new LayoutRow(array(
                        new LayoutColumn(array(
                            new Well(Gradebook::useService()->createTest($Form, $tblDivisionSubject->getId(),
                                $Test))
                        ))
                    ))
                ), new Title(new PlusSign() . ' Hinzufügen'))
            ))
        );

        return $Stage;
    }

    /**
     * @return Form
     */
    private
    function formTest()
    {
        $tblGradeTypeList = Gradebook::useService()->getGradeTypeAllWhereTest();
        $tblPeriodList = Term::useService()->getPeriodAll();

        return new Form(new FormGroup(array(
            new FormRow(array(
                new FormColumn(
                    new SelectBox('Test[Period]', 'Zeitraum', array('Name' => $tblPeriodList)), 6
                ),
                new FormColumn(
                    new SelectBox('Test[GradeType]', 'Zensuren-Typ', array('Name' => $tblGradeTypeList)), 6
                )
            )),
            new FormRow(array(
                new FormColumn(
                    new TextField('Test[Description]', '1. Klassenarbeit', 'Beschreibung'), 12
                ),
            )),
            new FormRow(array(
                new FormColumn(
                    new DatePicker('Test[Date]', '', 'Datum', new Calendar()), 4
                ),
                new FormColumn(
                    new DatePicker('Test[CorrectionDate]', '', 'Korrekturdatum', new Calendar()), 4
                ),
                new FormColumn(
                    new DatePicker('Test[ReturnDate]', '', 'R&uuml;ckgabedatum', new Calendar()), 4
                ),
            ))
        )));
    }

    /**
     * @param $Id
     * @param $Test
     *
     * @return Stage|string
     */
    public
    function frontendEditTest(
        $Id,
        $Test = null
    ) {
        $Stage = new Stage('Zensuren', 'Test bearbeiten');

        $tblTest = Gradebook::useService()->getTestById($Id);
        if ($tblTest) {
            $Global = $this->getGlobal();
            if (!$Global->POST) {
                $Global->POST['Test']['Description'] = $tblTest->getDescription();
                $Global->POST['Test']['Date'] = $tblTest->getDate();
                $Global->POST['Test']['CorrectionDate'] = $tblTest->getCorrectionDate();
                $Global->POST['Test']['ReturnDate'] = $tblTest->getReturnDate();
                $Global->savePost();
            }

            $tblDivisionSubject = Division::useService()->getDivisionSubjectByDivisionAndSubjectAndSubjectGroup(
                $tblTest->getServiceTblDivision(),
                $tblTest->getServiceTblSubject(),
                $tblTest->getServiceTblSubjectGroup() ? $tblTest->getServiceTblSubjectGroup() : null
            );

            $Stage->addButton(
                new Standard('Zur&uuml;ck', '/Education/Graduation/Gradebook/Test/Selected', new ChevronLeft()
                    , array('DivisionSubjectId' => $tblDivisionSubject->getId()))
            );

            $Form = new Form(new FormGroup(array(
                new FormRow(array(
                    new FormColumn(
                        new TextField('Test[Description]', '1. Klassenarbeit', 'Beschreibung'), 12
                    ),
                )),
                new FormRow(array(
                    new FormColumn(
                        new DatePicker('Test[Date]', '', 'Datum', new Calendar()), 4
                    ),
                    new FormColumn(
                        new DatePicker('Test[CorrectionDate]', '', 'Korrekturdatum', new Calendar()), 4
                    ),
                    new FormColumn(
                        new DatePicker('Test[ReturnDate]', '', 'R&uuml;ckgabedatum', new Calendar()), 4
                    ),
                ))
            )));
            $Form
                ->appendFormButton(new Primary('Speichern', new Save()))
                ->setConfirm('Eventuelle Änderungen wurden noch nicht gespeichert');

            $tblDivision = $tblTest->getServiceTblDivision();

            $Stage->setContent(
                new Layout (array(
                    new LayoutGroup(array(
                        new LayoutRow(array(
                            new LayoutColumn(
                                new Panel(
                                    'Fach-Klasse',
                                    'Klasse ' . $tblDivision->getTblLevel()->getName() . $tblDivision->getName() . ' - ' .
                                    $tblTest->getServiceTblSubject()->getName() .
                                    ($tblTest->getServiceTblSubjectGroup() ? new Small(
                                        ' (Gruppe: ' . $tblTest->getServiceTblSubjectGroup()->getName() . ')') : ''),
                                    Panel::PANEL_TYPE_INFO
                                ), 6
                            ),
                            new LayoutColumn(
                                new Panel('Zeitraum:', $tblTest->getServiceTblPeriod()->getName(),
                                    Panel::PANEL_TYPE_INFO), 3
                            ),
                            new LayoutColumn(
                                new Panel('Zensuren-Typ:', $tblTest->getTblGradeType()->getName(),
                                    Panel::PANEL_TYPE_INFO), 3
                            )
                        )),
                    )),
                    new LayoutGroup(array(
                        new LayoutRow(array(
                            new LayoutColumn(
                                new Well(Gradebook::useService()->updateTest($Form, $tblTest->getId(), $Test))
                            )
                        ))
                    ), new Title(new Edit() . ' Bearbeiten'))
                ))
            );

            return $Stage;
        } else {

            return new Warning('Test nicht gefunden')
            . new Redirect('/Education/Graduation/Gradebook/Test', 2);
        }
    }

    /**
     * @param $Id
     * @param $Grade
     *
     * @return Stage|string
     */
    public
    function frontendEditTestGrade(
        $Id,
        $Grade = null
    ) {

        $Stage = new Stage('Leistungsermittlung', 'Zensuren bearbeiten');

        $tblTest = Gradebook::useService()->getTestById($Id);
        if ($tblTest) {

            $tblDivisionSubject = Division::useService()->getDivisionSubjectByDivisionAndSubjectAndSubjectGroup(
                $tblTest->getServiceTblDivision(),
                $tblTest->getServiceTblSubject(),
                $tblTest->getServiceTblSubjectGroup() ? $tblTest->getServiceTblSubjectGroup() : null
            );

            $Stage->addButton(
                new Standard('Zur&uuml;ck', '/Education/Graduation/Gradebook/Test/Selected', new ChevronLeft()
                    , array('DivisionSubjectId' => $tblDivisionSubject->getId()))
            );

            $gradeList = Gradebook::useService()->getGradeAllByTest($tblTest);
            if ($gradeList) {
                $Global = $this->getGlobal();
                /** @var TblGrade $grade */
                foreach ($gradeList as $grade) {
                    if (empty($Grade)) {
                        $Global->POST['Grade'][$grade->getServiceTblPerson()->getId()]['Grade'] = $grade->getGrade();
                        $Global->POST['Grade'][$grade->getServiceTblPerson()->getId()]['Comment'] = $grade->getComment();
                    }
                }
                $Global->savePost();
            }

            $tblDivision = $tblTest->getServiceTblDivision();
            $student = array();

            if ($tblDivisionSubject->getTblSubjectGroup()) {
                $tblSubjectStudentAllByDivisionSubject = Division::useService()->getSubjectStudentByDivisionSubject($tblDivisionSubject);
                if ($tblSubjectStudentAllByDivisionSubject) {
                    foreach ($tblSubjectStudentAllByDivisionSubject as $tblSubjectStudent) {
                        $student[$tblSubjectStudent->getServiceTblPerson()->getId()]['Name']
                            = $tblSubjectStudent->getServiceTblPerson()->getFirstName() . ' '
                            . $tblSubjectStudent->getServiceTblPerson()->getLastName();
                        $student[$tblSubjectStudent->getServiceTblPerson()->getId()]['Grade']
                            = new TextField('Grade[' . $tblSubjectStudent->getServiceTblPerson()->getId() . '][Grade]',
                            '', '');
                        $student[$tblSubjectStudent->getServiceTblPerson()->getId()]['Comment']
                            = new TextField('Grade[' . $tblSubjectStudent->getServiceTblPerson()->getId() . '][Comment]',
                            '', '');
                        $tblGrade = Gradebook::useService()->getGradeByTestAndStudent($tblTest,
                            $tblSubjectStudent->getServiceTblPerson());
//                        Debugger::screenDump($tblGrade);
                        if ($tblGrade) {
                            $student[$tblSubjectStudent->getServiceTblPerson()->getId()]['Grade']
                                = (new TextField('Grade[' . $tblSubjectStudent->getServiceTblPerson()->getId() . '][Grade]',
                                '', ''))->setDisabled();
                            $student[$tblSubjectStudent->getServiceTblPerson()->getId()]['Comment']
                                = (new TextField('Grade[' . $tblSubjectStudent->getServiceTblPerson()->getId() . '][Comment]',
                                '', ''))->setDisabled();
                        } else {
                            $student[$tblSubjectStudent->getServiceTblPerson()->getId()]['Grade']
                                = new TextField('Grade[' . $tblSubjectStudent->getServiceTblPerson()->getId() . '][Grade]',
                                '', '');
                            $student[$tblSubjectStudent->getServiceTblPerson()->getId()]['Comment']
                                = new TextField('Grade[' . $tblSubjectStudent->getServiceTblPerson()->getId() . '][Comment]',
                                '', '');
                        }
                    }
                }
            } else {
                $tblDivisionStudentAll = Division::useService()->getStudentAllByDivision($tblDivision);
                if ($tblDivisionStudentAll) {
                    foreach ($tblDivisionStudentAll as $tblDivisionStudent) {
                        $student[$tblDivisionStudent->getId()]['Name']
                            = $tblDivisionStudent->getFirstName() . ' '
                            . $tblDivisionStudent->getLastName();
                        $tblGrade = Gradebook::useService()->getGradeByTestAndStudent($tblTest,
                            $tblDivisionStudent);
//                        Debugger::screenDump($tblGrade);
                        if ($tblGrade) {
                            $student[$tblDivisionStudent->getId()]['Grade']
                                = (new TextField('Grade[' . $tblDivisionStudent->getId() . '][Grade]',
                                '', ''))->setDisabled();
                            $student[$tblDivisionStudent->getId()]['Comment']
                                = (new TextField('Grade[' . $tblDivisionStudent->getId() . '][Comment]',
                                '', ''))->setDisabled();
                        } else {
                            $student[$tblDivisionStudent->getId()]['Grade']
                                = new TextField('Grade[' . $tblDivisionStudent->getId() . '][Grade]',
                                '', '');
                            $student[$tblDivisionStudent->getId()]['Comment']
                                = new TextField('Grade[' . $tblDivisionStudent->getId() . '][Comment]',
                                '', '');
                        }
                    }
                }
            }

            $Stage->setContent(
                new Layout (array(
                    new LayoutGroup(array(
                        new LayoutRow(array(
                            new LayoutColumn(
                                new Panel(
                                    'Fach-Klasse',
                                    'Klasse ' . $tblDivision->getTblLevel()->getName() . $tblDivision->getName() . ' - ' .
                                    $tblTest->getServiceTblSubject()->getName() .
                                    ($tblTest->getServiceTblSubjectGroup() ? new Small(
                                        ' (Gruppe: ' . $tblTest->getServiceTblSubjectGroup()->getName() . ')') : ''),
                                    Panel::PANEL_TYPE_INFO
                                ), 6
                            ),
                            new LayoutColumn(
                                new Panel('Zeitraum:', $tblTest->getServiceTblPeriod()->getName(),
                                    Panel::PANEL_TYPE_INFO), 3
                            ),
                            new LayoutColumn(
                                new Panel('Zensuren-Typ:', $tblTest->getTblGradeType()->getName(),
                                    Panel::PANEL_TYPE_INFO), 3
                            )
                        )),
                    )),
                    new LayoutGroup(array(
                        new LayoutRow(array(
                            new LayoutColumn(
                                Gradebook::useService()->updateGradeToTest(
                                    new Form(
                                        new FormGroup(array(
                                            new FormRow(
                                                new FormColumn(
                                                    new TableData(
                                                        $student, null, array(
                                                        'Name' => 'Schüler',
                                                        'Grade' => 'Zensur',
                                                        'Comment' => 'Kommentar'
                                                    ), false)
                                                )
                                            ),
                                        ))
                                        , new Primary('Speichern', new Save()))
                                    , $tblTest->getId(), $Grade
                                )
                            )
                        ))
                    )),
                ))
            );

            return $Stage;
        } else {

            return new Warning('Test nicht gefunden')
            . new Redirect('/Education/Graduation/Gradebook/Test', 2);
        }
    }

    /**
     * @param null $ScoreCondition
     * @return Stage
     */
    public
    function frontendScore(
        $ScoreCondition = null
    ) {

        $Stage = new Stage('Berechnungsvorschrift', 'Übersicht');
        $Stage->addButton(
            new Standard('Zensuren-Gruppe', '/Education/Graduation/Gradebook/Score/Group', new ListingTable(), null,
                'Erstellen/Berarbeiten')
        );

        $tblScoreConditionAll = Gradebook::useService()->getScoreConditionAll();
        if ($tblScoreConditionAll) {
            foreach ($tblScoreConditionAll as &$tblScoreCondition) {
                $scoreGroups = '';
                $tblScoreGroups = Gradebook::useService()->getScoreConditionGroupListByCondition($tblScoreCondition);
                if ($tblScoreGroups) {
                    foreach ($tblScoreGroups as $tblScoreGroup) {
                        $scoreGroups .= $tblScoreGroup->getTblScoreGroup()->getName() . ', ';
                    }
                }
                if (($length = strlen($scoreGroups)) > 2) {
                    $scoreGroups = substr($scoreGroups, 0, $length - 2);
                }
                $tblScoreCondition->ScoreGroups = $scoreGroups;
                $tblScoreCondition->Option =
//                    (new Standard('', '/Education/Graduation/Gradebook/Score/Condition/Edit', new Pencil(),
//                        array('Id' => $tblScoreCondition->getId()), 'Bearbeiten')) .
                    (new Standard('', '/Education/Graduation/Gradebook/Score/Group/Select', new Listing(),
                        array('Id' => $tblScoreCondition->getId()), 'Zensuren-Gruppen auswählen'));
            }
        }


        $Form = $this->formScoreCondition()
            ->appendFormButton(new Primary('Hinzufügen', new Plus()))
            ->setConfirm('Eventuelle Änderungen wurden noch nicht gespeichert');

        $Stage->setContent(
            new Layout(array(
                new LayoutGroup(array(
                    new LayoutRow(array(
                        new LayoutColumn(array(
                            new TableData($tblScoreConditionAll, null, array(
                                'Name' => 'Name',
                                'ScoreGroups' => 'Zensuren-Gruppen',
                                'Priority' => 'Priorität',
                                'Round' => 'Runden',
                                'Option' => 'Optionen',
                            ))
                        ))
                    ))
                ), new Title(new ListingTable() . ' Übersicht')),
                new LayoutGroup(array(
                    new LayoutRow(array(
                        new LayoutColumn(array(
                            new Well(Gradebook::useService()->createScoreCondition($Form, $ScoreCondition))
                        ))
                    ))
                ), new Title(new PlusSign() . ' Hinzufügen'))
            ))
        );

        return $Stage;
    }

    private
    function formScoreCondition()
    {
        return new Form(new FormGroup(array(
            new FormRow(array(
                new FormColumn(
                    new TextField('ScoreCondition[Name]', 'Klassenarbeit 60% : Rest 40%', 'Name'), 8
                ),
                new FormColumn(
                    new TextField('ScoreCondition[Round]', '', 'Rundung'), 2
                ),
                new FormColumn(
                    new NumberField('ScoreCondition[Priority]', '1', 'Priorität'), 2
                )
            ))
        )));
    }

    /**
     * @param null $ScoreGroup
     * @return Stage
     */
    public
    function frontendScoreGroup(
        $ScoreGroup = null
    ) {

        $Stage = new Stage('Zensuren-Gruppe', 'Übersicht');
        $Stage->addButton(
            new Standard('Zurück', '/Education/Graduation/Gradebook/Score', new ChevronLeft())
        );

        $tblScoreGroupAll = Gradebook::useService()->getScoreGroupAll();
        if ($tblScoreGroupAll) {
            foreach ($tblScoreGroupAll as &$tblScoreGroup) {
                $gradeTypes = '';
                $tblScoreGroupGradeTypes = Gradebook::useService()->getScoreGroupGradeTypeListByGroup($tblScoreGroup);
                if ($tblScoreGroupGradeTypes) {
                    foreach ($tblScoreGroupGradeTypes as $tblScoreGroupGradeType) {
                        $gradeTypes .= $tblScoreGroupGradeType->getTblGradeType()->getName() . ', ';
                    }
                }
                if (($length = strlen($gradeTypes)) > 2) {
                    $gradeTypes = substr($gradeTypes, 0, $length - 2);
                }
                $tblScoreGroup->GradeTypes = $gradeTypes;
                $tblScoreGroup->Option =
//                    (new Standard('', '/Education/Graduation/Gradebook/Score/Group/Edit', new Pencil(),
//                        array('Id' => $tblScoreGroup->getId()), 'Bearbeiten')) .
                    (new Standard('', '/Education/Graduation/Gradebook/Score/Group/GradeType/Select', new Listing(),
                        array('Id' => $tblScoreGroup->getId()), 'Zensuren-Typen auswählen'));
            }
        }


        $Form = $this->formScoreGroup()
            ->appendFormButton(new Primary('Hinzufügen', new Plus()))
            ->setConfirm('Eventuelle Änderungen wurden noch nicht gespeichert');

        $Stage->setContent(
            new Layout(array(
                new LayoutGroup(array(
                    new LayoutRow(array(
                        new LayoutColumn(array(
                            new TableData($tblScoreGroupAll, null, array(
                                'Name' => 'Name',
                                'GradeTypes' => 'Zensuren-Typen',
                                'Multiplier' => 'Faktor',
                                'Round' => 'Runden',
                                'Option' => 'Optionen',
                            ))
                        ))
                    ))
                ), new Title(new ListingTable() . ' Übersicht')),
                new LayoutGroup(array(
                    new LayoutRow(array(
                        new LayoutColumn(array(
                            new Well(Gradebook::useService()->createScoreGroup($Form, $ScoreGroup))
                        ))
                    ))
                ), new Title(new PlusSign() . ' Hinzufügen'))
            ))
        );

        return $Stage;
    }

    private
    function formScoreGroup()
    {
        return new Form(new FormGroup(array(
            new FormRow(array(
                new FormColumn(
                    new TextField('ScoreGroup[Name]', 'Rest', 'Name'), 8
                ),
                new FormColumn(
                    new TextField('ScoreGroup[Round]', '', 'Rundung'), 2
                ),
                new FormColumn(
                    new TextField('ScoreGroup[Multiplier]', 'z.B. 40 für 40%', 'Faktor'), 2
                )
            ))
        )));
    }

    /**
     * @param $Id
     *
     * @return Stage
     */
    public
    function frontendScoreGroupGradeTypeSelect(
        $Id = null
    ) {

        $Stage = new Stage('Zensuren-Gruppe', 'Zensuren-Typen auswählen');

        $Stage->addButton(new Standard('Zurück', '/Education/Graduation/Gradebook/Score/Group', new ChevronLeft()));

        if (empty($Id)) {
            $Stage->setContent(new Warning('Die Daten konnten nicht abgerufen werden'));
        } else {
            $tblScoreGroup = Gradebook::useService()->getScoreGroupById($Id);
            if (empty($tblScoreGroup)) {
                $Stage->setContent(new Warning('Die Zensuren-Gruppe konnte nicht abgerufen werden'));
            } else {
                $tblScoreGroupGradeTypeListByGroup = Gradebook::useService()->getScoreGroupGradeTypeListByGroup($tblScoreGroup);
                $tblGradeTypeAll = Gradebook::useService()->getGradeTypeAllWhereTest();
                $tblGradeTypeAllByGroup = array();
                if ($tblScoreGroupGradeTypeListByGroup) {
                    /** @var TblScoreGroupGradeTypeList $tblScoreGroupGradeType */
                    foreach ($tblScoreGroupGradeTypeListByGroup as $tblScoreGroupGradeType) {
                        $tblGradeTypeAllByGroup[] = $tblScoreGroupGradeType->getTblGradeType();
                    }
                }

                if (!empty($tblGradeTypeAllByGroup) && $tblGradeTypeAll) {
                    $tblGradeTypeAll = array_udiff($tblGradeTypeAll, $tblGradeTypeAllByGroup,
                        function (TblGradeType $ObjectA, TblGradeType $ObjectB) {

                            return $ObjectA->getId() - $ObjectB->getId();
                        }
                    );
                }

                if ($tblScoreGroupGradeTypeListByGroup) {
                    foreach ($tblScoreGroupGradeTypeListByGroup as &$tblScoreGroupGradeTypeList) {
                        $tblScoreGroupGradeTypeList->Name = $tblScoreGroupGradeTypeList->getTblGradeType()->getName();
                        $tblScoreGroupGradeTypeList->Option =
                            (new \SPHERE\Common\Frontend\Link\Repository\Primary(
                                'Entfernen', '/Education/Graduation/Gradebook/Score/Group/GradeType/Remove',
                                new Minus(), array(
                                'Id' => $tblScoreGroupGradeTypeList->getId()
                            )))->__toString();
                    }
                }

                if ($tblGradeTypeAll) {
                    foreach ($tblGradeTypeAll as $tblGradeType) {
                        $tblGradeType->Option =
                            (new Form(
                                new FormGroup(
                                    new FormRow(array(
                                        new FormColumn(
                                            new TextField('GradeType[Multiplier]', 'Faktor', '', new Quantity()
                                            )
                                            , 7),
                                        new FormColumn(
                                            new Primary('Hinzufügen',
                                                new Plus())
                                            , 5)
                                    ))
                                ), null,
                                '/Education/Graduation/Gradebook/Score/Group/GradeType/Add', array(
                                    'tblScoreGroupId' => $tblScoreGroup->getId(),
                                    'tblGradeTypeId' => $tblGradeType->getId()
                                )
                            ))->__toString();
                    }
                }

                $Stage->setContent(
                    new Layout(array(
                        new LayoutGroup(array(
                            new LayoutRow(array(
                                new LayoutColumn(
                                    new Panel('Zensuren-Gruppe', $tblScoreGroup->getName(), Panel::PANEL_TYPE_INFO),
                                    12
                                ),
                            ))
                        )),
                        new LayoutGroup(array(
                            new LayoutRow(array(
                                new LayoutColumn(array(
                                    new Title('Ausgewählte', 'Zensuren-Typen'),
                                    new TableData($tblScoreGroupGradeTypeListByGroup, null,
                                        array(
                                            'Name' => 'Name',
                                            'Multiplier' => 'Faktor',
                                            'Option' => 'Option'
                                        )
                                    )
                                ), 6
                                ),
                                new LayoutColumn(array(
                                    new Title('Verfügbare', 'Zensuren-Typen'),
                                    new TableData($tblGradeTypeAll, null,
                                        array(
                                            'Name' => 'Name',
                                            'Option' => 'Option'
                                        )
                                    )
                                ), 6
                                )
                            )),
                        )),
                    ))
                );
            }
        }

        return $Stage;
    }

    /**
     * @param null $tblScoreGroupId
     * @param null $tblGradeTypeId
     * @param null $GradeType
     *
     * @return Stage
     */
    public
    function frontendScoreGroupGradeTypeAdd(
        $tblScoreGroupId = null,
        $tblGradeTypeId = null,
        $GradeType = null
    ) {
        $Stage = new Stage('Zensuren-Berechnung', 'Zensuren-Typ einer Zenuseren-Gruppe hinzufügen');

        $tblScoreGroup = Gradebook::useService()->getScoreGroupById($tblScoreGroupId);
        $tblGradeType = Gradebook::useService()->getGradeTypeById($tblGradeTypeId);

        if ($GradeType['Multiplier'] == '') {
            $multiplier = 1;
        } else {
            $multiplier = $GradeType['Multiplier'];
        }

        if ($tblScoreGroup && $tblGradeType) {
            $Stage->setContent(Gradebook::useService()->addScoreGroupGradeTypeList($tblGradeType, $tblScoreGroup,
                $multiplier));
        }

        return $Stage;
    }

    /**
     * @param $Id
     *
     * @return Stage
     */
    public
    function frontendScoreGroupGradeTypeRemove(
        $Id
    ) {
        $Stage = new Stage('Zensuren-Berechnung', 'Zensuren-Typ von einer Zenuseren-Gruppe entfernen');

        $tblScoreGroupGradeTypeList = Gradebook::useService()->getScoreGroupGradeTypeListById($Id);
        if ($tblScoreGroupGradeTypeList) {
            $Stage->setContent(Gradebook::useService()->removeScoreGroupGradeTypeList($tblScoreGroupGradeTypeList));
        }

        return $Stage;
    }

    /**
     * @param $Id
     *
     * @return Stage
     */
    public
    function frontendScoreGroupSelect(
        $Id = null
    ) {

        $Stage = new Stage('Berechnungsvorschrift', 'Zensuren-Gruppen auswählen');

        $Stage->addButton(new Standard('Zurück', '/Education/Graduation/Gradebook/Score', new ChevronLeft()));

        if (empty($Id)) {
            $Stage->setContent(new Warning('Die Daten konnten nicht abgerufen werden'));
        } else {

            $tblScoreCondition = Gradebook::useService()->getScoreConditionById($Id);
            if (!$tblScoreCondition) {
                $Stage->setContent(new Warning('Die Zensuren-Gruppe konnte nicht abgerufen werden'));
            } else {
                $tblScoreConditionGroupListByCondition = Gradebook::useService()->getScoreConditionGroupListByCondition($tblScoreCondition);
                $tblScoreGroupAll = Gradebook::useService()->getScoreGroupAll();
                $tblScoreGroupAllByCondition = array();
                if ($tblScoreConditionGroupListByCondition) {
                    /** @var TblScoreConditionGroupList $tblScoreConditionGroup */
                    foreach ($tblScoreConditionGroupListByCondition as $tblScoreConditionGroup) {
                        $tblScoreGroupAllByCondition[] = $tblScoreConditionGroup->getTblScoreGroup();
                    }
                }

                if (!empty($tblScoreGroupAllByCondition) && $tblScoreGroupAll) {
                    $tblScoreGroupAll = array_udiff($tblScoreGroupAll, $tblScoreGroupAllByCondition,
                        function (TblScoreGroup $ObjectA, TblScoreGroup $ObjectB) {

                            return $ObjectA->getId() - $ObjectB->getId();
                        }
                    );
                }

                if ($tblScoreConditionGroupListByCondition) {
                    foreach ($tblScoreConditionGroupListByCondition as &$tblScoreConditionGroupList) {
                        $tblScoreConditionGroupList->Name = $tblScoreConditionGroupList->getTblScoreGroup()->getName();
                        $tblScoreConditionGroupList->Option =
                            (new \SPHERE\Common\Frontend\Link\Repository\Primary(
                                'Entfernen', '/Education/Graduation/Gradebook/Score/Group/Remove',
                                new Minus(), array(
                                'Id' => $tblScoreConditionGroupList->getId()
                            )))->__toString();
                    }
                }

                if ($tblScoreGroupAll) {
                    foreach ($tblScoreGroupAll as $tblScoreGroup) {
                        $tblScoreGroup->Option =
                            (new Form(
                                new FormGroup(
                                    new FormRow(array(
                                        new FormColumn(
                                            new Primary('Hinzufügen',
                                                new Plus())
                                            , 5)
                                    ))
                                ), null,
                                '/Education/Graduation/Gradebook/Score/Group/Add', array(
                                    'tblScoreGroupId' => $tblScoreGroup->getId(),
                                    'tblScoreConditionId' => $tblScoreCondition->getId()
                                )
                            ))->__toString();
                    }
                }

                $Stage->setContent(
                    new Layout(array(
                        new LayoutGroup(array(
                            new LayoutRow(array(
                                new LayoutColumn(
                                    new Panel('Berechnungsvorschrift', $tblScoreCondition->getName(),
                                        Panel::PANEL_TYPE_INFO), 12
                                ),
                            ))
                        )),
                        new LayoutGroup(array(
                            new LayoutRow(array(
                                new LayoutColumn(array(
                                    new Title('Ausgewählte', 'Zensuren-Gruppen'),
                                    new TableData($tblScoreConditionGroupListByCondition, null,
                                        array(
                                            'Name' => 'Name',
                                            'Option' => 'Option'
                                        )
                                    )
                                ), 6
                                ),
                                new LayoutColumn(array(
                                    new Title('Verfügbare', 'Zensuren-Gruppen'),
                                    new TableData($tblScoreGroupAll, null,
                                        array(
                                            'Name' => 'Name',
                                            'Option' => 'Option'
                                        )
                                    )
                                ), 6
                                )
                            )),
                        )),
                    ))
                );
            }
        }

        return $Stage;
    }

    /**
     * @param null $tblScoreGroupId
     * @param null $tblScoreConditionId
     *
     * @return Stage
     */
    public
    function frontendScoreGroupAdd(
        $tblScoreGroupId = null,
        $tblScoreConditionId = null
    ) {
        $Stage = new Stage('Zensuren-Berechnung', 'Zensuren-Gruppe einer Berechnungsvorschrift hinzufügen');

        $tblScoreGroup = Gradebook::useService()->getScoreGroupById($tblScoreGroupId);
        $tblScoreCondition = Gradebook::useService()->getScoreConditionById($tblScoreConditionId);

        if ($tblScoreGroup && $tblScoreCondition) {
            $Stage->setContent(Gradebook::useService()->addScoreConditionGroupList($tblScoreCondition,
                $tblScoreGroup));
        }

        return $Stage;
    }

    /**
     * @param $Id
     *
     * @return Stage
     */
    public
    function frontendScoreGroupRemove(
        $Id
    ) {
        $Stage = new Stage('Zensuren-Berechnung', 'Zensuren-Gruppe von einer Berechnungsvorschrift entfernen');

        $tblScoreConditionGroupList = Gradebook::useService()->getScoreConditionGroupListById($Id);
        if ($tblScoreConditionGroupList) {
            $Stage->setContent(Gradebook::useService()->removeScoreConditionGroupList($tblScoreConditionGroupList));
        }

        return $Stage;
    }
}
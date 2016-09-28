<?php
/**
 * Created by PhpStorm.
 * User: Kauschke
 * Date: 28.09.2016
 * Time: 13:03
 */

namespace SPHERE\Application\Education\Certificate\GradeInformation;

use SPHERE\Application\Education\Certificate\Prepare\Prepare;
use SPHERE\Application\Education\Graduation\Evaluation\Evaluation;
use SPHERE\Application\Education\Lesson\Division\Division;
use SPHERE\Application\Education\Lesson\Division\Service\Entity\TblDivisionSubject;
use SPHERE\Application\People\Meta\Common\Common;
use SPHERE\Application\People\Meta\Student\Student;
use SPHERE\Application\People\Person\Service\Entity\TblPerson;
use SPHERE\Application\Platform\Gatekeeper\Authorization\Account\Account;
use SPHERE\Common\Frontend\Form\Repository\Button\Primary;
use SPHERE\Common\Frontend\Form\Repository\Field\DatePicker;
use SPHERE\Common\Frontend\Form\Repository\Field\TextField;
use SPHERE\Common\Frontend\Form\Structure\Form;
use SPHERE\Common\Frontend\Form\Structure\FormColumn;
use SPHERE\Common\Frontend\Form\Structure\FormGroup;
use SPHERE\Common\Frontend\Form\Structure\FormRow;
use SPHERE\Common\Frontend\Icon\Repository\Ban;
use SPHERE\Common\Frontend\Icon\Repository\Calendar;
use SPHERE\Common\Frontend\Icon\Repository\ChevronLeft;
use SPHERE\Common\Frontend\Icon\Repository\Edit;
use SPHERE\Common\Frontend\Icon\Repository\Enable;
use SPHERE\Common\Frontend\Icon\Repository\Exclamation;
use SPHERE\Common\Frontend\Icon\Repository\EyeOpen;
use SPHERE\Common\Frontend\Icon\Repository\ListingTable;
use SPHERE\Common\Frontend\Icon\Repository\PlusSign;
use SPHERE\Common\Frontend\Icon\Repository\Save;
use SPHERE\Common\Frontend\Icon\Repository\Select;
use SPHERE\Common\Frontend\Icon\Repository\Setup;
use SPHERE\Common\Frontend\IFrontendInterface;
use SPHERE\Common\Frontend\Layout\Repository\Panel;
use SPHERE\Common\Frontend\Layout\Repository\Title;
use SPHERE\Common\Frontend\Layout\Repository\Well;
use SPHERE\Common\Frontend\Layout\Structure\Layout;
use SPHERE\Common\Frontend\Layout\Structure\LayoutColumn;
use SPHERE\Common\Frontend\Layout\Structure\LayoutGroup;
use SPHERE\Common\Frontend\Layout\Structure\LayoutRow;
use SPHERE\Common\Frontend\Link\Repository\Standard;
use SPHERE\Common\Frontend\Message\Repository\Danger;
use SPHERE\Common\Frontend\Table\Structure\TableData;
use SPHERE\Common\Frontend\Text\Repository\Bold;
use SPHERE\Common\Frontend\Text\Repository\Muted;
use SPHERE\Common\Frontend\Text\Repository\Small;
use SPHERE\Common\Frontend\Text\Repository\Success;
use SPHERE\Common\Frontend\Text\Repository\Warning;
use SPHERE\Common\Frontend\Text\Repository\Warning as WarningText;
use SPHERE\Common\Window\Stage;
use SPHERE\System\Extension\Extension;

/**
 * Class Frontend
 *
 * @package SPHERE\Application\Education\Certificate\GradeInformation
 */
class Frontend extends Extension implements IFrontendInterface
{

    /**
     * @return Stage
     */
    public function frontendSelectDivision()
    {

        $Stage = new Stage('Noteninformation', 'Klasse auswählen');

        $tblPerson = false;
        $tblAccount = Account::useService()->getAccountBySession();
        if ($tblAccount) {
            $tblPersonAllByAccount = Account::useService()->getPersonAllByAccount($tblAccount);
            if ($tblPersonAllByAccount) {
                $tblPerson = $tblPersonAllByAccount[0];
            }
        }

        if ($tblPerson) {
            $tblDivisionList = Division::useService()->getDivisionTeacherAllByTeacher($tblPerson);
        } else {
            $tblDivisionList = false;
        }

        $divisionTable = array();
        if ($tblDivisionList) {
            foreach ($tblDivisionList as $tblDivisionTeacher) {
                $tblDivision = $tblDivisionTeacher->getTblDivision();
                $divisionTable[] = array(
                    'Year' => $tblDivision->getServiceTblYear() ? $tblDivision->getServiceTblYear()->getDisplayName() : '',
                    'Type' => $tblDivision->getTypeName(),
                    'Division' => $tblDivision->getDisplayName(),
                    'Option' => new Standard(
                        '', '/Education/Certificate/GradeInformation/Create', new Select(),
                        array(
                            'DivisionId' => $tblDivision->getId()
                        ),
                        'Auswählen'
                    )
                );
            }
        }

        $Stage->setContent(
            new Layout(array(
                new LayoutGroup(array(
                    new LayoutRow(array(
                        new LayoutColumn(array(
                            new TableData($divisionTable, null, array(
                                'Year' => 'Schuljahr',
                                'Type' => 'Schulart',
                                'Division' => 'Klasse',
                                'Option' => ''
                            ), array(
                                'order' => array(
                                    array('0', 'desc'),
                                    array('1', 'asc'),
                                    array('2', 'asc'),
                                )
                            ))
                        ))
                    ))
                ), new Title(new Select() . ' Auswahl'))
            ))
        );

        return $Stage;
    }

    /**
     * @param null $DivisionId
     * @param null $Data
     *
     * @return Stage|string
     */
    public function frontendGradeInformation($DivisionId = null, $Data = null)
    {

        $Stage = new Stage('Noteninformation', 'Übersicht');
        $Stage->addButton(new Standard(
            'Zurück', '/Education/Certificate/GradeInformation', new ChevronLeft()
        ));

        $tblDivision = Division::useService()->getDivisionById($DivisionId);
        if ($tblDivision) {

            $tableData = array();
            $tblPrepareCertificateAllByDivision = Prepare::useService()->getPrepareAllByDivision($tblDivision, true);
            if ($tblPrepareCertificateAllByDivision) {
                foreach ($tblPrepareCertificateAllByDivision as $tblPrepareCertificate) {

                    $tableData[] = array(
                        'Date' => $tblPrepareCertificate->getDate(),
                        'Name' => $tblPrepareCertificate->getName(),
                        'Status' => $tblPrepareCertificate->isAppointedDateTaskUpdated()
                            ? new Warning(new Exclamation() . ' Stichtagsnotenauftrag wurde aktualisiert')
                            : new Success(new Enable() . ' Keine Fachnoten-Änderungen'),
                        'Option' =>
                            (new Standard(
                                '', '/Education/Certificate/GradeInformation/Edit', new Edit(),
                                array(
                                    'PrepareId' => $tblPrepareCertificate->getId(),
                                )
                                , 'Bearbeiten'
                            ))
                            . (new Standard(
                                '', '/Education/Certificate/GradeInformation/Setting', new Setup(),
                                array(
                                    'PrepareId' => $tblPrepareCertificate->getId(),
                                )
                                , 'Einstellungen'
                            ))
                    );
                }
            }

            $Form = $this->formPrepare()
                ->appendFormButton(new Primary('Speichern', new Save()))
                ->setConfirm('Eventuelle Änderungen wurden noch nicht gespeichert');

            $Stage->setContent(
                new Layout(array(
                        new LayoutGroup(array(
                            new LayoutRow(array(
                                new LayoutColumn(array(
                                    new Panel(
                                        'Klasse',
                                        $tblDivision->getDisplayName(),
                                        Panel::PANEL_TYPE_INFO
                                    )
                                ))
                            ))
                        )),
                        new LayoutGroup(array(
                            new LayoutRow(array(
                                new LayoutColumn(array(
                                    new TableData($tableData, null, array(
                                        'Date' => 'Datum',
                                        'Name' => 'Name',
                                        'Status' => 'Status',
                                        'Option' => ''
                                    ),
                                        array(
                                            'order' => array(
                                                array(0, 'desc')
                                            ),
                                            'columnDefs' => array(
                                                array('type' => 'de_date', 'targets' => 0)
                                            )
                                        )
                                    )
                                ))
                            ))
                        ), new Title(new ListingTable() . ' Übersicht')),
                        new LayoutGroup(array(
                            new LayoutRow(array(
                                new LayoutColumn(
                                    new Well(GradeInformation::useService()->createGradeInformation($Form, $tblDivision,
                                        $Data))
                                )
                            ))
                        ), new Title(new PlusSign() . ' Hinzufügen'))
                    )
                )
            );

            return $Stage;
        } else {

            return $Stage . new Danger('Klasse nicht gefunden.', new Ban());
        }
    }

    /**
     * @return Form
     */
    private function formPrepare()
    {

        return new Form(new FormGroup(array(
            new FormRow(array(
                new FormColumn(
                    new DatePicker('Data[Date]', '', 'Datum', new Calendar()), 3
                ),
                new FormColumn(
                    new TextField('Data[Name]', 'Name', 'Name'), 9
                ),
            )),
        )));
    }

    /**
     * @param null $PrepareId
     * @param null $Data
     *
     * @return Stage|string
     */
    public function frontendEditGradeInformation($PrepareId = null, $Data = null)
    {

        $Stage = new Stage('Noteninformation', 'Bearbeiten');

        $tblPrepare = Prepare::useService()->getPrepareById($PrepareId);
        if ($tblPrepare) {
            $tblDivision = $tblPrepare->getServiceTblDivision();
            if ($tblDivision) {
                $Stage->addButton(new Standard(
                    'Zurück', '/Education/Certificate/GradeInformation/Create', new ChevronLeft(),
                    array('DivisionId' => $tblDivision->getId())
                ));
            }

            if ($Data === null) {
                $Global = $this->getGlobal();
                $Global->POST['Data']['Date'] = $tblPrepare->getDate();
                $Global->POST['Data']['Name'] = $tblPrepare->getName();
                $Global->savePost();
            }

            $Form = $this->formPrepare()
                ->appendFormButton(new Primary('Speichern', new Save()))
                ->setConfirm('Eventuelle Änderungen wurden noch nicht gespeichert');

            $Stage->setContent(
                new Layout(array(
                        new LayoutGroup(array(
                            new LayoutRow(array(
                                new LayoutColumn(array(
                                    new Panel(
                                        'Noteninformation',
                                        $tblPrepare->getName() . ' ' . new Small(new Muted($tblPrepare->getDate())),
                                        Panel::PANEL_TYPE_INFO
                                    ),
                                ), 6),
                                new LayoutColumn(array(
                                    new Panel(
                                        'Klasse',
                                        $tblDivision ? $tblDivision->getDisplayName() : '',
                                        Panel::PANEL_TYPE_INFO
                                    ),
                                ), 6),
                            ))
                        )),
                        new LayoutGroup(array(
                            new LayoutRow(array(
                                new LayoutColumn(
                                    new Well(GradeInformation::useService()->updateGradeInformation($Form, $tblPrepare,
                                        $Data))
                                )
                            ))
                        ), new Title(new PlusSign() . ' Hinzufügen'))
                    )
                )
            );

            return $Stage;
        } else {
            $Stage->addButton(new Standard(
                'Zurück', '/Education/Certificate/GradeInformation', new ChevronLeft()
            ));

            return $Stage . new Danger('Noteninformation nicht gefunden.', new Ban());
        }
    }

    /**
     * @param null $PrepareId
     *
     * @return Stage|string
     */
    public function frontendSetting($PrepareId = null)
    {

        $Stage = new Stage('Noteninformation', 'Klassenübersicht');

        $tblPrepare = Prepare::useService()->getPrepareById($PrepareId);
        if ($tblPrepare) {
            $tblDivision = $tblPrepare->getServiceTblDivision();
            $studentTable = array();
            if ($tblDivision) {
                $Stage->addButton(new Standard(
                    'Zurück', '/Education/Certificate/GradeInformation/Create', new ChevronLeft(),
                    array('DivisionId' => $tblDivision->getId())
                ));

                $tblGradeTypeList = array();
                if ($tblPrepare->getServiceTblBehaviorTask()) {
                    $tblTestAllByTask = Evaluation::useService()->getTestAllByTask($tblPrepare->getServiceTblBehaviorTask());
                    if ($tblTestAllByTask) {
                        foreach ($tblTestAllByTask as $tblTest) {
                            if (($tblGradeType = $tblTest->getServiceTblGradeType())) {
                                if (!isset($tblGradeTypeList[$tblGradeType->getId()])) {
                                    $tblGradeTypeList[$tblGradeType->getId()] = $tblGradeType;
                                }
                            }
                        }
                    }
                }
                $countBehavior = count($tblGradeTypeList);

                $tblStudentList = Division::useService()->getStudentAllByDivision($tblDivision);
                if ($tblStudentList) {
                    foreach ($tblStudentList as $tblPerson) {
                        $tblAddress = $tblPerson->fetchMainAddress();
                        $birthday = '';
                        if (($tblCommon = Common::useService()->getCommonByPerson($tblPerson))) {
                            if ($tblCommon->getTblCommonBirthDates()) {
                                $birthday = $tblCommon->getTblCommonBirthDates()->getBirthday();
                            }
                        }
                        $course = '';
                        if (($tblStudent = Student::useService()->getStudentByPerson($tblPerson))) {
                            $tblTransferType = Student::useService()->getStudentTransferTypeByIdentifier('PROCESS');
                            if ($tblTransferType) {
                                $tblStudentTransfer = Student::useService()->getStudentTransferByType($tblStudent,
                                    $tblTransferType);
                                if ($tblStudentTransfer) {
                                    $tblCourse = $tblStudentTransfer->getServiceTblCourse();
                                    if ($tblCourse) {
                                        $course = $tblCourse->getName();
                                    }
                                }
                            }
                        }

                        // Fächer zählen
                        if ($tblDivision->getServiceTblYear()) {
                            $tblDivisionSubjectList = Division::useService()->getDivisionSubjectAllByPersonAndYear(
                                $tblPerson, $tblDivision->getServiceTblYear()
                            );
                        } else {
                            $tblDivisionSubjectList = false;
                        }
                        if ($tblDivisionSubjectList) {
                            $countSubjects = count($tblDivisionSubjectList);
                        } else {
                            $countSubjects = 0;
                        }

                        // Zensuren zählen
                        if ($tblPrepare->getServiceTblAppointedDateTask()) {
                            $tblPrepareGradeSubjectList = Prepare::useService()->getPrepareGradeAllByPerson(
                                $tblPrepare, $tblPerson, $tblPrepare->getServiceTblAppointedDateTask()->getTblTestType()
                            );
                        } else {
                            $tblPrepareGradeSubjectList = false;
                        }
                        if ($tblPrepareGradeSubjectList) {
                            $countSubjectGrades = count($tblPrepareGradeSubjectList);
                        } else {
                            $countSubjectGrades = 0;
                        }

                        if ($tblPrepare->getServiceTblBehaviorTask()) {
                            $tblPrepareGradeBehaviorList = Prepare::useService()->getPrepareGradeAllByPerson(
                                $tblPrepare, $tblPerson, $tblPrepare->getServiceTblBehaviorTask()->getTblTestType()
                            );
                        } else {
                            $tblPrepareGradeBehaviorList = false;
                        }
                        if ($tblPrepareGradeBehaviorList) {
                            $countBehaviorGrades = count($tblPrepareGradeBehaviorList);
                        } else {
                            $countBehaviorGrades = 0;
                        }

                        if ($tblPrepare->getServiceTblAppointedDateTask()) {
                            $subjectGradesText = $countSubjectGrades . ' von ' . $countSubjects; // . ' Zensuren&nbsp;';
                        } else {
                            $subjectGradesText = 'Kein Stichtagsnotenauftrag ausgewählt';
                        }

                        if ($tblPrepare->getServiceTblBehaviorTask()) {
                            $behaviorGradesText = $countBehaviorGrades . ' von ' . $countBehavior; // . ' Zensuren&nbsp;';
                        } else {
                            $behaviorGradesText = 'Kein Kopfnoten ausgewählt';
                        }

                        $tblPrepareStudent = Prepare::useService()->getPrepareStudentBy($tblPrepare, $tblPerson);
                        if ($tblPrepareStudent) {
                            $tblCertificate = $tblPrepareStudent->getServiceTblCertificate();
                        } else {
                            $tblCertificate = false;
                        }

                        $studentTable[] = array(
                            'Number' => count($studentTable) + 1,
                            'Name' => $tblPerson->getLastFirstName(),
                            'Address' => $tblAddress ? $tblAddress->getGuiTwoRowString() : '',
                            'Birthday' => $birthday,
                            'Course' => $course,
                            'SubjectGrades' => ($countSubjectGrades < $countSubjects || !$tblPrepare->getServiceTblAppointedDateTask()
                                ? new WarningText(new Exclamation() . ' ' . $subjectGradesText)
                                : new Success(new Enable() . ' ' . $subjectGradesText)),
                            'BehaviorGrades' => ($countBehaviorGrades < $countBehavior || !$tblPrepare->getServiceTblBehaviorTask()
                                ? new WarningText(new Exclamation() . ' ' . $behaviorGradesText)
                                : new Success(new Enable() . ' ' . $behaviorGradesText)),
                            'Template' => ($tblCertificate
                                ? new Success(new Enable() . ' ' . $tblCertificate->getName()
                                    . ($tblCertificate->getDescription() ? '<br>' . $tblCertificate->getDescription() : ''))
                                : new WarningText(new Exclamation() . ' Keine Zeugnisvorlage ausgewählt')),
                            'Option' =>
                                (new Standard(
                                    '', '/Education/Certificate/Prepare/Certificate', new Edit(),
                                    array('PrepareId' => $tblPrepare->getId(), 'PersonId' => $tblPerson->getId()),
                                    'Zeugnisvorlage auswählen und zusätzliche Informationen bearbeiten'))
                                . ($tblCertificate
                                    ? (new Standard(
                                        '', '/Education/Certificate/Prepare/Certificate/Show', new EyeOpen(),
                                        array('PrepareId' => $tblPrepare->getId(), 'PersonId' => $tblPerson->getId()),
                                        'Zeugnisvorschau anzeigen'))
                                    : '')
                        );
                    }
                }
            }

            /*
             * Buttons
             */
            $buttonAppointedDateTask = new Standard(
                'Stichtagsnotenauftrag wählen und Fachnoten übernehmen',
                '/Education/Certificate/GradeInformation/Setting/AppointedDateTask',
                new Select(),
                array(
                    'PrepareId' => $tblPrepare->getId()
                ),
                'Stichtagsnotenauftrag auswählen und Fachnoten übernehmen'
            );
            $buttonAppointedDateTaskShowGrades = new Standard(
                'Fachnoten ansehen',
                '/Education/Certificate/GradeInformation/Setting/SubjectGrades',
                new EyeOpen(),
                array(
                    'PrepareId' => $tblPrepare->getId(),
                ),
                'Fachnoten ansehen'
            );
            $buttonBehaviorTask = new Standard(
                'Kopfnotenauftrag wählen',
                '/Education/Certificate/Prepare/BehaviorTask',
                new Select(),
                array(
                    'PrepareId' => $tblPrepare->getId()
                ),
                'Kopfnotenauftrag auswählen'
            );
            $buttonBehaviorTaskShowGrades = new Standard(
                'Kopfnoten ansehen und Kopfnoten festlegen',
                '/Education/Certificate/Prepare/BehaviorGrades',
                new EyeOpen(),
                array(
                    'PrepareId' => $tblPrepare->getId(),
                ),
                'Kopfnoten ansehen und Kopfnoten festlegen'
            );
            $buttonUpdateAppointedDateTask = new Standard(
                'Aktualisieren',
                '/Education/Certificate/GradeInformation/Setting/AppointedDateTask/Update',
                new Select(),
                array(
                    'PrepareId' => $tblPrepare->getId(),
                ),
                'Fachnoten aus dem Stichtagsnotenauftrag aktualisieren'
            );

            $Stage->setContent(
                new Layout(array(
                    new LayoutGroup(array(
                        new LayoutRow(array(
                            new LayoutColumn(array(
                                new Panel(
                                    'Noteninformation',
                                    array(
                                        $tblPrepare->getName() . ' ' . new Small(new Muted($tblPrepare->getDate()))
                                    ),
                                    Panel::PANEL_TYPE_INFO
                                ),
                            ), 6),
                            new LayoutColumn(array(
                                new Panel(
                                    'Klasse',
                                    $tblDivision->getDisplayName(),
                                    Panel::PANEL_TYPE_INFO
                                ),
                            ), 6),
                        )),
                    )),
                    new LayoutGroup(array(
                        new LayoutRow(array(
                            new LayoutColumn(array(
                                $tblPrepare->isAppointedDateTaskUpdated()
                                    ? new Warning('Die Fachnoten im Stichtagsnotenauftrag wurden aktualisiert.',
                                    new Exclamation()) : null,
                            )),
                            new LayoutColumn(array(
                                new Panel(
                                    'Stichtagsnotenauftrag',
                                    array(
                                        $tblPrepare->getServiceTblAppointedDateTask()
                                            ? $tblPrepare->getServiceTblAppointedDateTask()->getName()
                                            . ' ' . $tblPrepare->getServiceTblAppointedDateTask()->getDate()
                                            : new Exclamation() . ' Kein Stichtagsnotenauftrag ausgewählt',
                                        $buttonAppointedDateTask
                                        . ($tblPrepare->isAppointedDateTaskUpdated() ? $buttonUpdateAppointedDateTask : '')
                                        . ($tblPrepare->getServiceTblAppointedDateTask()
                                            ? $buttonAppointedDateTaskShowGrades
                                            : '')
                                    ),
                                    $tblPrepare->getServiceTblAppointedDateTask()
                                        ? Panel::PANEL_TYPE_SUCCESS
                                        : Panel::PANEL_TYPE_WARNING
                                ),
                            ), 6),
                            new LayoutColumn(array(
                                new Panel(
                                    'Kopfnotenauftrag',
                                    array(
                                        $tblPrepare->getServiceTblBehaviorTask()
                                            ? $tblPrepare->getServiceTblBehaviorTask()->getName()
                                            . ' ' . $tblPrepare->getServiceTblBehaviorTask()->getDate()
                                            : 'Kein Kopfnotenauftrag ausgewählt',
                                        $buttonBehaviorTask .
                                        ($tblPrepare->getServiceTblBehaviorTask()
                                            ? $buttonBehaviorTaskShowGrades
                                            : '')
                                    ),
                                    $tblPrepare->getServiceTblBehaviorTask()
                                        ? Panel::PANEL_TYPE_SUCCESS
                                        : Panel::PANEL_TYPE_WARNING
                                ),
                            ), 6),
                        )),
                    ), new Title('Notenaufträge')),
                    new LayoutGroup(array(
                        new LayoutRow(array(
                            new LayoutColumn(array(
                                '<br>',
                                new TableData($studentTable, null, array(
                                    'Number' => '#',
                                    'Name' => 'Name',
                                    'Address' => 'Adresse',
                                    'Birthday' => 'Geburts&shy;datum',
                                    'Course' => 'Bildungs&shy;gang',
                                    'SubjectGrades' => 'Fachnoten',
                                    'BehaviorGrades' => 'Kopfnoten',
                                    'Template' => 'Vorlage',
                                    'Option' => ''
                                ), array(
                                    'order' => array(
                                        array('0', 'asc'),
                                    ),
                                    "paging" => false, // Deaktivieren Blättern
                                    "iDisplayLength" => -1,    // Alle Einträge zeigen
                                ))
                            ))
                        ))
                    ), new Title('Übersicht'))
                ))
            );

            return $Stage;
        } else {
            $Stage->addButton(new Standard(
                'Zurück', '/Education/Certificate/GradeInformation', new ChevronLeft()
            ));

            return $Stage . new Danger('Noteninformation nicht gefunden.', new Ban());
        }
    }

    /**
     * @param null $PrepareId
     *
     * @return Stage|string
     */
    public function frontendAppointedDateTask($PrepareId = null)
    {

        $Stage = new Stage('Stichtagsnotenauftrag', 'Auswählen');
        $Stage->addButton(new Standard(
            'Zurück', '/Education/Certificate/GradeInformation/Setting', new ChevronLeft(), array(
                'PrepareId' => $PrepareId
            )
        ));

        $Stage->setMessage(new WarningText(new Bold(new Exclamation() . ' Hinweis:')
            . ' Bei der Auswahl des Stichtagsnotenauftrags werden alle Zensuren dieses Auftrags übernommen. Dieser Vorgang kann
             einen Augenblick dauern.'));

        $tblPrepare = Prepare::useService()->getPrepareById($PrepareId);
        if ($tblPrepare) {
            $tblDivision = $tblPrepare->getServiceTblDivision();

            $tblTestType = Evaluation::useService()->getTestTypeByIdentifier('APPOINTED_DATE_TASK');
            $tblTaskList = Evaluation::useService()->getTaskAllByDivision($tblDivision, $tblTestType);
            $tableContent = array();
            if ($tblTaskList) {
                foreach ($tblTaskList as $tblTask) {
                    if ($tblPrepare->getServiceTblAppointedDateTask()) {
                        $isChosen = $tblTask->getId() == $tblPrepare->getServiceTblAppointedDateTask()->getId();
                    } else {
                        $isChosen = false;
                    }
                    $tableContent[] = array(
                        'Date' => $isChosen ? new Bold($tblTask->getDate()) : $tblTask->getDate(),
                        'Name' => $isChosen ? new Bold($tblTask->getName()) : $tblTask->getName(),
                        'Period' => $tblTask->getServiceTblPeriod()
                            ? $tblTask->getServiceTblPeriod()->getDisplayName()
                            : 'Gesamtes Schuljahr',
                        'Option' => $isChosen
                            ? ''
                            : new Standard(
                                '',
                                '/Education/Certificate/GradeInformation/Setting/AppointedDateTask/Select',
                                new Select(),
                                array(
                                    'PrepareId' => $tblPrepare->getId(),
                                    'TaskId' => $tblTask->getId()
                                ),
                                'Auswählen'
                            )
                    );
                }
            }

            $Stage->setContent(
                new Layout(array(
                    new LayoutGroup(array(
                        new LayoutRow(array(
                            new LayoutColumn(array(
                                new Panel(
                                    'Noteninformation',
                                    $tblPrepare->getName() . ' ' . new Small(new Muted($tblPrepare->getDate())),
                                    Panel::PANEL_TYPE_INFO
                                ),
                            ), 6),
                            new LayoutColumn(array(
                                new Panel(
                                    'Klasse',
                                    $tblDivision->getDisplayName(),
                                    Panel::PANEL_TYPE_INFO
                                ),
                            ), 6),
                            new LayoutColumn(array(
                                new Panel(
                                    'Stichtagsnotenauftrag',
                                    array(
                                        $tblPrepare->getServiceTblAppointedDateTask()
                                            ? $tblPrepare->getServiceTblAppointedDateTask()->getName()
                                            . ' ' . $tblPrepare->getServiceTblAppointedDateTask()->getDate()
                                            : new Exclamation() . ' Kein Stichtagsnotenauftrag ausgewählt',
                                    ),
                                    $tblPrepare->getServiceTblAppointedDateTask()
                                        ? Panel::PANEL_TYPE_SUCCESS
                                        : Panel::PANEL_TYPE_WARNING
                                ),
                            ), 12),
                            new LayoutColumn(array(
                                $tblTaskList
                                    ? new TableData($tableContent, null, array(
                                    'Date' => 'Stichtag',
                                    'Name' => 'Name',
                                    'Period' => 'Zeitraum',
                                    'Option' => ''
                                ),
                                    array(
                                        'order' => array(
                                            array(0, 'desc')
                                        ),
                                        'columnDefs' => array(
                                            array('type' => 'de_date', 'targets' => 0)
                                        ),
                                        "paging" => false, // Deaktivieren Blättern
                                        "iDisplayLength" => -1,    // Alle Einträge zeigen
                                    )
                                )
                                    : new Warning('Für diese Klasse sind keine Notenaufträge vorhanden.',
                                    new Exclamation())
                            )),
                        ))
                    ))
                ))
            );

            return $Stage;
        } else {

            return $Stage . new Danger('Noteninformation nicht gefunden.', new Ban());
        }
    }

    /**
     * @param null $PrepareId
     * @param null $TaskId
     *
     * @return Stage|string
     */
    public function frontendSelectAppointedDateTask($PrepareId = null, $TaskId = null)
    {

        $Stage = new Stage('Stichtagsnotenauftrag', 'Auswählen');
        $Stage->addButton(new Standard(
            'Zurück', '/Education/Certificate/GradeInformation/Setting', new ChevronLeft(), array(
                'PrepareId' => $PrepareId
            )
        ));

        $Stage->setMessage(new \SPHERE\Common\Frontend\Text\Repository\Warning(new Bold(new Exclamation() . ' Hinweis:')
            . ' Bei der Auswahl des Stichtagsnotenauftrags werden alle Zensuren dieses Auftrags übernommen. Dieser Vorgang kann
             einen Augenblick dauern.'));

        $tblPrepare = Prepare::useService()->getPrepareById($PrepareId);
        $tblTask = Evaluation::useService()->getTaskById($TaskId);

        if ($tblPrepare && $tblTask) {
            $tblDivision = $tblPrepare->getServiceTblDivision();

            $Stage->setContent(
                new Layout(array(
                    new LayoutGroup(array(
                        new LayoutRow(array(
                            new LayoutColumn(array(
                                new Panel(
                                    'Zeugnisvorbereitung',
                                    $tblPrepare->getName() . ' ' . new Small(new Muted($tblPrepare->getDate())),
                                    Panel::PANEL_TYPE_INFO
                                ),
                            ), 6),
                            new LayoutColumn(array(
                                new Panel(
                                    'Klasse',
                                    $tblDivision->getDisplayName(),
                                    Panel::PANEL_TYPE_INFO
                                ),
                            ), 6),
                            new LayoutColumn(array(
                                GradeInformation::useService()->updatePrepareSetAppointedDateTask(
                                    $tblPrepare, $tblTask
                                )
                            )),
                        ))
                    ))
                ))
            );

            return $Stage;
        } else {

            return $Stage . new Danger('Noteninformation oder Notenauftrag nicht gefunden.', new Ban());
        }
    }

    /**
     * @param null $PrepareId
     *
     * @return Stage|string
     */
    public function frontendUpdateAppointedDateTask($PrepareId = null)
    {

        $Stage = new Stage('Stichtagsnotenauftrag', 'Aktualisieren');

        $tblPrepare = Prepare::useService()->getPrepareById($PrepareId);
        if ($tblPrepare && $tblPrepare->getServiceTblAppointedDateTask()) {
            $tblDivision = $tblPrepare->getServiceTblDivision();

            $Stage->setContent(
                new Layout(array(
                    new LayoutGroup(array(
                        new LayoutRow(array(
                            new LayoutColumn(array(
                                new Panel(
                                    'Noteninformation',
                                    $tblPrepare->getName() . ' ' . new Small(new Muted($tblPrepare->getDate())),
                                    Panel::PANEL_TYPE_INFO
                                ),
                            ), 6),
                            new LayoutColumn(array(
                                new Panel(
                                    'Klasse',
                                    $tblDivision->getDisplayName(),
                                    Panel::PANEL_TYPE_INFO
                                ),
                            ), 6),
                            new LayoutColumn(array(
                                GradeInformation::useService()->updatePrepareUpdateAppointedDateTask($tblPrepare)
                            )),
                        ))
                    ))
                ))
            );

            return $Stage;
        } else {

            return $Stage . new Danger('Zeugnisvorbereitung nicht gefunden.', new Ban());
        }
    }

    /**
     * @param null $PrepareId
     *
     * @return Stage|string
     */
    public function frontendSubjectGrades($PrepareId = null)
    {

        $Stage = new Stage('Fachnoten', 'Übersicht');
        $Stage->addButton(new Standard(
            'Zurück', '/Education/Certificate/GradeInformation/Setting', new ChevronLeft(), array(
                'PrepareId' => $PrepareId
            )
        ));

        $tblPrepare = Prepare::useService()->getPrepareById($PrepareId);
        if ($tblPrepare) {
            $tblTestType = Evaluation::useService()->getTestTypeByIdentifier('APPOINTED_DATE_TASK');

            $tableData = array();
            $tableHeader['Number'] = '#';
            $tableHeader['Student'] = 'Schüler';
            if (($tblDivision = $tblPrepare->getServiceTblDivision())
                && ($tblYear = $tblDivision->getServiceTblYear())
            ) {
                $tblStudentAllByDivision = Division::useService()->getStudentAllByDivision($tblDivision);
                if ($tblStudentAllByDivision) {
                    foreach ($tblStudentAllByDivision as $tblPerson) {
                        $tableData[$tblPerson->getId()]['Number'] = count($tableData) +1;
                        $tableData[$tblPerson->getId()]['Student'] = $tblPerson->getLastFirstName();
                        $tblDivisionSubjectList = Division::useService()->getDivisionSubjectAllByPersonAndYear(
                            $tblPerson, $tblYear
                        );
                        if ($tblDivisionSubjectList) {
                            /** @var TblDivisionSubject $tblDivisionSubject */
                            foreach ($tblDivisionSubjectList as $tblDivisionSubject) {
                                $tblPrepareGrade = Prepare::useService()->getPrepareGradeBySubject(
                                    $tblPrepare,
                                    $tblPerson,
                                    $tblDivisionSubject->getTblDivision(),
                                    $tblDivisionSubject->getServiceTblSubject(),
                                    $tblTestType
                                );
                                if (!isset($tableHeader['Subject' . $tblDivisionSubject->getServiceTblSubject()])) {
                                    $tableHeader['Subject' . $tblDivisionSubject->getServiceTblSubject()]
                                        = $tblDivisionSubject->getServiceTblSubject()->getAcronym()
                                        . ($tblDivision->getId() == $tblDivisionSubject->getTblDivision()->getId()
                                            ? '' : ' (' . $tblDivisionSubject->getTblDivision()->getDisplayName() . ')');
                                }
                                $tableData[$tblPerson->getId()]['Subject' . $tblDivisionSubject->getServiceTblSubject()]
                                    = $tblPrepareGrade ? $tblPrepareGrade->getGrade()
                                    : new WarningText('f');
                            }
                        }
                    }

                    // leere Zellen setzen
                    foreach ($tableHeader as $key => $value) {
                        /** @var TblPerson $tblPerson */
                        foreach ($tblStudentAllByDivision as $tblPerson) {
                            if (!isset($tableData[$tblPerson->getId()][$key])) {
                                $tableData[$tblPerson->getId()][$key] = '';
                            }
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
                                    'Noteninformation',
                                    $tblPrepare->getName() . ' ' . new Small(new Muted($tblPrepare->getDate())),
                                    Panel::PANEL_TYPE_INFO
                                ),
                            ), 6),
                            new LayoutColumn(array(
                                new Panel(
                                    'Klasse',
                                    $tblDivision->getDisplayName(),
                                    Panel::PANEL_TYPE_INFO
                                ),
                            ), 6),
                            new LayoutColumn(array(
                                new TableData(
                                    $tableData,
                                    null,
                                    $tableHeader,
                                    null
                                )
                            )),
                        ))
                    ))
                ))
            );

            return $Stage;
        } else {

            return $Stage . new Danger('Noteninformation oder Person nicht gefunden.', new Ban());
        }
    }
}
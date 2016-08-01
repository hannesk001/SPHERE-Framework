<?php
/**
 * Created by PhpStorm.
 * User: Kauschke
 * Date: 26.07.2016
 * Time: 13:33
 */

namespace SPHERE\Application\Education\Certificate\PrintCertificate;

use SPHERE\Application\Education\Certificate\Prepare\Prepare;
use SPHERE\Application\People\Person\Person;
use SPHERE\Common\Frontend\Icon\Repository\Ban;
use SPHERE\Common\Frontend\Icon\Repository\ChevronLeft;
use SPHERE\Common\Frontend\Icon\Repository\Disable;
use SPHERE\Common\Frontend\Icon\Repository\Download;
use SPHERE\Common\Frontend\Icon\Repository\Exclamation;
use SPHERE\Common\Frontend\Icon\Repository\Ok;
use SPHERE\Common\Frontend\Icon\Repository\Question;
use SPHERE\Common\Frontend\IFrontendInterface;
use SPHERE\Common\Frontend\Layout\Repository\Panel;
use SPHERE\Common\Frontend\Layout\Structure\Layout;
use SPHERE\Common\Frontend\Layout\Structure\LayoutColumn;
use SPHERE\Common\Frontend\Layout\Structure\LayoutGroup;
use SPHERE\Common\Frontend\Layout\Structure\LayoutRow;
use SPHERE\Common\Frontend\Link\Repository\External;
use SPHERE\Common\Frontend\Link\Repository\Standard;
use SPHERE\Common\Frontend\Message\Repository\Danger;
use SPHERE\Common\Frontend\Message\Repository\Warning;
use SPHERE\Common\Frontend\Table\Structure\TableData;
use SPHERE\Common\Window\Redirect;
use SPHERE\Common\Window\Stage;
use SPHERE\System\Extension\Extension;

class Frontend extends Extension implements IFrontendInterface
{

    public function frontendPrintCertificate()
    {

        $Stage = new Stage('Zeugnis', 'Übersicht (nicht gedruckte Zeugnisse)');

        // freigebene und nicht gedruckte Zeugnisse
        $tableContent = array();
        $tblPrepareStudentList = Prepare::useService()->getPrepareStudentAllWhere(true, false);
        if ($tblPrepareStudentList) {
            foreach ($tblPrepareStudentList as $tblPrepareStudent) {
                if (($tblPerson = $tblPrepareStudent->getServiceTblPerson())) {
                    $tblPrepare = $tblPrepareStudent->getTblPrepareCertificate();
                    $tblDivision = $tblPrepare->getServiceTblDivision();
                    $tblYear = $tblDivision ? $tblDivision->getServiceTblYear() : false;


                    $tableContent[] = array(
                        'Year' => $tblYear ? $tblYear->getDisplayName() : '',
                        'Division' => $tblDivision ? $tblDivision->getDisplayName() : '',
                        'Student' => $tblPerson->getLastFirstName(),
                        'Option' => new Standard(
                            'Zeugnis drucken',
                            '/Education/Certificate/PrintCertificate/Confirm',
                            new Download(),
                            array(
                                'PrepareId' => $tblPrepare->getId(),
                                'PersonId' => $tblPerson->getId(),
                            ), false)
                    );
                }
            }
        }

        $Stage->setContent(
            new Layout(array(
                new LayoutGroup(array(
                    new LayoutRow(array(
                        new LayoutColumn(array(
                            empty($tableContent)
                                ? new Warning('Keine Zeugnisse zum Druck verfügbar', new Exclamation())
                                : new TableData(
                                $tableContent,
                                null,
                                array(
                                    'Year' => 'Schuljahr',
                                    'Division' => 'Klasse',
                                    'Student' => 'Schüler',
                                    'Option' => ''
                                ),
                                array(
                                    'order' => array(
                                        array(0, 'desc'),
                                        array(1, 'asc'),
                                        array(2, 'asc'),
                                    ),
                                )
                            )
                        ))
                    ))
                ))
            ))
        );

        return $Stage;
    }

    /**
     * @param null $PrepareId
     * @param null $PersonId
     *
     * @return Stage
     */
    public function frontendConfirmPrintCertificate($PrepareId = null, $PersonId = null)
    {

        $Stage = new Stage('Zeugnis', 'Drucken und revisionssicher abspeichern');

        if (($tblPrepare = Prepare::useService()->getPrepareById($PrepareId))
            && ($tblPerson = Person::useService()->getPersonById($PersonId))
            && ($tblPrepareStudent = Prepare::useService()->getPrepareStudentBy($tblPrepare, $tblPerson))
        ) {
            $Stage->addButton(new Standard(
                'Zurück', '/Education/Certificate/PrintCertificate', new ChevronLeft()
            ));


            $Stage->setContent(
                new Layout(new LayoutGroup(new LayoutRow(new LayoutColumn(array(
                    new Panel(
                        'Schüler',
                        $tblPerson->getLastFirstName(),
                        Panel::PANEL_TYPE_INFO
                    ),
                    new Panel(
                        new Question() . ' Dieses Zeugnis wirklich drucken und revisionssicher abspeichern?',
                        array(
                            $tblPerson->getLastFirstName()
                            . ($tblPrepare->getServiceTblDivision()
                                ? ' ' . $tblPrepare->getServiceTblDivision()->getDisplayName()
                                : '')
                            . $tblPrepare->getName()
                        ),
                        Panel::PANEL_TYPE_DANGER,
                        (new External(
                            'Ja',
                            '/Api/Education/Certificate/Generator/Create',
                            new Ok(),
                            array(
                                'PrepareId' => $tblPrepare->getId(),
                                'PersonId' => $tblPerson->getId(),
                            ),
                            'Zeugnis drucken und revisionssicher abspeichern'))
                            ->setRedirect('/Education/Certificate/PrintCertificate', 3)
                        . new Standard(
                            'Nein', '/Education/Certificate/PrintCertificate', new Disable()
                        )
                    ),
                )))))
            );
        } else {
            $Stage->setContent(
                new Layout(new LayoutGroup(array(
                    new LayoutRow(new LayoutColumn(array(
                        new Danger(new Ban() . ' Das Zeugnis konnte nicht gefunden werden'),
                        new Redirect('/Education/Certificate/PrintCertificate', Redirect::TIMEOUT_ERROR)
                    )))
                )))
            );
        }

        return $Stage;
    }

}
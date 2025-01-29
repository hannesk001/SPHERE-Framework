<?php

namespace SPHERE\Application\Education\Graduation\Grade;

use SPHERE\Application\Education\Graduation\Gradebook\MinimumGradeCount\SelectBoxItem;
use SPHERE\Application\Education\Lesson\DivisionCourse\DivisionCourse;
use SPHERE\Application\Education\Lesson\Subject\Service\Entity\TblSubject;
use SPHERE\Application\Education\Lesson\Subject\Subject;
use SPHERE\Application\Education\School\Type\Type;
use SPHERE\Application\People\Person\Person;
use SPHERE\Common\Frontend\Form\Repository\Field\NumberField;
use SPHERE\Common\Frontend\Form\Repository\Field\RadioBox;
use SPHERE\Common\Frontend\Form\Repository\Field\SelectBox;
use SPHERE\Common\Frontend\Form\Repository\Field\TextArea;
use SPHERE\Common\Frontend\Form\Repository\Field\TextField;
use SPHERE\Common\Frontend\Form\Structure\Form;
use SPHERE\Common\Frontend\Form\Structure\FormColumn;
use SPHERE\Common\Frontend\Form\Structure\FormGroup;
use SPHERE\Common\Frontend\Form\Structure\FormRow;
use SPHERE\Common\Frontend\Icon\Repository\Disable;
use SPHERE\Common\Frontend\Icon\Repository\Filter;
use SPHERE\Common\Frontend\Icon\Repository\Plus;
use SPHERE\Common\Frontend\Icon\Repository\Save;
use SPHERE\Common\Frontend\Layout\Repository\Panel;
use SPHERE\Common\Frontend\Layout\Repository\PullRight;
use SPHERE\Common\Frontend\Layout\Repository\Title;
use SPHERE\Common\Frontend\Layout\Repository\Well;
use SPHERE\Common\Frontend\Layout\Structure\Layout;
use SPHERE\Common\Frontend\Layout\Structure\LayoutColumn;
use SPHERE\Common\Frontend\Layout\Structure\LayoutGroup;
use SPHERE\Common\Frontend\Layout\Structure\LayoutRow;
use SPHERE\Common\Frontend\Link\Repository\Primary;
use SPHERE\Common\Frontend\Link\Repository\Standard;
use SPHERE\Common\Frontend\Table\Structure\TableData;
use SPHERE\Common\Frontend\Text\Repository\Bold;
use SPHERE\Common\Frontend\Text\Repository\Danger as DangerText;
use SPHERE\Common\Window\Stage;

class FrontendSkill extends FrontendScoreType
{
    public function getSkills(?TblSubject $tblSubject)
    {
        $dataList = array();
        if (is_null($tblSubject)) {
            // Überfachliche Kompetenz
            $dataList[] = array('Subject' => 'Überfach', 'Levels' => '3 (GS), 4 (GS)', 'Category' => 'Selbstkonzept', 'Skill' => 'Du hast Zutrauen zu dir und deinem Handeln, entwickelst eine eigene Meinung, triffst Entscheidungen und vertrittst diese.');
            $dataList[] = array('Subject' => 'Überfach', 'Levels' => '3 (GS), 4 (GS)', 'Category' => 'Motivation', 'Skill' => 'Du zeigst Neugier und Interesse und nimmst aufmerksam am Unterricht teil.');
            $dataList[] = array('Subject' => 'Überfach', 'Levels' => '3 (GS), 4 (GS)', 'Category' => 'Motivation', 'Skill' => 'Du arbeitest ausdauernd und konzentriert.');
            $dataList[] = array('Subject' => 'Überfach', 'Levels' => '3 (GS), 4 (GS)', 'Category' => 'Lernmethodische Kompetenzen', 'Skill' => 'Du planst und organisierst deine Arbeit sowie dein Arbeitsmaterial.');
            $dataList[] = array('Subject' => 'Überfach', 'Levels' => '3 (GS), 4 (GS)', 'Category' => 'Sozial-kommunikative Kompetenzen', 'Skill' => 'Du arbeitest mit anderen zusammen, hältst Regeln ein, nimmst Rücksicht, hilfst anderen und löst Konflikte.');
        } elseif ($tblSubject->getAcronym() == 'DEU') {
            // DEU
            $dataList[] = array('Subject' => 'DEU', 'Levels' => '3 (GS), 4 (GS)', 'Category' => 'Sprechen und Zuhören', 'Skill' => 'Du hörst aufmerksam zu und entnimmst wichtige Informationen aus Gesprächen und Hörtexten.');
            $dataList[] = array('Subject' => 'DEU', 'Levels' => '3 (GS), 4 (GS)', 'Category' => 'Sprechen und Zuhören', 'Skill' => 'Du gestaltest deine Redebeiträge sprachlich angemessen und passend zum Thema.');
            $dataList[] = array('Subject' => 'DEU', 'Levels' => '3 (GS), 4 (GS)', 'Category' => 'Lesen', 'Skill' => 'Du liest Texte flüssig und sinngestaltend.');
            $dataList[] = array('Subject' => 'DEU', 'Levels' => '3 (GS), 4 (GS)', 'Category' => 'Lesen', 'Skill' => 'Du entnimmst Texten und anderen Medien gezielt Informationen.');
            $dataList[] = array('Subject' => 'DEU', 'Levels' => '3 (GS), 4 (GS)', 'Category' => 'Texte schreiben', 'Skill' => 'Du schreibst flüssig in einer lesbaren Handschrift.');
            $dataList[] = array('Subject' => 'DEU', 'Levels' => '3 (GS), 4 (GS)', 'Category' => 'Texte schreiben', 'Skill' => 'Du planst, schreibst und überarbeitest Texte dem Schreibanlass angemessen.');
            $dataList[] = array('Subject' => 'DEU', 'Levels' => '3 (GS), 4 (GS)', 'Category' => 'Richtig schreiben', 'Skill' => 'Du schreibst den erarbeiteten Wortschatz richtig.');
            $dataList[] = array('Subject' => 'DEU', 'Levels' => '3 (GS), 4 (GS)', 'Category' => 'Richtig schreiben', 'Skill' => 'Du setzt die Satz- und Redezeichen richtig.');
            $dataList[] = array('Subject' => 'DEU', 'Levels' => '3 (GS), 4 (GS)', 'Category' => 'Richtig schreiben', 'Skill' => 'Du wendest Rechtschreibstrategien selbstständig an.');
            $dataList[] = array('Subject' => 'DEU', 'Levels' => '3 (GS), 4 (GS)', 'Category' => 'Sprache untersuchen', 'Skill' => 'Du unterscheidest die erarbeiteten Satzglieder.');
            $dataList[] = array('Subject' => 'DEU', 'Levels' => '3 (GS), 4 (GS)', 'Category' => 'Sprache untersuchen', 'Skill' => 'Du bildest und verwendest die erarbeiteten Zeitformen richtig.');
        } elseif ($tblSubject->getAcronym() == 'MA') {
            // MA
            $dataList[] = array('Subject' => 'MA', 'Levels' => '3 (GS), 4 (GS)', 'Category' => '', 'Skill' => 'Du stellst mathematisch Zusammenhänge verständlich dar und beschreibst Lösungswege.');
            $dataList[] = array('Subject' => 'MA', 'Levels' => '3 (GS), 4 (GS)', 'Category' => '', 'Skill' => 'Du beherrschst die Grundaufgaben des Kopfrechnens im Zahlenraum bis 100.');
            $dataList[] = array('Subject' => 'MA', 'Levels' => '3 (GS), 4 (GS)', 'Category' => '', 'Skill' => 'Du löst Sachaufgaben und Aufgaben mit Größen (Geld, Gewicht, Länge und Zeit).');
            $dataList[] = array('Subject' => 'MA', 'Levels' => '3 (GS), 4 (GS)', 'Category' => '', 'Skill' => 'Du benennst räumliche Beziehungen, geometrische Formen und Körper sowie deren Abbildungen und stellst sie dar.');
        }

        return $dataList;
    }

    public function getSkillCategories(): array
    {
        $dataList[] = array('Id' => 0, 'Name' => 'Selbstkonzept', 'Description' => '');
        $dataList[] = array('Id' => 2, 'Name' => 'Motivation', 'Description' => '');
        $dataList[] = array('Id' => 3, 'Name' => 'Lernmethodische Kompetenzen', 'Description' => '');
        $dataList[] = array('Id' => 4, 'Name' => 'Sozial-kommunikative Kompetenzen', 'Description' => '');
        $dataList[] = array('Id' => 5, 'Name' => 'Sprechen und Zuhören', 'Description' => '');
        $dataList[] = array('Id' => 6, 'Name' => 'Lesen', 'Description' => '');

        return $dataList;
    }

    public function frontendSkill(): Stage
    {
        $stage = new Stage('Kompetenzen', 'Übersicht');
        $dataList = $this->getSkills(null);
        $dataList = array_merge($dataList, $this->getSkills(Subject::useService()->getSubjectByAcronym('DEU')));
        $dataList = array_merge($dataList, $this->getSkills(Subject::useService()->getSubjectByAcronym('MA')));

        $columns = array(
            'Subject' => 'Fach',
            'Category' => 'Kategorie',
            'Skill' => 'Kompetenz',
            'Levels' => 'Klassenstufen',
        );
        $interactive = array(
            'order' => array(
                array(0, 'asc'),
                array(1, 'asc'),
//                array(2, 'asc')
            ),
//            'columnDefs' => array(
//                array('type' => 'de_date', 'targets' => array(0, 1)),
//                array('orderable' => false, 'width' => '1%', 'targets' => -1),
//            ),
            'responsive' => false
        );
        $stage->setContent(
            $this->getFilter()
                . (new Primary('Kompetenz hinzufügen', '/Education/Graduation/Grade/Skill/Add', new Plus()))
                . new TableData($dataList, null, $columns, $interactive)
        );

        return  $stage;
    }

    private function getFilter(): string
    {
        return new Panel(
            'Filter',
            new Layout (new LayoutGroup(array(
                new LayoutRow(array(
                    new LayoutColumn(
                        new SelectBox('Data[Type]', 'Schulart', array('Name' => Type::useService()->getTypeAll())), 4
                    ),
                    new LayoutColumn(
                        new SelectBox('Data[Subject]', 'Fach', array('{{ Acronym }} - {{ Name }}' => Subject::useService()->getSubjectAll())), 4
                    ),
                    new LayoutColumn(
                        new SelectBox('Data[Category]', 'Kategorie', array('{{ Name }}' => array())), 4
                    ),
                )),
                new LayoutRow(array(
                    new LayoutColumn(
                        (new Primary('Filtern', '', new Filter()))
                    ),
                )),
            ))),
            Panel::PANEL_TYPE_INFO
        );
    }

    public function frontendSkillAdd(): Stage
    {
        $stage = new Stage('Kompetenz', 'Hinzufügen');

        $stage->setContent(new Well($this->formSkill()));

        return  $stage;
    }

    public function formSkill(): Form
    {
        $categories = array();
        $tempList = $this->getSkillCategories();
        foreach ($tempList as $category) {
            $categories[] = new SelectBoxItem($category['Id'], $category['Name']);
        }

        return new Form(new FormGroup(array(
            new FormRow(
                new FormColumn(
                    new SelectBox('Data[Subject]', 'Fach', array('{{ Acronym }} - {{ Name }}' => Subject::useService()->getSubjectAll()))
                )
            ),
            new FormRow(array(
                new FormColumn(
                    new SelectBox('Data[CategorySelect]', 'Kategorie auswählen', array('{{ Name }}' => $categories))
                    , 6),
                new FormColumn(
                    new TextField('Data[CategoryNew]', '', 'oder neue Kategorie anlegen')
                    , 6),
            )),
            new FormRow(
                new FormColumn(
                    (new TextArea('Data[Skill]', 'Kompetenz', 'Kompetenz'))->setRequired(),
                )
            ),
            new FormRow(
                new FormColumn(array(
                    new Panel(
                        'Klassenstufen'  . new DangerText('*'),
                        new Layout(new LayoutGroup(new LayoutRow($this->getLevelColumns()))),
                        Panel::PANEL_TYPE_INFO
                    )
                )),
            ),
            new FormRow(array(
                new FormColumn(array(
                    (new Primary('Speichern', '/Education/Graduation/Grade/Skill', new Save())),
//                        ->ajaxPipelineOnClick(ApiTeacherGroup::pipelineSaveTeacherGroupEdit($DivisionCourseId)),
                    (new Standard('Abbrechen', '/Education/Graduation/Grade/Skill', new Disable()))
//                        ->ajaxPipelineOnClick(ApiTeacherGroup::pipelineLoadViewTeacherGroups())
                ))
            ))
        )));
    }

    public function frontendSkillCategory(): Stage
    {
        $stage = new Stage('Kompetenzen-Kategorien', 'Übersicht');

        $dataList = $this->getSkillCategories();

        $columns = array(
            'Name' => 'Name',
            'Description' => 'Beschreibung',
        );
        $interactive = array(
            'order' => array(
                array(0, 'asc'),
//                array(1, 'asc'),
//                array(2, 'asc')
            ),
//            'columnDefs' => array(
//                array('type' => 'de_date', 'targets' => array(0, 1)),
//                array('orderable' => false, 'width' => '1%', 'targets' => -1),
//            ),
            'responsive' => false
        );
        $stage->setContent(
            (new Primary('Kategorie hinzufügen', '/Education/Graduation/Grade/Category/Skill/Add', new Plus()))
            . new TableData($dataList, null, $columns, $interactive)
        );

        return  $stage;
    }

    public function frontendSkillCategoryAdd(): Stage
    {
        $stage = new Stage('Kompetenz-Kategorie', 'Hinzufügen');

        $stage->setContent(new Well($this->formCategory()));

        return  $stage;
    }

    public function formCategory(): Form
    {
        return new Form(new FormGroup(array(
            new FormRow(
                new FormColumn(
                    (new TextField('Data[Name]', 'Name', 'Name'))->setRequired(),
                )
            ),
            new FormRow(
                new FormColumn(
                    new TextArea('Data[Description]', 'Beschreibung', 'Beschreibung')
                )
            ),
            new FormRow(array(
                new FormColumn(array(
                    (new Primary('Speichern', '/Education/Graduation/Grade/Category/Skill', new Save())),
//                        ->ajaxPipelineOnClick(ApiTeacherGroup::pipelineSaveTeacherGroupEdit($DivisionCourseId)),
                    (new Standard('Abbrechen', '/Education/Graduation/Grade/Category/Skill', new Disable()))
//                        ->ajaxPipelineOnClick(ApiTeacherGroup::pipelineLoadViewTeacherGroups())
                ))
            ))
        )));
    }

    /**
     * @return Stage
     */
    public function frontendSkillStudentInput(): Stage
    {
        $stage = new Stage('Kompetenzen', 'Für Schüler vergeben');

        $PersonId = 480;
        $tblPerson = Person::useService()->getPersonById($PersonId);
        $tblSubject = Subject::useService()->getSubjectByAcronym('DEU');

        $skills = $this->getSkills($tblSubject);
        $dataList = array();
        foreach ($skills as $skill) {
            $category = $skill['Category'];
            if (!isset($dataList[$category])) {
                $dataList[$category ?? 'Ohne Kategorie'] = array();
            }

            $dataList[$category][] = $skill['Skill'] . new PullRight(new NumberField('', '100', ''));
        }

        $content = '';
        foreach ($dataList as $category => $skills) {
            $content .= new Panel($category, $skills, Panel::PANEL_TYPE_PRIMARY);
        }

        $stage->setContent(
            new Layout(array(
                new LayoutGroup(array(
                    new LayoutRow(array(
                        new LayoutColumn(
                            new Panel('Schüler', $tblPerson->getLastFirstName(), Panel::PANEL_TYPE_INFO)
                        , 6),
                        new LayoutColumn(
                            new Panel('Kurs', DivisionCourse::useService()->getCurrentMainCoursesByPersonAndDate($tblPerson), Panel::PANEL_TYPE_INFO)
                        , 6),
                    ))
                )),
                new LayoutGroup(array(
                    new LayoutRow(array(
                        new LayoutColumn(
                            new Well(
                                $content
                                    . (new Primary('Speichern', '/Education/Graduation/Grade/Student/Skill/Input', new Save()))
                                    . (new Standard('Abbrechen', '/Education/Graduation/Grade/Student/Skill/Input', new Disable()))
                            )
                        ),
                    ))
                ), new Title('Fach: ' . $tblSubject->getDisplayName()))
            )),
        );

        return  $stage;
    }

    /**
     * @return Stage
     */
    public function frontendSkillDivisionCourseInput(): Stage
    {
        $stage = new Stage('Kompetenzen', 'Für Kurs vergeben');

        $tblDivisionCourse = DivisionCourse::useService()->getDivisionCourseById(45);
        $tblSubject = Subject::useService()->getSubjectByAcronym('DEU');

        $skills = $this->getSkills($tblSubject);
        $skill = current($skills);

        $headerList['Number'] = $this->getTableColumnHead('#');
        $headerList['Person'] = $this->getTableColumnHead('Schüler');
        $scoreType = $this->getSkillScoreType();

        foreach ($scoreType as $score) {
            $headerList[$score['Identifier']] = $this->getTableColumnHead($score['Name']);
        }

        $bodyList[] = array();
        if (($tblPersonList = $tblDivisionCourse->getStudentsWithSubCourses())) {
            $count = 0;
            foreach ($tblPersonList as $tblPerson) {

                $bodyList[$tblPerson->getId()]['Number'] =  $this->getTableColumnBody(++$count);
                $bodyList[$tblPerson->getId()]['Person'] =  $this->getTableColumnBody($tblPerson->getLastFirstNameWithCallNameUnderline());

                foreach ($scoreType as $score) {
                    $bodyList[$tblPerson->getId()][$score['Identifier']] = $this->getTableColumnBody(
                        new RadioBox('Data[' . $tblPerson->getId() . ']', ' ', $score['Identifier'])
                    );
                }
            }
        }

        $content = $this->getTableCustom($headerList, $bodyList);

        $stage->setContent(
            new Layout(array(
                new LayoutGroup(array(
                    new LayoutRow(array(
                        new LayoutColumn(
                            new Panel('Kurs', $tblDivisionCourse->getDisplayName(), Panel::PANEL_TYPE_INFO)
                            , 4),
                        new LayoutColumn(
                            new Panel('Fach', $tblSubject->getDisplayName(), Panel::PANEL_TYPE_INFO)
                            , 4),
                        new LayoutColumn(
                            new Panel('Kategorie', $skill['Category'], Panel::PANEL_TYPE_INFO)
                            , 4),
                    ))
                )),
                new LayoutGroup(array(
                    new LayoutRow(array(
                        new LayoutColumn(
                            $content
                                . (new Primary('Speichern', '/Education/Graduation/Grade/DivisionCourse/Skill/Input', new Save()))
                                . (new Standard('Abbrechen', '/Education/Graduation/Grade/DivisionCourse/Skill/Input', new Disable()))
                        ),
                    ))
                ), new Title(new Bold($skill['Skill'])))
            )),
        );

        return  $stage;
    }

    public function getSkillScoreType(): array
    {
        $dataList[] = array('Identifier' => '1', 'Name' => 'übertrifft die Anforderung');
        $dataList[] = array('Identifier' => '2', 'Name' => 'vollständig erreicht');
        $dataList[] = array('Identifier' => '3', 'Name' => 'im Wesentlichen erreicht');
        $dataList[] = array('Identifier' => '4', 'Name' => 'teilweise erreicht');
        $dataList[] = array('Identifier' => '5', 'Name' => 'kaum erreicht');

        return $dataList;
    }
}
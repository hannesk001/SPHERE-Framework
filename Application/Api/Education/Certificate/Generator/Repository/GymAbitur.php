<?php
/**
 * Created by PhpStorm.
 * User: Kauschke
 * Date: 08.03.2018
 * Time: 10:44
 */

namespace SPHERE\Application\Api\Education\Certificate\Generator\Repository;

use SPHERE\Application\Api\Education\Certificate\Generator\Certificate;
use SPHERE\Application\Education\Certificate\Generate\Generate;
use SPHERE\Application\Education\Certificate\Generator\Repository\Element;
use SPHERE\Application\Education\Certificate\Generator\Repository\Page;
use SPHERE\Application\Education\Certificate\Generator\Repository\Section;
use SPHERE\Application\Education\Certificate\Generator\Repository\Slice;
use SPHERE\Application\Education\Certificate\Prepare\Prepare;
use SPHERE\Application\Education\Lesson\Division\Division;
use SPHERE\Application\Education\Lesson\Subject\Service\Entity\TblSubject;
use SPHERE\Application\Education\Lesson\Subject\Subject;
use SPHERE\Application\People\Meta\Student\Student;
use SPHERE\Application\People\Person\Person;
use SPHERE\Application\People\Person\Service\Entity\TblPerson;
use SPHERE\Application\Setting\Consumer\Consumer;

/**
 * Class GymAbitur
 *
 * @package SPHERE\Application\Api\Education\Certificate\Generator\Repository
 */
class GymAbitur extends Certificate
{

    /**
     * @var array|false
     */
    private $AdvancedCourses = false;

    /**
     * @var array|false
     */
    private $BasicCourses = false;

    private $gradeTextList = array(
        '1' => 'sehr gut',
        '2' => 'gut',
        '3' => 'befriedigend',
        '4' => 'ausreichend',
        '5' => 'mangelhaft',
        '6' => 'ungenügend',
    );

    /**
     * @param TblPerson|null $tblPerson
     *
     * @return Page[]
     */
    public function buildPages(TblPerson $tblPerson = null)
    {

        $personId = $tblPerson ? $tblPerson->getId() : 0;

        $Header = $this->getHead($this->isSample(), true, 'auto', '50px');

        $this->setCourses($tblPerson);

        $hasLatinums = false;
        $hasGraecums = false;
        $hasHebraicums = false;
        if ($tblPerson && $this->getTblPrepareCertificate()) {
            if (($tblPrepareInformation = Prepare::useService()->getPrepareInformationBy($this->getTblPrepareCertificate(), $tblPerson, 'Latinums'))
                && $tblPrepareInformation->getValue()
            ) {
                $hasLatinums = true;
            }
            if (($tblPrepareInformation = Prepare::useService()->getPrepareInformationBy($this->getTblPrepareCertificate(), $tblPerson, 'Graecums'))
                && $tblPrepareInformation->getValue()
            ) {
                $hasGraecums = true;
            }
            if (($tblPrepareInformation = Prepare::useService()->getPrepareInformationBy($this->getTblPrepareCertificate(), $tblPerson, 'Hebraicums'))
                && $tblPrepareInformation->getValue()
            ) {
                $hasLatinums = true;
            }
        }
        $certificates = 'Dieses Zeugnis schließt den Nachweis des <b>';
        if ($hasLatinums) {
            $certificates .= 'Latinums';
        } else {
            $certificates .= '<s>Latinums</s>';
        }
        $certificates .= '/';
        if ($hasGraecums) {
            $certificates .= 'Graecums';
        } else {
            $certificates .= '<s>Graecums</s>';
        }
        $certificates .= '/';
        if ($hasHebraicums) {
            $certificates .= 'Hebraicums';
        } else {
            $certificates .= '<s>Hebraicums</s>';
        }
        $certificates .= '</b>² ein.';

        // Seite 4 zuerst für Multi-Pdf-Druck
        $pageList[] = (new Page())
            ->addSlice((new Slice())
                ->addSection((new Section())
                    ->addElementColumn((new Element())
                        ->setContent('Vor- und Zuname')
                        ->styleMarginTop('10px')
                        , '18%')
                    ->addElementColumn((new Element())
                        ->setContent('
                                {{ Content.P' . $personId . '.Person.Data.Name.First }}
                                {{ Content.P' . $personId . '.Person.Data.Name.Last }}
                        ')
                        ->styleAlignCenter()
                        ->styleBorderBottom()
                        ->styleMarginTop('10px')
                    )
                )
                ->addSection((new Section())
                    ->addElementColumn((new Element())
                        ->setContent('Ergebnisse der Pflichtfächer, die in Klassenstufe 10 abgeschlossen wurden¹')
                        ->styleTextBold()
                        ->styleMarginTop('15px')
                    )
                )
            )
            ->addSlice($this->getLevelTen($tblPerson ? $tblPerson : null))
            ->addSlice((new Slice())
                ->addSection((new Section())
                    ->addElementColumn((new Element())
                        ->setContent('Fremdsprachen')
                        ->styleTextBold()
                        ->styleMarginTop('45px')
                    )
                )
            )
            ->addSlice($this->getForeignLanguages($tblPerson ? $tblPerson : null))
            ->addSlice((new Slice())
                ->addSection((new Section())
                    ->addElementColumn((new Element())
                        ->setContent($certificates)
                        ->styleMarginTop('15px')
                    )
                )
            )
            ->addSlice((new Slice)
                ->addElement((new Element())
                    ->setContent('Bemerkungen:')
                    ->styleTextBold()
                    ->styleMarginTop('30px')
                )
            )
            ->addSlice($this->getDescriptionContent($personId, '160px'))
            ->addSlice((new Slice())
                ->addSection((new Section())
                    ->addElementColumn((new Element())
                        ->setContent('
                            {% if Content.P' . $personId . '.Person.Common.BirthDates.Gender == 2 %}
                                Frau
                            {% else %}
                                {% if Content.P' . $personId . '.Person.Common.BirthDates.Gender == 1 %}
                                    Herr
                                {% else %}
                                    Frau/Herr²
                                {% endif %}
                            {% endif %}
                            <u> {{ Content.P' . $personId . '.Person.Data.Name.First }} {{ Content.P' . $personId . '.Person.Data.Name.Last }} </u> 
                            hat die <b>Abiturprüfung bestanden</b> und die Berechtigung zum Studium an einer Hochschule in der
                            Bundesrepublik Deutschland erworben.
                        ')
                        ->stylePaddingBottom()
                    )
                )
            )
            ->addSlice((new Slice())
                ->addSection((new Section())
                    ->addElementColumn((new Element())
                        ->setContent('
                                {{ Content.P' . $personId . '.Company.Address.City.Name }}, {{ Content.P' . $personId . '.Input.Date }}
                            ')
                        ->styleMarginTop('70px')
                        ->styleBorderBottom()
                        , '35%')
                    ->addElementColumn((new Element()))
                )
                ->addSection((new Section())
                    ->addElementColumn((new Element())
                        ->setContent('
                                Ort, Datum
                            ')
                        ->styleTextSize('10pt')
                        ->styleMarginTop('0px')
                        , '35%')
                    ->addElementColumn((new Element()))
                )
            )
            ->addSlice($this->getExaminationsBoard())
            ->addSlice($this->getInfo(
                '40px',
                '¹ Das jeweilige Fach ist einzutragen. Die Ausweisung der Noten und Notenstufen kann der Schüler ablehnen
                 (§ 65 Absatz 3 SOGYA).',
                '² Nichtzutreffendes ist zu streichen.'
            ));

        $pageList[] = (new Page())
            ->addSlice($Header)
            ->addSlice((new Slice())
                ->addElement((new Element())
                    ->setContent('Zeugnis')
                    ->styleTextSize('27px')
                    ->styleAlignCenter()
                    ->styleMarginTop('25%')
                    ->styleTextBold()
                )
            )
            ->addSlice((new Slice())
                ->addElement((new Element())
                    ->setContent('der allgemeinen Hochschulreife')
                    ->styleTextSize('27px')
                    ->styleAlignCenter()
                    ->styleMarginTop('20px')
                    ->styleMarginBottom('5px')
                )
            )
            ->addSliceArray($this->getSchoolPartAbitur($personId))
            ->addSlice((new Slice())
                ->addSection((new Section())
                    ->addElementColumn((new Element())
                        ->setContent('Vor- und Zuname')
                        , '25%')
                    ->addElementColumn((new Element())
                        ->setContent('
                                {{ Content.P' . $personId . '.Person.Data.Name.First }}
                                {{ Content.P' . $personId . '.Person.Data.Name.Last }}
                        ')
                        ->styleAlignCenter()
                        ->styleBorderBottom()
                    )
                )->styleMarginTop('20px')
            )
            ->addSlice((new Slice())
                ->addSection((new Section())
                    ->addElementColumn((new Element())
                        ->setContent('geboren am')
                        , '25%')
                    ->addElementColumn((new Element())
                        ->setContent('{% if(Content.P' . $personId . '.Person.Common.BirthDates.Birthday is not empty) %}
                                    {{ Content.P' . $personId . '.Person.Common.BirthDates.Birthday|date("d.m.Y") }}
                                {% else %}
                                    &nbsp;
                                {% endif %}')
                        ->styleAlignCenter()
                        ->styleBorderBottom()
                        , '20%')
                    ->addElementColumn((new Element())
                        ->setContent('in')
                        ->styleAlignCenter()
                        , '5%')
                    ->addElementColumn((new Element())
                        ->setContent('{% if(Content.P' . $personId . '.Person.Common.BirthDates.Birthplace is not empty) %}
                                    {{ Content.P' . $personId . '.Person.Common.BirthDates.Birthplace }}
                                {% else %}
                                    &nbsp;
                                {% endif %}')
                        ->styleAlignCenter()
                        ->styleBorderBottom()
                    )
                )->styleMarginTop('20px')
            )
            ->addSlice((new Slice())
                ->addSection((new Section())
                    ->addElementColumn((new Element())
                        ->setContent('wohnhaft in')
                        , '25%')
                    ->addElementColumn((new Element())
                        ->setContent('{% if(Content.P' . $personId . '.Person.Address.City.Name) %}
                                    {{ Content.P' . $personId . '.Person.Address.Street.Name }}
                                    {{ Content.P' . $personId . '.Person.Address.Street.Number }},
                                    {{ Content.P' . $personId . '.Person.Address.City.Code }}
                                    {{ Content.P' . $personId . '.Person.Address.City.Name }}
                                {% else %}
                                      &nbsp;
                                {% endif %}')
                        ->styleAlignCenter()
                        ->styleBorderBottom()
                    )
                )->styleMarginTop('20px')
            )
            ->addSlice((new Slice())
                ->addSection((new Section())
                    ->addElementColumn((new Element())
                        ->setContent('hat sich nach dem Besuch der gymnasialen Oberstufe der Abiturprüfung unterzogen.')
                    )
                )->styleMarginTop('20px')
            )
            ->addSlice((new Slice())
                ->addSection((new Section())
                    ->addElementColumn((new Element())
                        ->setContent('Dem Zeugnis liegt die „Verordnung des Sächsischen Staatsministeriums für Kultus
                            über allgemeinbildende Gymnasien und die Abiturprüfung im Freistaat Sachsen“ (SOGYA) in der jeweils geltenden Fassung zu Grunde.
                        ')
                        ->styleTextSize('12px')
                    )
                )->styleMarginTop('370px')
            );

        /*
         * Block I
         */
        $slice = new Slice();
        $slice
            ->addSection((new Section())
                ->addSliceColumn(
                    (new Slice)
                        ->addSection((new Section())
                            ->addElementColumn((new Element())
                                ->setContent('Fach')
                                ->styleTextBold()
                                ->styleAlignCenter()
                            )
                        )
                        ->addSection((new Section())
                            ->addElementColumn((new Element())
                                ->setContent('&nbsp;')
                            )
                        )
                        ->addSection((new Section())
                            ->addElementColumn((new Element())
                                ->setContent('&nbsp;')
                            )
                        )
                        ->addSection((new Section())
                            ->addElementColumn((new Element())
                                ->setContent('&nbsp;')
                            )
                            ->addElementColumn((new Element())
                                ->setContent('LF²')
                                ->styleAlignCenter()
                                ->styleBorderLeft()
                            , '10%')
                        )
                        ->styleBorderTop()
                        ->styleBorderLeft()
                        ->styleMarginTop('10px')
                , '50%')
                ->addSliceColumn(
                    (new Slice)
                        ->addSection((new Section())
                            ->addElementColumn((new Element())
                                ->setContent('Bewertung¹')
                                ->styleTextBold()
                                ->styleAlignCenter()
                            )
                        )
                        ->addSection((new Section())
                            ->addElementColumn((new Element())
                                ->setContent('Halbjahresergebnisse in einfacher Wertung')
                                ->styleAlignCenter()
                            )
                        )
                        ->addSection((new Section())
                            ->addElementColumn((new Element())
                                ->setContent('Jahrgangsstufe 11')
                                ->styleAlignCenter()
                            , '50%')
                            ->addElementColumn((new Element())
                                ->setContent('Jahrgangsstufe 12')
                                ->styleAlignCenter()
                                ->styleBorderLeft()
                            , '50%')
                        )
                        ->addSection((new Section())
                            ->addElementColumn((new Element())
                                ->setContent('1. Halbjahr')
                                ->styleAlignCenter()
                                , '25%')
                            ->addElementColumn((new Element())
                                ->setContent('2. Halbjahr')
                                ->styleAlignCenter()
                                ->styleBorderLeft()
                                , '25%')
                            ->addElementColumn((new Element())
                                ->setContent('1. Halbjahr')
                                ->styleAlignCenter()
                                ->styleBorderLeft()
                                , '25%')
                            ->addElementColumn((new Element())
                                ->setContent('2. Halbjahr')
                                ->styleAlignCenter()
                                ->styleBorderLeft()
                                , '25%')
                        )
                        ->styleBorderLeft()
                        ->styleBorderTop()
                        ->styleBorderRight()
                        ->styleMarginTop('10px')
                    , '50%')
            )
            ->addSection($this->setFieldRow('Sprachlich-literarisch-künstlerisches Aufgabenfeld'))
            ->addSection($this->setSubjectRow($personId, 'Deutsch'))
            ->addSection($this->setSubjectRow($personId, 'Sorbisch'))
            ->addSection($this->setSubjectRow($personId, 'Englisch'))
            ->addSection($this->setSubjectRow($personId, 'Französisch'))
            ->addSection($this->setSubjectRow($personId, 'Griechisch'))
            ->addSection($this->setSubjectRow($personId, 'Italienisch'))
            ->addSection($this->setSubjectRow($personId, 'Latein'))
            ->addSection($this->setSubjectRow($personId, 'Polnisch'))
            ->addSection($this->setSubjectRow($personId, 'Russisch'))
            ->addSection($this->setSubjectRow($personId, 'Spanisch'))
            ->addSection($this->setSubjectRow($personId, 'Tschechisch'))
            ->addSection($this->setSubjectRow($personId))
            ->addSection($this->setSubjectRow($personId, 'Kunst'))
            ->addSection($this->setSubjectRow($personId, 'Musik'))

            ->addSection($this->setFieldRow('Gesellschaftswissenschaftliches Aufgabenfeld'))
            ->addSection($this->setSubjectRow($personId, 'Geschichte'))
            ->addSection($this->setSubjectRow($personId, 'Gemeinschaftskunde/Rechtserziehung/Wirtschaft'))
            ->addSection($this->setSubjectRow($personId, 'Geographie'))

            ->addSection($this->setFieldRow('Mathematisch-naturwissenschaftlich-technisches Aufgabenfeld'))
            ->addSection($this->setSubjectRow($personId, 'Mathematik'))
            ->addSection($this->setSubjectRow($personId, 'Biologie'))
            ->addSection($this->setSubjectRow($personId, 'Chemie'))
            ->addSection($this->setSubjectRow($personId, 'Physik'))
            ->addSection($this->setFieldRow())
            ->addSection($this->setSubjectRow($personId, 'RELIGION'))
            ->addSection($this->setSubjectRow($personId, 'Sport'))
            ->addSection($this->setFieldRow())
            ->addSection($this->setSubjectRow($personId, 'Astronomie', false))
            ->addSection($this->setSubjectRow($personId, 'Informatik', false))
            ->addSection($this->setSubjectRow($personId, 'Philosophie', false))
            ->addSection($this->setSubjectRow($personId, '&nbsp;', false))
            ->addSection($this->setSubjectRow($personId, '&nbsp;', false, true))
        ;


        $pageList[] = (new Page())
            ->addSlice((new Slice())
                ->addSection((new Section())
                    ->addElementColumn((new Element())
                        ->setContent('Vor- und Zuname')
                        ->styleMarginTop('10px')
                        , '18%')
                    ->addElementColumn((new Element())
                        ->setContent('
                                {{ Content.P' . $personId . '.Person.Data.Name.First }}
                                {{ Content.P' . $personId . '.Person.Data.Name.Last }}
                        ')
                        ->styleAlignCenter()
                        ->styleBorderBottom()
                        ->styleMarginTop('10px')
                    )
                )
                ->addSection((new Section())
                    ->addElementColumn((new Element())
                        ->setContent('Block I: Ergebnisse in der Qualifikationsphase')
                        ->styleTextBold()
                        ->styleMarginTop('10px')
                    )
                )
            )
            ->addSlice($slice)
            ->addSlice($this->getInfo(
                '240px',
                '¹ Alle Punktzahlen werden zweistellig angegeben.',
                '² Grundkursfächer bleiben ohne besondere Kennzeichnung. Leistungskursfächer sind in der betreffenden Zeile der Spalte „LF“ zu kennzeichnen.',
                '³ An Gymnasien gem. § 38 Abs. 2 SOGYA sind die Fächer Ev./Kath. Religion dem gesellschaftswissenschaftlichen Aufgabenfeld zugeordnet.'
//               , new Sup('4') . ' ⁴ mathematisch-naturwissenschaftlich-technisches Aufgabenfeld'
            ))
        ;

        /*
         * Block II
         */
        if (($tblPrepareInformation = Prepare::useService()->getPrepareInformationBy($this->getTblPrepareCertificate(), $tblPerson, 'IsBellUsed'))
            && $tblPrepareInformation->getValue()
        ) {
            $isBellUsed = true;
        } else {
            $isBellUsed = false;
        }

        if (($tblPrepareInformation = Prepare::useService()->getPrepareInformationBy($this->getTblPrepareCertificate(), $tblPerson, 'BellPoints'))) {
            $bellPoints = ($isBellUsed ? '' : '(')
                . str_pad($tblPrepareInformation->getValue(),2, 0, STR_PAD_LEFT)
                . ($isBellUsed ? '' : ')');
        } else {
            $bellPoints = '&nbsp;';
        }

        // Berechnung der Gesamtqualifikation und der Durchschnittsnote
        /** @noinspection PhpUnusedLocalVariableInspection */
        list($countCourses, $resultBlockI) = Prepare::useService()->getResultForAbiturBlockI(
            $this->getTblPrepareCertificate(),
            $tblPerson
        );
        $resultBlockII = Prepare::useService()->getResultForAbiturBlockII(
            $this->getTblPrepareCertificate(),
            $tblPerson
        );
        $resultPoints = $resultBlockI + $resultBlockII;
        if ($resultBlockI >= 200 && $resultBlockII >= 100) {
            $resultAverageGrade = Prepare::useService()->getResultForAbiturAverageGrade($resultPoints);
        } else {
            $resultAverageGrade = '&nbsp;';
        }

        $slice = new Slice();
        $slice
            ->addSection((new Section())
                ->addSliceColumn(
                    (new Slice)
                        ->addSection((new Section())
                            ->addElementColumn((new Element())
                                ->setContent('&nbsp;')
                            )
                        )
                        ->addSection((new Section())
                            ->addElementColumn((new Element())
                                ->setContent('Prüfungsfach')
                                ->stylePaddingLeft('5px')
                                ->styleTextBold()
                            )
                        )
                        ->addSection((new Section())
                            ->addElementColumn((new Element())
                                ->setContent('&nbsp;')
                                ->styleTextSize('12px')
                            )
                        )
                        ->addSection((new Section())
                            ->addElementColumn((new Element())
                                ->setContent('&nbsp;')
                                ->styleTextSize('13px')
                            )
                        )
                        ->styleBorderTop()
                        ->styleBorderLeft()
                        ->styleMarginTop('10px')
                    , '30%')
                ->addSliceColumn(
                    (new Slice)
                        ->addSection((new Section())
                            ->addElementColumn((new Element())
                                ->setContent('Bewertung:')
                                ->styleTextBold()
                                ->styleAlignCenter()
                            )
                        )
                        ->addSection((new Section())
                            ->addElementColumn((new Element())
                                ->setContent('Punktzahlen in einfacher Wertung')
                                ->styleAlignCenter()
                            )
                        )
                        ->addSection((new Section())
                            ->addElementColumn((new Element())
                                ->setContent('schriftliche')
                                ->styleTextSize('12px')
                                ->styleAlignCenter()
                                ->styleBorderTop()
                                , '25%')
                            ->addElementColumn((new Element())
                                ->setContent('mündliche')
                                ->styleTextSize('12px')
                                ->styleAlignCenter()
                                ->styleBorderTop()
                                ->styleBorderLeft()
                                , '25%')
                            ->addElementColumn((new Element())
                                ->setContent('zusätzliche')
                                ->styleTextSize('12px')
                                ->styleAlignCenter()
                                ->styleBorderTop()
                                ->styleBorderLeft()
                                , '25%')
                            ->addElementColumn((new Element())
                                ->setContent('Gesamtergebnis in')
                                ->styleTextSize('12px')
                                ->styleAlignCenter()
                                ->styleBorderTop()
                                ->styleBorderLeft()
                                , '25%')
                        )
                        ->addSection((new Section())
                            ->addElementColumn((new Element())
                                ->setContent('Prüfung')
                                ->styleTextSize('12px')
                                ->styleAlignCenter()
                                , '25%')
                            ->addElementColumn((new Element())
                                ->setContent('Prüfung')
                                ->styleTextSize('12px')
                                ->styleAlignCenter()
                                ->styleBorderLeft()
                                , '25%')
                            ->addElementColumn((new Element())
                                ->setContent('mündliche Prüfung')
                                ->styleTextSize('12px')
                                ->styleAlignCenter()
                                ->styleBorderLeft()
                                , '25%')
                            ->addElementColumn((new Element())
                                ->setContent('<b>vierfacher</b> Wertung')
                                ->styleTextSize('12px')
                                ->styleAlignCenter()
                                ->styleBorderLeft()
                                , '25%')
                        )
                        ->styleBorderLeft()
                        ->styleBorderTop()
                        ->styleBorderRight()
                        ->styleMarginTop('10px')
                    , '70%')
            )
            ->addSectionList($this->setExamRows($personId, $isBellUsed));

        $pageList[] = (new Page())
            ->addSlice((new Slice())
                ->addSection((new Section())
                    ->addElementColumn((new Element())
                        ->setContent('Vor- und Zuname')
                        ->styleMarginTop('10px')
                        , '18%')
                    ->addElementColumn((new Element())
                        ->setContent('
                                {{ Content.P' . $personId . '.Person.Data.Name.First }}
                                {{ Content.P' . $personId . '.Person.Data.Name.Last }}
                        ')
                        ->styleAlignCenter()
                        ->styleBorderBottom()
                        ->styleMarginTop('10px')
                    )
                )
                ->addSection((new Section())
                    ->addElementColumn((new Element())
                        ->setContent('Block II: Ergebnisse in der Abiturprüfung')
                        ->styleTextBold()
                        ->styleMarginTop('15px')
                    )
                )
            )
            ->addSlice($slice)
            ->addSlice((new Slice())
                ->addSection((new Section())
                    ->addElementColumn((new Element())
                        ->setContent('Besondere Lernleistung')
                        ->styleTextBold()
                        ->styleMarginTop('40px')
                    )
                )
                ->addSection((new Section())
                    ->addElementColumn((new Element())
                        ->setContent('Thema')
                        ->stylePaddingLeft('5px')
                        ->styleBorderLeft()
                        ->styleBorderTop()
                        ->styleMarginTop('15px')
                        , '50%')
                    ->addElementColumn((new Element())
                        ->setContent('Punktzahl in <b>vierfacher</b> Wertung')
                        ->stylePaddingLeft('5px')
                        ->styleBorderLeft()
                        ->styleBorderTop()
                        ->styleBorderRight()
                        ->styleMarginTop('15px')
                        , '50%')
                )
                ->addSection((new Section())
                    ->addElementColumn((new Element())
                        ->setContent('
                            {% if(Content.P' . $personId . '.Input.BellSubject is not empty) %}
                                {{ Content.P' . $personId . '.Input.BellSubject|nl2br }}
                            {% else %}
                                &nbsp;
                            {% endif %}')
                        ->stylePaddingLeft('5px')
                        ->stylePaddingRight('5px')
                        ->styleBorderLeft()
                        ->styleBorderTop()
                        ->styleBorderBottom()
                        ->styleHeight('69px')
                        , '50%')
                    ->addSliceColumn((new Slice)
                        ->addSection((new Section())
                            ->addElementColumn((new Element())
                                ->setContent('&nbsp;')
                            )
                        )
                        ->addSection((new Section())
                            ->addElementColumn((new Element())
                                ->setContent('&nbsp;')
                                , '33%')
                            ->addElementColumn((new Element())
                                ->setContent($bellPoints)
                                ->styleAlignCenter()
                                ->styleBorderBottom()
                            )
                            ->addElementColumn((new Element())
                                ->setContent('&nbsp;')
                                , '33%')
                        )
                        ->addSection((new Section())
                            ->addElementColumn((new Element())
                                ->setContent('&nbsp;')
                            )
                        )
                        ->addSection((new Section())
                            ->addElementColumn((new Element())
                                ->setContent('&nbsp;')
                            )
                        )
                        ->styleBorderAll()
                        , '50%')
                )
            )
            // Berechnung der Gesamtqualifikation und der Durchschnittsnote
            ->addSlice((new Slice())
                ->addSection((new Section())
                    ->addElementColumn((new Element())
                        ->setContent('Berechnung der Gesamtqualifikation und der Durchschnittsnote')
                        ->styleTextBold()
                        ->styleMarginTop('40px')
                        ->styleMarginBottom('15px')
                    )
                )
            )
            ->addSlice((new Slice())
                ->addSection((new Section())
                    ->addElementColumn((new Element())
                        ->setContent('Block I:')
                        ->stylePaddingLeft('5px')
                    )
                    ->addElementColumn((new Element())
                        ->setContent('&nbsp;')
                        , '10%')
                    ->addElementColumn((new Element())
                        ->setContent('mindestens 200,')
                        , '25%')
                )
                ->addSection((new Section())
                    ->addElementColumn((new Element())
                        ->setContent('Punktsumme aus den Halbjahresergebnissen²')
                        ->stylePaddingLeft('5px')
                    )
                    ->addElementColumn((new Element())
                        ->setContent($resultBlockI)
                        ->styleAlignCenter()
                        ->styleBorderBottom()
                        , '10%')
                    ->addElementColumn((new Element())
                        ->setContent('höchstens 600 Punkte')
                        , '25%')
                )
                ->addSection((new Section())
                    ->addElementColumn((new Element())
                        ->setContent('&nbsp;')
                    )
                )
                ->addSection((new Section())
                    ->addElementColumn((new Element())
                        ->setContent('Block II:')
                        ->stylePaddingLeft('5px')
                    )
                    ->addElementColumn((new Element())
                        ->setContent('&nbsp;')
                        , '10%')
                    ->addElementColumn((new Element())
                        ->setContent('&nbsp;')
                        , '25%')
                )
                ->addSection((new Section())
                    ->addElementColumn((new Element())
                        ->setContent('Punktsumme aus den Gesamtergebnissen in den Prüfungsfächern')
                        ->stylePaddingLeft('5px')
                    )
                    ->addElementColumn((new Element())
                        ->setContent('&nbsp;')
                        , '10%')
                    ->addElementColumn((new Element())
                        ->setContent('mindestens 100,')
                        , '25%')
                )
                ->addSection((new Section())
                    ->addElementColumn((new Element())
                        ->setContent('in vierfacher Wertung³')
                        ->stylePaddingLeft('5px')
                    )
                    ->addElementColumn((new Element())
                        ->setContent($resultBlockII)
                        ->styleAlignCenter()
                        ->styleBorderBottom()
                        , '10%')
                    ->addElementColumn((new Element())
                        ->setContent('höchstens 300 Punkte')
                        , '25%')
                )
                ->addSection((new Section())
                    ->addElementColumn((new Element())
                        ->setContent('&nbsp;')
                    )
                )
                ->addSection((new Section())
                    ->addElementColumn((new Element())
                        ->setContent('&nbsp;')
                    )
                    ->addElementColumn((new Element())
                        ->setContent('&nbsp;')
                        , '10%')
                    ->addElementColumn((new Element())
                        ->setContent('mindestens 300,')
                        , '25%')
                )
                ->addSection((new Section())
                    ->addElementColumn((new Element())
                        ->setContent('Gesamtpunktzahl')
                        ->stylePaddingLeft('5px')
                    )
                    ->addElementColumn((new Element())
                        ->setContent($resultPoints)
                        ->styleAlignCenter()
                        ->styleBorderBottom()
                        , '10%')
                    ->addElementColumn((new Element())
                        ->setContent('höchstens 900 Punkte')
                        , '25%')
                )
                ->addSection((new Section())
                    ->addElementColumn((new Element())
                        ->setContent('&nbsp;')
                    )
                )
                ->addSection((new Section())
                    ->addElementColumn((new Element())
                        ->setContent('&nbsp;')
                    )
                )
                ->addSection((new Section())
                    ->addElementColumn((new Element())
                        ->setContent('Durchschnittsnote')
                        ->stylePaddingLeft('5px')
                    )
                    ->addElementColumn((new Element())
                        ->setContent($resultAverageGrade)
                        ->styleAlignCenter()
                        ->styleBorderBottom()
                        , '10%')
                    ->addElementColumn((new Element())
                        ->setContent('&nbsp;')
                        , '25%')
                )
                ->addSection((new Section())
                    ->addElementColumn((new Element())
                        ->setContent('&nbsp;')
                    )
                )
                ->styleBorderAll()
            )
            ->addSlice($this->setPointsOverview('180px'))
            ->addSlice($this->getInfo(
                '50px',
                '¹ Alle Punktzahlen werden zweistellig angegeben.',
                '² Halbjahresergebnisse aus Leistungskursfächern (LF) werden doppelt gewichtet.',
                '³ Bei Einbringung einer Besonderen Lernleistung wird diese an Stelle des 5. Prüfungsfaches gewertet.'
            ));

        return $pageList;
    }

    /**
     * @param string $name
     * @param bool $isBold
     *
     * @return Section
     */
    private function setFieldRow($name = '&nbsp;', $isBold = true)
    {

        return (new Section())
            ->addElementColumn((new Element())
                ->setContent($name)
                ->stylePaddingLeft('5px')
                ->styleTextBold($isBold ? 'bold' : 'normal')
                ->styleBorderTop()
                ->styleBorderLeft()
                ->styleBorderRight()
            );
    }

    /**
     * @param $personId
     * @param string $subjectName
     * @param bool $hasAdvancedCourse
     * @param bool $isLastRow
     *
     * @return Section
     */
    private function setSubjectRow($personId, $subjectName = '&nbsp;', $hasAdvancedCourse = true, $isLastRow = false)
    {

        $color = '#BBB';
        $isAdvancedSubject = false;

        // tatsächliche Religion aus der Schülerakte bestimmen
        if ($subjectName == 'RELIGION') {
            $subjectName = 'Ev./Kath. Religion³/Ethik';
            if (($tblPerson = Person::useService()->getPersonById($personId))
                && ($tblStudent = $tblPerson->getStudent())
                && ($tblStudentSubjectType = Student::useService()->getStudentSubjectTypeByIdentifier('RELIGION'))
                && ($tblStudentSubjectRanking = Student::useService()->getStudentSubjectRankingByIdentifier('1'))
                && ($tblStudentSubject = Student::useService()->getStudentSubjectByStudentAndSubjectAndSubjectRanking($tblStudent,
                $tblStudentSubjectType, $tblStudentSubjectRanking))
                && ($tblReligionSubject = $tblStudentSubject->getServiceTblSubject())
            ) {
                $subjectName = $tblReligionSubject->getName() . '³';
            }
        } else {
            // Leistungskurse markieren
            if (isset($this->AdvancedCourses[0])) {
                /** @var TblSubject $advancedSubject1 */
                $advancedSubjectAcronym1 = $this->AdvancedCourses[0];
                if (($tblAdvancedSubject1 = Subject::useService()->getSubjectByAcronym($advancedSubjectAcronym1))
                    && $tblAdvancedSubject1->getName() == $subjectName
                ) {
                    $isAdvancedSubject = true;
                }
            }
            if (isset($this->AdvancedCourses[1])) {
                /** @var TblSubject $advancedSubject2 */
                $advancedSubjectAcronym2 = $this->AdvancedCourses[1];
                if (($tblAdvancedSubject2 = Subject::useService()->getSubjectByAcronym($advancedSubjectAcronym2))
                    && $tblAdvancedSubject2->getName() == $subjectName
                ) {
                    $isAdvancedSubject = true;
                }
            }
        }

        $grades = array(
            '11-1' => '&nbsp;',
            '11-2' => '&nbsp;',
            '12-1' => '&nbsp;',
            '12-2' => '&nbsp;',
        );

        $tblSubject = Subject::useService()->getSubjectByName($subjectName);
        if (!$tblSubject && $subjectName == 'Gemeinschaftskunde/Rechtserziehung/Wirtschaft') {
            $tblSubject = Subject::useService()->getSubjectByAcronym('GRW');
        }

        if (($tblPerson = Person::useService()->getPersonById($personId))
            && $tblSubject
        ) {
            for ($level = 11; $level < 13; $level++) {
                for ($term = 1; $term < 3; $term++) {
                    $midTerm = $level . '-' . $term;
                    if (($tblPrepareAdditionalGradeType = Prepare::useService()->getPrepareAdditionalGradeTypeByIdentifier($midTerm))
                        && ($tblPrepare = $this->getTblPrepareCertificate())
                        && ($tblPrepareAdditionalGrade = Prepare::useService()->getPrepareAdditionalGradeBy(
                            $tblPrepare,
                            $tblPerson,
                            $tblSubject,
                            $tblPrepareAdditionalGradeType
                        ))
                    ) {
                        $isSelected = $tblPrepareAdditionalGrade->isSelected();
                        $value = str_pad($tblPrepareAdditionalGrade->getGrade(),2, 0, STR_PAD_LEFT);
                        $grades[$midTerm] = ($isSelected ? '' : '(') . $value . ($isSelected ? '' : ')');
                    }
                }
            }
        }

        return (new Section())
            ->addElementColumn((new Element())
                ->setContent($subjectName)
                ->stylePaddingLeft('5px')
                ->styleBorderTop()
                ->styleBorderLeft()
                ->styleBorderBottom($isLastRow ? '1px' : '0px')
                , $hasAdvancedCourse ? '45%' : '50%')
            ->addElementColumn((new Element())
                ->setContent($isAdvancedSubject ? 'X' : '&nbsp;')
                ->styleAlignCenter()
                ->styleBackgroundColor($hasAdvancedCourse ? $color : '#FFF')
                ->styleBorderTop()
                ->styleBorderLeft($hasAdvancedCourse ? '1px' : '0px')
                ->styleBorderBottom($isLastRow ? '1px' : '0px')
                , $hasAdvancedCourse ? '5%' : '0%')
            ->addElementColumn((new Element())
                ->setContent($grades['11-1'])
                ->styleAlignCenter()
                ->styleBackgroundColor($color)
                ->styleBorderTop()
                ->styleBorderLeft()
                ->styleBorderBottom($isLastRow ? '1px' : '0px')
                , '12.5%')
            ->addElementColumn((new Element())
                ->setContent($grades['11-2'])
                ->styleAlignCenter()
                ->styleBackgroundColor($color)
                ->styleBorderTop()
                ->styleBorderLeft()
                ->styleBorderBottom($isLastRow ? '1px' : '0px')
                , '12.5%')
            ->addElementColumn((new Element())
                ->setContent($grades['12-1'])
                ->styleAlignCenter()
                ->styleBackgroundColor($color)
                ->styleBorderTop()
                ->styleBorderLeft()
                ->styleBorderBottom($isLastRow ? '1px' : '0px')
                , '12.5%')
            ->addElementColumn((new Element())
                ->setContent($grades['12-2'])
                ->styleAlignCenter()
                ->styleBackgroundColor($color)
                ->styleBorderTop()
                ->styleBorderLeft()
                ->styleBorderRight()
                ->styleBorderBottom($isLastRow ? '1px' : '0px')
                , '12.5%')
            ;
    }

    private function setExamRows($personId, $isBellUsed)
    {
        $color = '#BBB';
        $sectionList = array();

        for ($i = 1; $i < 6; $i++) {
            $section = new Section();

            $subjectName = '&nbsp;';
            $writtenExam = '&nbsp;';
            $verbalExam = '&nbsp;';
            $extraVerbalExam = '&nbsp;';
            $total = '&nbsp;';

            if (($tblPerson = Person::useService()->getPersonById($personId))
                && $this->getTblPrepareCertificate()
            ) {

                if ($i < 4) {
                    if (($tblPrepareAdditionalGradeType = Prepare::useService()->getPrepareAdditionalGradeTypeByIdentifier('WRITTEN_EXAM'))
                        && ($writtenExamGrade = Prepare::useService()->getPrepareAdditionalGradeByRanking(
                            $this->getTblPrepareCertificate(),
                            $tblPerson,
                            $tblPrepareAdditionalGradeType,
                            $i))
                    ) {
                        if (($tblSubject = $writtenExamGrade->getServiceTblSubject())) {
                            $subjectName = $i . '. ' . ($i < 3 ? '(LF) ' : ' ') . $tblSubject->getName();
                        }

                        $writtenExam = str_pad($writtenExamGrade->getGrade(), 2, 0, STR_PAD_LEFT);

                        if (($tblPrepareAdditionalGradeType = Prepare::useService()->getPrepareAdditionalGradeTypeByIdentifier('EXTRA_VERBAL_EXAM'))
                            && ($extraVerbalExamGrade = Prepare::useService()->getPrepareAdditionalGradeByRanking(
                                $this->getTblPrepareCertificate(),
                                $tblPerson,
                                $tblPrepareAdditionalGradeType,
                                $i))
                        ) {
                            $extraVerbalExamGradeValue = $extraVerbalExamGrade->getGrade();
                            if ($extraVerbalExamGradeValue !== '' && $extraVerbalExamGradeValue !== null) {
                                $extraVerbalExam = str_pad($extraVerbalExamGradeValue, 2, 0, STR_PAD_LEFT);
                            }
                        } else {
                            $extraVerbalExamGrade = false;
                        }

                        $total = Prepare::useService()->calcAbiturExamGradesTotalForWrittenExam(
                            $writtenExamGrade,
                            $extraVerbalExamGrade ? $extraVerbalExamGrade : null
                        );
                    }
                } else {
                    if (($tblPrepareAdditionalGradeType = Prepare::useService()->getPrepareAdditionalGradeTypeByIdentifier('VERBAL_EXAM'))
                        && ($verbalExamGrade = Prepare::useService()->getPrepareAdditionalGradeByRanking(
                            $this->getTblPrepareCertificate(),
                            $tblPerson,
                            $tblPrepareAdditionalGradeType,
                            $i))
                    ) {
                        if (($tblSubject = $verbalExamGrade->getServiceTblSubject())) {
                            $subjectName = $i . '. ' . ($i < 3 ? '(LF) ' : ' ') . $tblSubject->getName();
                        }

                        $verbalExam = ($isBellUsed && $i == 5 ? '(' : '')
                            . str_pad($verbalExamGrade->getGrade(), 2, 0, STR_PAD_LEFT)
                            . ($isBellUsed && $i == 5 ? ')' : '');

                        if (($tblPrepareAdditionalGradeType = Prepare::useService()->getPrepareAdditionalGradeTypeByIdentifier('EXTRA_VERBAL_EXAM'))
                            && ($extraVerbalExamGrade = Prepare::useService()->getPrepareAdditionalGradeByRanking(
                                $this->getTblPrepareCertificate(),
                                $tblPerson,
                                $tblPrepareAdditionalGradeType,
                                $i))
                        ) {
                            $extraVerbalExamGradeValue = $extraVerbalExamGrade->getGrade();
                            if ($extraVerbalExamGradeValue !== '' && $extraVerbalExamGradeValue !== null) {
                                $extraVerbalExam = ($isBellUsed && $i == 5 ? '(' : '')
                                    . str_pad($extraVerbalExamGradeValue, 2, 0, STR_PAD_LEFT)
                                    . ($isBellUsed && $i == 5 ? ')' : '');
                            }
                        } else {
                            $extraVerbalExamGrade = false;
                        }

                        $total = Prepare::useService()->calcAbiturExamGradesTotalForVerbalExam(
                            $verbalExamGrade,
                            $extraVerbalExamGrade ? $extraVerbalExamGrade : null
                        );

                        $total = ($isBellUsed && $i == 5 ? '(' : '')
                        . $total
                        . ($isBellUsed && $i == 5 ? ')' : '');
                    }
                }
            }

            $section
                ->addElementColumn((new Element())
                    ->setContent($subjectName)
                    ->styleBorderLeft()
                    ->styleBorderTop()
                    ->styleBorderBottom($i < 5 ? '0px' : '1px')
                    , '30%')
                ->addElementColumn((new Element())
                    ->setContent($writtenExam)
                    ->styleAlignCenter()
                    ->styleBorderLeft()
                    ->styleBorderTop()
                    ->styleBorderBottom($i < 5 ? '0px' : '1px')
                    ->styleBackgroundColor($i <4 ? $color : '#FFF')
                    , '17.5%')
                ->addElementColumn((new Element())
                    ->setContent($verbalExam)
                    ->styleAlignCenter()
                    ->styleBorderLeft()
                    ->styleBorderTop()
                    ->styleBorderBottom($i < 5 ? '0px' : '1px')
                    ->styleBackgroundColor($i > 3 ? $color : '#FFF')
                    , '17.5%')
                ->addElementColumn((new Element())
                    ->setContent($extraVerbalExam)
                    ->styleAlignCenter()
                    ->styleBorderLeft()
                    ->styleBorderTop()
                    ->styleBorderBottom($i < 5 ? '0px' : '1px')
                    ->styleBackgroundColor($color)
                    , '17.5%')
                ->addElementColumn((new Element())
                    ->setContent($total)
                    ->styleAlignCenter()
                    ->styleBorderLeft()
                    ->styleBorderTop()
                    ->styleBorderBottom($i < 5 ? '0px' : '1px')
                    ->styleBorderRight()
                    ->styleBackgroundColor($color)
                    , '17.5%');

            $sectionList[] = $section;
        }

        return $sectionList;
    }

    /**
     * @param $personId
     * @param string $MarginTop
     *
     * @return Slice[]
     */
    private function getSchoolPartAbitur($personId, $MarginTop = '20px')
    {

        if (($tblSetting = Consumer::useService()->getSetting(
                'Education', 'Certificate', 'Prepare', 'IsSchoolExtendedNameDisplayed'))
            && $tblSetting->getValue()
        ) {
            $isSchoolExtendedNameDisplayed = true;
        } else {
            $isSchoolExtendedNameDisplayed = false;
        }
        if (($tblSetting = Consumer::useService()->getSetting(
                'Education', 'Certificate', 'Prepare', 'SchoolExtendedNameSeparator'))
            && $tblSetting->getValue()
        ) {
            $separator = $tblSetting->getValue();
        } else {
            $separator = false;
        }
//        $isLargeCompanyName = false;
//        $isLargeSecondRow = false;
        $isLargeCompanyName = true;
        $isLargeSecondRow = true;
        $schoolName = '';
        $extendedName = '';
        // get company name
        if (($tblPerson = Person::useService()->getPersonById($personId))) {
            if (($tblStudent = Student::useService()->getStudentByPerson($tblPerson))) {
                if (($tblTransferType = Student::useService()->getStudentTransferTypeByIdentifier('PROCESS'))) {
                    $tblStudentTransfer = Student::useService()->getStudentTransferByType($tblStudent,
                        $tblTransferType);
                    if ($tblStudentTransfer) {
                        if (($tblCompany = $tblStudentTransfer->getServiceTblCompany())) {
                            $place = '';
                            if (($tblAddress = $tblCompany->fetchMainAddress())
                                && ($tblCity = $tblAddress->getTblCity())
                            ) {
                                $place = ' zu ' . $tblCity->getName();
                            }
                            $schoolName = $tblCompany->getName();
                            $extendedName = $isSchoolExtendedNameDisplayed ?
                                ($separator ? ' ' . $separator . ' ' : ' ') . $tblCompany->getExtendedName() : '';
                            $extendedName .= $place;

                            if (strlen($schoolName) > 60) {
                                $isLargeCompanyName = true;
                            }
                            if (strlen($extendedName) > 60) {
                                $isLargeSecondRow = true;
                            }
                        }
                    }
                }
            }
        }

        $SchoolSlice = (new Slice());
        if ($isLargeCompanyName) {
            $SchoolSlice->addSection((new Section())
                ->addElementColumn((new Element())
                    ->setContent('Name und Ort der Schule:')
                    , '25%')
                ->addElementColumn((new Element())
                    ->setContent($schoolName ? $schoolName : '&nbsp;')
                    ->styleBorderBottom()
                    ->styleAlignCenter()
                )
            )->styleMarginTop($MarginTop);
        } else {
            $SchoolSlice->addSection((new Section())
                ->addElementColumn((new Element())
                    ->setContent('Name und Ort der Schule:')
                    , '25%')
                ->addElementColumn((new Element())
                    ->setContent($schoolName ? $schoolName : '&nbsp;')
                    ->styleBorderBottom()
                    ->styleAlignCenter()
                , '50%')
                ->addElementColumn((new Element())
                    ->setContent('&nbsp;')
                    ->styleBorderBottom()
                    , '25%')
            )->styleMarginTop($MarginTop);
        }
        $slices[] = $SchoolSlice;

        $SecondSlice = (new Slice());
        if ($isLargeSecondRow) {
            $SecondSlice->addSection((new Section())
                ->addElementColumn((new Element())
                    ->setContent('&nbsp;')
                    , '25%')
                ->addElementColumn((new Element())
                    ->setContent($extendedName ? $extendedName : '&nbsp;')
                    ->styleBorderBottom()
                    ->styleAlignCenter()
                )
            )->styleMarginTop($MarginTop);
        } else {
            $SecondSlice->addSection((new Section())
                ->addElementColumn((new Element())
                    ->setContent('&nbsp;')
                    , '25%')
                ->addElementColumn((new Element())
                    ->setContent($extendedName ? $extendedName : '&nbsp;')
                    ->styleBorderBottom()
                    ->styleAlignCenter()
                )
                ->addElementColumn((new Element())
                    ->setContent('&nbsp;')
                    ->styleBorderBottom()
                    , '25%')
            )->styleMarginTop($MarginTop);
        }
        $slices[] = $SecondSlice;

        return $slices;
    }

    /**
     * @param TblPerson|null $tblPerson
     */
    private function setCourses(TblPerson $tblPerson = null)
    {

        $advancedCourses = array();
        $basicCourses = array();
        if ($tblPerson && ($tblDivision = $this->getTblDivision())
            && ($tblDivisionSubjectList = Division::useService()->getDivisionSubjectByDivision($tblDivision))
        ) {
            foreach ($tblDivisionSubjectList as $tblDivisionSubjectItem) {
                if (($tblSubjectGroup = $tblDivisionSubjectItem->getTblSubjectGroup())) {

                    if (($tblSubjectStudentList = Division::useService()->getSubjectStudentByDivisionSubject(
                        $tblDivisionSubjectItem))
                    ) {
                        foreach ($tblSubjectStudentList as $tblSubjectStudent) {
                            if (($tblSubject = $tblDivisionSubjectItem->getServiceTblSubject())
                                && ($tblPersonStudent = $tblSubjectStudent->getServiceTblPerson())
                                && $tblPerson->getId() == $tblPersonStudent->getId()
                            ) {
                                if ($tblSubjectGroup->isAdvancedCourse()) {
                                    if ($tblSubject->getName() == 'Deutsch' || $tblSubject->getName() == 'Mathematik') {
                                        $advancedCourses[0] = $tblSubject->getAcronym();
                                    } else {
                                        $advancedCourses[1] = $tblSubject->getAcronym();
                                    }
                                } else {
                                    $basicCourses[$tblSubject->getAcronym()] = $tblSubject->getAcronym();
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!empty($advancedCourses)) {
            $this->AdvancedCourses = $advancedCourses;
        }
        if (!empty($basicCourses)) {
            $this->BasicCourses = $basicCourses;
        }
    }

    /**
     * @param Section $section
     * @param string $name
     * @param $textSize
     * @param bool $isBorderBottom
     * @param bool $isBorderRight
     */
    private function setColumnElement(
        Section $section,
        $name,
        $textSize,
        $isBorderBottom = false,
        $isBorderRight = false
    ) {

        $section
            ->addElementColumn((new Element())
                ->setContent($name)
                ->styleTextSize($textSize)
                ->styleAlignCenter()
                ->styleBorderLeft()
                ->styleBorderTop()
                ->styleBorderRight($isBorderRight ? '1px' : '0px')
                ->styleBorderBottom($isBorderBottom ? '1px' : '0px')
                , '14.28%');
    }

    /**
     * @param string $MarginTop
     *
     * @return Slice
     */
    private function setPointsOverview($MarginTop = '25px')
    {

        $textSize = '10px';
        $slice = new Slice();
        $slice
            ->addSection((new Section())
                ->addElementColumn((new Element())
                    ->setContent('Für die Umsetzung der Noten in Punkte gilt:')
                    ->styleTextSize($textSize)
                )
            );

        $section = new Section();
        $this->setColumnElement($section, 'Notenstufen', $textSize);
        $this->setColumnElement($section, 'sehr gut', $textSize);
        $this->setColumnElement($section, 'gut', $textSize);
        $this->setColumnElement($section, 'befriedigend', $textSize);
        $this->setColumnElement($section, 'ausreichend', $textSize);
        $this->setColumnElement($section, 'mangelhaft', $textSize);
        $this->setColumnElement($section, 'ungenügend', $textSize, false, true);
        $slice
            ->addSection($section);

        $section = new Section();
        $this->setColumnElement($section, 'Noten', $textSize);
        $this->setColumnElement($section, '+&nbsp;&nbsp;&nbsp;1&nbsp;&nbsp;&nbsp;-', $textSize);
        $this->setColumnElement($section, '+&nbsp;&nbsp;&nbsp;2&nbsp;&nbsp;&nbsp;-', $textSize);
        $this->setColumnElement($section, '+&nbsp;&nbsp;&nbsp;3&nbsp;&nbsp;&nbsp;-', $textSize);
        $this->setColumnElement($section, '+&nbsp;&nbsp;&nbsp;4&nbsp;&nbsp;&nbsp;-', $textSize);
        $this->setColumnElement($section, '+&nbsp;&nbsp;&nbsp;5&nbsp;&nbsp;&nbsp;-', $textSize);
        $this->setColumnElement($section, '6', $textSize, false, true);
        $slice
            ->addSection($section);

        $section = new Section();
        $this->setColumnElement($section, 'Punkte', $textSize, true);
        $this->setColumnElement($section, '15 14 13', $textSize, true);
        $this->setColumnElement($section, '12 11 10', $textSize, true);
        $this->setColumnElement($section, '09 08 07', $textSize, true);
        $this->setColumnElement($section, '06 05 04', $textSize, true);
        $this->setColumnElement($section, '03 02 01', $textSize, true);
        $this->setColumnElement($section, '00', $textSize, true, true);
        $slice
            ->addSection($section);

        return $slice
            ->styleMarginTop($MarginTop);
    }

    private function getLevelTen(TblPerson $tblPerson = null)
    {

        $slice = new Slice();
        $slice
            ->addSection((new Section())
                ->addElementColumn((new Element())
                    ->setContent('Fach')
                    ->styleAlignCenter()
                    ->styleTextBold()
                    ->styleBorderLeft()
                    ->styleBorderTop()
                    ->styleMarginTop('15px')
                    , '50%')
                ->addElementColumn((new Element())
                    ->setContent('Note')
                    ->styleAlignCenter()
                    ->styleTextBold()
                    ->styleBorderLeft()
                    ->styleBorderTop()
                    ->styleMarginTop('15px')
                    , '20%')
                ->addElementColumn((new Element())
                    ->setContent('Notenstufe')
                    ->styleAlignCenter()
                    ->styleTextBold()
                    ->styleBorderLeft()
                    ->styleBorderTop()
                    ->styleBorderRight()
                    ->styleMarginTop('15px')
                    , '30%')
            );

        // Zensuren ausblenden wenn der Schüler widersprochen hat
        if (($tblPrepareInformation = Prepare::useService()->getPrepareInformationBy($this->getTblPrepareCertificate(),
            $tblPerson, 'LevelTenGradesAreNotShown'))
        ) {
            $levelTenGradesAreNotShown = $tblPrepareInformation->getValue();
        } else {
            $levelTenGradesAreNotShown = false;
        }

        $tblPrepareAdditionalGradeType = Prepare::useService()->getPrepareAdditionalGradeTypeByIdentifier('LEVEL-10');
        for ($i = 1; $i < 8; $i++) {
            $subject = '&nbsp;';
            $grade = '&nbsp;';
            $gradeText = '&nbsp;';

            if ($tblPrepareAdditionalGradeType
                && ($tblPrepareAdditionalGrade = Prepare::useService()->getPrepareAdditionalGradeByRanking(
                    $this->getTblPrepareCertificate(),
                    $tblPerson,
                    $tblPrepareAdditionalGradeType,
                    $i
                ))
                && ($tblSubject = $tblPrepareAdditionalGrade->getServiceTblSubject())
            ) {
                $subject = $tblSubject->getName();
                if (!$levelTenGradesAreNotShown) {
                    $grade = $tblPrepareAdditionalGrade->getGrade();
                    if (isset($this->gradeTextList[$tblPrepareAdditionalGrade->getGrade()])) {
                        $gradeText = $this->gradeTextList[$tblPrepareAdditionalGrade->getGrade()];
                    }
                }
            }
            $slice
                ->addSection((new Section())
                    ->addElementColumn((new Element())
                        ->setContent($subject)
                        ->stylePaddingLeft('5px')
                        ->styleBorderLeft()
                        ->styleBorderTop()
                        ->styleBorderBottom($i == 7 ? '1px' : '0px')
                        , '50%')
                    ->addElementColumn((new Element())
                        ->setContent($grade)
                        ->styleAlignCenter()
                        ->styleBorderLeft()
                        ->styleBorderTop()
                        ->styleBorderBottom($i == 7 ? '1px' : '0px')
                        , '20%')
                    ->addElementColumn((new Element())
                        ->setContent($gradeText)
                        ->styleAlignCenter()
                        ->styleBorderLeft()
                        ->styleBorderTop()
                        ->styleBorderRight()
                        ->styleBorderBottom($i == 7 ? '1px' : '0px')
                        , '30%')
                );
        }

        return $slice;
    }

    private function getForeignLanguages(TblPerson $tblPerson = null)
    {

        $slice = new Slice();
        $slice
            ->addSection((new Section())
                ->addElementColumn((new Element())
                    ->setContent('Fach')
                    ->styleAlignCenter()
                    ->styleBorderLeft()
                    ->styleBorderTop()
                    ->styleMarginTop('15px')
                    , '50%')
                ->addElementColumn((new Element())
                    ->setContent('Klassen-/Jahrgangsstufe')
                    ->styleAlignCenter()
                    ->styleBorderLeft()
                    ->styleBorderTop()
                    ->styleBorderRight()
                    ->styleMarginTop('15px')
                    , '50%')
            );

        $tblStudentSubjectType = Student::useService()->getStudentSubjectTypeByIdentifier('FOREIGN_LANGUAGE');
        if (($tblPerson)) {
            $tblStudent = $tblPerson->getStudent();
        } else {
            $tblStudent = false;
        }

        for ($i = 1; $i < 5; $i++) {
            $subject = '&nbsp;';
            $levelFrom = '&nbsp;';
            $levelTill = '&nbsp;';

            if ($tblStudent
                && $tblStudentSubjectType
                && ($tblStudentSubjectRanking = Student::useService()->getStudentSubjectRankingByIdentifier($i))
                && ($tblStudentSubject = Student::useService()->getStudentSubjectByStudentAndSubjectAndSubjectRanking(
                    $tblStudent, $tblStudentSubjectType, $tblStudentSubjectRanking
                ))
            ) {
                if (($tblSubject = $tblStudentSubject->getServiceTblSubject())) {
                    $subject = $tblSubject->getName();
                    if (($tblLevelFrom = $tblStudentSubject->getServiceTblLevelFrom())) {
                        $levelFrom = $tblLevelFrom->getName();
                    } else {
                        $levelFrom = '&ndash;';
                    }
                    if (($tblLevelTill = $tblStudentSubject->getServiceTblLevelTill())) {
                        $levelTill = $tblLevelTill->getName();
                    } else {
                        $levelTill = '&ndash;';
                    }
                }
            }

            $slice
                ->addSection((new Section())
                    ->addElementColumn((new Element())
                        ->setContent($subject)
                        ->stylePaddingLeft('5px')
                        ->styleBorderLeft()
                        ->styleBorderTop()
                        ->styleBorderBottom($i == 4 ? '1px' : '0px')
                        , '50%')
                    ->addSliceColumn((new Slice())
                        ->addSection((new Section())
                            ->addElementColumn((new Element())
                                ->setContent('von')
                                ->stylePaddingLeft('5px')
                                , '15%')
                            ->addElementColumn((new Element())
                                ->setContent($levelFrom)
                                ->styleAlignCenter()
                                , '35%')
                            ->addElementColumn((new Element())
                                ->setContent('bis')
                                ->stylePaddingLeft('5px')
                                , '15%')
                            ->addElementColumn((new Element())
                                ->setContent($levelTill)
                                ->styleAlignCenter()
                                , '35%')
                        )
                        ->styleBorderLeft()
                        ->styleBorderTop()
                        ->styleBorderRight()
                        ->styleBorderBottom($i == 4 ? '1px' : '0px')
                    )
                );
        }

        return $slice;
    }

    /**
     * @param string $marginTop
     * @param string $textSize
     *
     * @return Slice
     * @throws \Exception
     */
    private function getExaminationsBoard($marginTop = '10px', $textSize = '10pt')
    {

        $leaderName = '&nbsp;';
        $leaderDescription = 'Vorsitzende(r)';
        $firstMemberName = '&nbsp;';
        $secondMemberName = '&nbsp;';

        if (($tblGenerateCertificate = $this->getTblPrepareCertificate()->getServiceTblGenerateCertificate())) {

            if (($tblGenerateCertificateSettingLeader = Generate::useService()->getGenerateCertificateSettingBy($tblGenerateCertificate, 'Leader'))
                && ($tblPersonLeader = Person::useService()->getPersonById($tblGenerateCertificateSettingLeader->getValue()))
            ) {
                $leaderName = $tblPersonLeader->getFullName();
                if (($tblCommon = $tblPersonLeader->getCommon())
                    && ($tblCommonBirthDates = $tblCommon->getTblCommonBirthDates())
                    && ($tblGender = $tblCommonBirthDates->getTblCommonGender())
                ) {
                    if ($tblGender->getName() == 'Männlich') {
                        $leaderDescription = 'Vorsitzender';
                    } elseif ($tblGender->getName() == 'Weiblich') {
                        $leaderDescription = 'Vorsitzende';
                    }
                }
            }

            if (($tblGenerateCertificateSettingFirstMember = Generate::useService()->getGenerateCertificateSettingBy($tblGenerateCertificate, 'FirstMember'))
                && ($tblPersonFirstMember = Person::useService()->getPersonById($tblGenerateCertificateSettingFirstMember->getValue()))
            ) {
                $firstMemberName = $tblPersonFirstMember->getFullName();
            }

            if (($tblGenerateCertificateSettingSecondMember = Generate::useService()->getGenerateCertificateSettingBy($tblGenerateCertificate, 'SecondMember'))
                && ($tblPersonSecondMember = Person::useService()->getPersonById($tblGenerateCertificateSettingSecondMember->getValue()))
            ) {
                $secondMemberName = $tblPersonSecondMember->getFullName();
            }
        }

        $slice = (new Slice())
            ->addSection((new Section())
                ->addElementColumn((new Element())
                    ->styleMarginTop($marginTop)
                    , '35%')
                ->addElementColumn((new Element())
                    ->setContent('Der Prüfungsausschuss')
                    ->styleTextSize($textSize)
                    ->styleAlignCenter()
                    ->styleMarginTop($marginTop)
                )
                ->addElementColumn((new Element())
                    ->styleMarginTop($marginTop)
                    , '35%')
            )
            ->addSection((new Section())
                ->addElementColumn((new Element())
                    ->setContent($leaderName)
                    ->styleBorderBottom()
                    ->styleMarginTop('15px')
                    , '35%')
                ->addElementColumn((new Element())
                )
                ->addElementColumn((new Element())
                    ->setContent($firstMemberName)
                    ->styleBorderBottom()
                    ->styleMarginTop('15px')
                    , '35%')
            )
            ->addSection((new Section())
                ->addElementColumn((new Element())
                    ->setContent($leaderDescription)
                    ->styleTextSize($textSize)
                    ->styleMarginTop('0px')
                    , '35%')
                ->addElementColumn((new Element())
                )
                ->addElementColumn((new Element())
                    ->setContent('Mitglied')
                    ->styleTextSize($textSize)
                    ->styleMarginTop('0px')
                    , '35%')
            )
            ->addSection((new Section())
                ->addElementColumn((new Element())
                    ->styleMarginTop('15px')
                    , '35%')
                ->addElementColumn((new Element())
                    ->setContent('Dienstsiegel </br> der Schule' )
                    ->styleTextSize($textSize)
                    ->styleAlignCenter()
                    ->styleMarginTop('15px')
                )
                ->addElementColumn((new Element())
                    ->styleMarginTop('15px')
                    , '35%')
            )
            ->addSection((new Section())
                ->addElementColumn((new Element())
                    , '35%')
                ->addElementColumn((new Element())
                )
                ->addElementColumn((new Element())
                    ->setContent($secondMemberName)
                    ->styleBorderBottom()
                    ->styleMarginTop('15px')
                    , '35%')
            )
            ->addSection((new Section())
                ->addElementColumn((new Element())
                    , '35%')
                ->addElementColumn((new Element())
                )
                ->addElementColumn((new Element())
                    ->setContent('Mitglied')
                    ->styleTextSize($textSize)
                    ->styleMarginTop('0px')
                    , '35%')
            )
        ;

        return $slice;
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: Kauschke
 * Date: 01.08.2018
 * Time: 15:19
 */

namespace SPHERE\Application\Platform\System\Restore;

use SPHERE\Application\Contact\Address\Address;
use SPHERE\Application\IServiceInterface;
use SPHERE\Application\People\Person\Person;
use SPHERE\Application\Setting\Consumer\Consumer;
use SPHERE\Common\Frontend\Icon\Repository\ChevronLeft;
use SPHERE\Common\Frontend\Icon\Repository\Exclamation;
use SPHERE\Common\Frontend\Icon\Repository\EyeOpen;
use SPHERE\Common\Frontend\Icon\Repository\Upload;
use SPHERE\Common\Frontend\IFrontendInterface;
use SPHERE\Common\Frontend\Link\Repository\Standard;
use SPHERE\Common\Frontend\Message\Repository\Danger;
use SPHERE\Common\Frontend\Message\Repository\Success;
use SPHERE\Common\Frontend\Message\Repository\Warning;
use SPHERE\Common\Frontend\Table\Structure\TableData;
use SPHERE\Common\Main;
use SPHERE\Common\Window\Navigation\Link;
use SPHERE\Common\Window\Redirect;
use SPHERE\Common\Window\Stage;
use SPHERE\Application\IModuleInterface;
use SPHERE\System\Extension\Extension;


// todo integrate into DataMaintenance after merge with 39 "Foerderbedarf/Nachteilsausgleich"

/**
 * Class Restore
 *
 * @package SPHERE\Application\Platform\System\Restore
 */
class Restore extends Extension implements IModuleInterface
{
    public static function registerModule()
    {

        Main::getDisplay()->addModuleNavigation(
            new Link(new Link\Route('SPHERE\Application\Platform\System\DataMaintenance\Restore\Person'),
                new Link\Name('Daten Wiederherstellen'))
        );
        Main::getDispatcher()->registerRoute(
            Main::getDispatcher()->createRoute('SPHERE\Application\Platform\System\DataMaintenance\Restore\Person',
                __CLASS__ . '::frontendPersonRestore'
            )
        );
        Main::getDispatcher()->registerRoute(
            Main::getDispatcher()->createRoute('SPHERE\Application\Platform\System\DataMaintenance\Restore\Person\Selected',
                __CLASS__ . '::frontendPersonRestoreSelected'
            )
        );
    }

    /**
     * @return IServiceInterface
     */
    public static function useService()
    {

    }

    /**
     * @return IFrontendInterface
     */
    public static function useFrontend()
    {

    }

    /**
     * @return Stage
     */
    public static function frontendPersonRestore()
    {
        $stage = new Stage('Personen Wiederherstellen', 'Übersicht');

        $dataList = array();
        if (($tblPersonList = Person::useService()->getPersonAllBySoftRemove())) {
            foreach ($tblPersonList as $tblPerson) {
                if (($date = $tblPerson->getEntityRemove())) {
                    $tblAddress = Address::useService()->getAddressByPerson($tblPerson, true);
                    $dataList[] = array(
                        'EntityRemove' => $date->format('d.m.Y'),
                        'Time' => $date->format('H:i:s'),
                        'Name' => $tblPerson->getLastFirstName(),
                        'Address' => $tblAddress ? $tblAddress->getGuiString() : '',
                        'Option' => new Standard(
                            '',
                            '\Platform\System\DataMaintenance\Restore\Person\Selected',
                            new EyeOpen(),
                            array(
                                'PersonId' => $tblPerson->getId()
                            ),
                            'Anzeigen'
                        )
                    );
                }
            }
        }

        $stage->setContent(
            empty($dataList)
                ? new Warning('Es sind keine soft gelöschten Person vorhanden.', new Exclamation())
                : new TableData(
                $dataList,
                null,
                array(
                    'EntityRemove' => 'Gelöscht am',
                    'Time' => 'Uhrzeit',
                    'Name' => 'Name',
                    'Address' => 'Adresse',
                    'Option' => ''
                ),
                array(
                    'order' => array(
                        array('0', 'desc'),
                        array('1', 'desc'),
                    ),
                    'columnDefs' => array(
                        array('type' => 'de_date', 'targets' => 0),
                        array('type' => 'de_time', 'targets' => 1),
                        array('type' => Consumer::useService()->getGermanSortBySetting(), 'targets' => 2),
                        array('width' => '1%', 'targets' => -1),
                    ),
                )
            )
        );

        return $stage;
    }

    /**
     * @param null $PersonId
     * @param bool $IsRestore
     *
     * @return Stage|string
     */
    public function frontendPersonRestoreSelected($PersonId = null, $IsRestore = false)
    {

        if (($tblPerson = Person::useService()->getPersonById($PersonId, true))) {
            $stage = new Stage('Person Wiederherstellen', 'Anzeigen');
            $stage->addButton(
                new Standard(
                    'Zurück',
                    '/Platform/System/DataMaintenance/Restore/Person',
                    new ChevronLeft()
                )
            );


            if (!$IsRestore) {
                $stage->addButton(
                    new Standard(
                        'Alle Daten wiederherstellen',
                        '/Platform/System/DataMaintenance/Restore/Person/Selected',
                        new Upload(),
                        array(
                            'PersonId' => $PersonId,
                            'IsRestore' => true
                        )
                    )
                );
            }

            if ($IsRestore) {
                $columns =  array(
                    'Number' => '#',
                    'Type' => 'Typ',
                    'Value' => 'Wert'
                );
            } else {
                $columns =  array(
                    'Number' => '#',
                    'Type' => 'Typ',
                    'Value' => 'Wert',
                    'EntityRemove' => 'Gelöscht am'
                );
            }

            $stage->setContent(
                ($IsRestore ? new Success('Die Daten wurden wieder hergestellt.', new \SPHERE\Common\Frontend\Icon\Repository\Success()) : '')
                . new TableData(
                    Person::useService()->getRestoreDetailList($tblPerson, $IsRestore),
                    null,
                    $columns,
                    array(
                        "paging" => false, // Deaktivieren Blättern
                        "iDisplayLength" => -1,    // Alle Einträge zeigen
                    )
                )
            );

            return $stage;
        } else {
            return new Stage('Person Wiederherstellen', 'Anzeigen')
                . new Danger('Die Person wurde nicht gefunden', new Exclamation())
                . new Redirect('/Platform/System/DataMaintenance/Restore/Person', Redirect::TIMEOUT_ERROR);
        }
    }
}
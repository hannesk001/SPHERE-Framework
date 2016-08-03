<?php
namespace SPHERE\Application\Api\Reporting\Custom\Herrnhut;

use MOC\V\Core\FileSystem\FileSystem;
use SPHERE\Application\Education\Lesson\Division\Division;
use SPHERE\Application\Reporting\Custom\Herrnhut\Person\Person;

/**
 * Class Common
 *
 * @package SPHERE\Application\Api\Reporting\Custom\Herrnhut
 */
class Common
{

    /**
     * @param null $DivisionId
     *
     * @return string|bool
     */
    public function downloadSignList($DivisionId = null)
    {

        $tblDivision = Division::useService()->getDivisionById($DivisionId);
        if ($tblDivision) {
            $PersonList = Person::useService()->createSignList($tblDivision);
            if ($PersonList) {
                $tblPersonList = Division::useService()->getStudentAllByDivision($tblDivision);
                if ($tblPersonList) {
                    $fileLocation = Person::useService()->createSignListExcel($PersonList, $tblPersonList);

                    return FileSystem::getDownload($fileLocation->getRealPath(),
                        "Herrnhut Unterschriftenliste ".$tblDivision->getDisplayName()
                        ." ".date("Y-m-d H:i:s").".xlsx")->__toString();
                }
            }
        }

        return false;
    }

    /**
     * @param null $DivisionId
     *
     * @return string|bool
     */
    public function downloadLanguageList($DivisionId = null)
    {

        $tblDivision = Division::useService()->getDivisionById($DivisionId);
        if ($tblDivision) {
            $PersonList = Person::useService()->createLanguageList($tblDivision);
            if ($PersonList) {
                $tblPersonList = Division::useService()->getStudentAllByDivision($tblDivision);
                if ($tblPersonList) {
                    $fileLocation = Person::useService()->createLanguageListExcel($PersonList, $tblPersonList);

                    return FileSystem::getDownload($fileLocation->getRealPath(),
                        "Herrnhut Fremdsprachenliste ".$tblDivision->getDisplayName()
                        ." ".date("Y-m-d H:i:s").".xlsx")->__toString();
                }
            }
        }

        return false;
    }
}

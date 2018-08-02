<?php
namespace SPHERE\Application\Contact\Address;

use SPHERE\Application\Contact\Address\Service\Data;
use SPHERE\Application\Contact\Address\Service\Entity\TblAddress;
use SPHERE\Application\Contact\Address\Service\Entity\TblCity;
use SPHERE\Application\Contact\Address\Service\Entity\TblState;
use SPHERE\Application\Contact\Address\Service\Entity\TblToCompany;
use SPHERE\Application\Contact\Address\Service\Entity\TblToPerson;
use SPHERE\Application\Contact\Address\Service\Entity\TblType;
use SPHERE\Application\Contact\Address\Service\Entity\ViewAddressToCompany;
use SPHERE\Application\Contact\Address\Service\Entity\ViewAddressToPerson;
use SPHERE\Application\Contact\Address\Service\Setup;
use SPHERE\Application\Corporation\Company\Service\Entity\TblCompany;
use SPHERE\Application\People\Person\Service\Entity\TblPerson;
use SPHERE\Common\Frontend\Form\IFormInterface;
use SPHERE\Common\Frontend\Icon\Repository\Ban;
use SPHERE\Common\Frontend\Message\Repository\Danger;
use SPHERE\Common\Frontend\Message\Repository\Success;
use SPHERE\Common\Window\Redirect;
use SPHERE\System\Database\Binding\AbstractService;

/**
 * Class Service
 *
 * @package SPHERE\Application\Contact\Address
 */
class Service extends AbstractService
{

    /**
     * @return false|ViewAddressToPerson[]
     */
    public function viewAddressToPersonAll()
    {

        return (new Data($this->getBinding()))->viewAddressToPersonAll();
    }

    /**
     * @return false|ViewAddressToCompany[]
     */
    public function viewAddressToCompanyAll()
    {

        return (new Data($this->getBinding()))->viewAddressToCompanyAll();
    }

    /**
     * @param bool $doSimulation
     * @param bool $withData
     *
     * @return string
     */
    public function setupService($doSimulation, $withData)
    {

        $Protocol = (new Setup($this->getStructure()))->setupDatabaseSchema($doSimulation);
        if (!$doSimulation && $withData) {
            (new Data($this->getBinding()))->setupDatabaseContent();
        }
        return $Protocol;
    }

    /**
     * @param integer $Id
     *
     * @return bool|TblCity
     */
    public function getCityById($Id)
    {

        return (new Data($this->getBinding()))->getCityById($Id);
    }

    /**
     * @param integer $Id
     *
     * @return bool|TblAddress
     */
    public function getAddressById($Id)
    {

        return (new Data($this->getBinding()))->getAddressById($Id);
    }

    /**
     * @return bool|TblCity[]
     */
    public function getCityAll()
    {

        return (new Data($this->getBinding()))->getCityAll();
    }

    /**
     * @return bool|TblState[]
     */
    public function getStateAll()
    {

        return (new Data($this->getBinding()))->getStateAll();
    }

    /**
     * @param string $Name
     *
     * @return bool|TblState
     */
    public function getStateByName($Name)
    {

        return (new Data($this->getBinding()))->getStateByName($Name);
    }

    /**
     * @return bool|TblAddress[]
     */
    public function getAddressAll()
    {

        return (new Data($this->getBinding()))->getAddressAll();
    }

    /**
     * @return bool|TblType[]
     */
    public function getTypeAll()
    {

        return (new Data($this->getBinding()))->getTypeAll();
    }

    /**
     * @param IFormInterface $Form
     * @param TblPerson      $tblPerson
     * @param                $Street
     * @param                $City
     * @param                $Type
     *
     * @return bool
     */
    public function checkFormAddressToPerson(
        IFormInterface &$Form,
        TblPerson $tblPerson,
        $Street,
        $City,
        $Type
    ) {

        $Error = false;
        if (isset($Street['Name']) && empty($Street['Name'])) {
            $Form->setError('Street[Name]', 'Bitte geben Sie eine Strasse an');
            $Error = true;
        } else {
            $Form->setSuccess('Street[Name]');
        }
        if (isset($Street['Number']) && empty($Street['Number'])) {
            $Form->setError('Street[Number]', 'Bitte geben Sie eine Hausnummer an');
            $Error = true;
        } else {
            $Form->setSuccess('Street[Number]');
        }

        if (isset($City['Code']) && empty($City['Code'])) {
            $Form->setError('City[Code]', 'Bitte geben Sie eine Postleitzahl ein');
            $Error = true;
        } else {
            $Form->setSuccess('City[Code]');
        }
        if (isset($City['Name']) && empty($City['Name'])) {
            $Form->setError('City[Name]', 'Bitte geben Sie einen Namen ein');
            $Error = true;
        } else {
            $Form->setSuccess('City[Name]');
        }
        if (!($tblType = $this->getTypeById($Type['Type']))) {
            $Form->setError('Type[Type]', 'Bitte geben Sie einen Typ ein');
            $Error = true;
        } else {
            $Form->setSuccess('Type[Type]');
            // control there is no other MainAddress
            if ($tblType->getName() == 'Hauptadresse') {
                $tblAddressMain = Address::useService()->getAddressByPerson($tblPerson);
                if ($tblAddressMain) {
                    $Form->setError('Type[Type]', '"'.$tblPerson->getFullName()
                        .'" besitzt bereits eine Hauptadresse');
                    $Error = true;
                }
            }
        }
        return $Error;
    }

    /**
     * @param IFormInterface $Form
     * @param TblPerson $tblPerson
     * @param array $Street
     * @param array $City
     * @param integer $State
     * @param array $Type
     * @param string $County
     * @param string $Nation
     * @param $Group
     *
     * @return IFormInterface|string|TblToPerson
     */
    public function createAddressToPerson(
        IFormInterface $Form,
        TblPerson $tblPerson,
        $Street,
        $City,
        $State,
        $Type,
        $County,
        $Nation,
        $Group
    ) {

        /**
         * Skip to Frontend
         */
        if (null === $Street
            && null === $City
            && null === $State
        ) {
            return $Form;
        }

        $Error = $this->checkFormAddressToPerson($Form, $tblPerson, $Street, $City, $Type);
        $tblType = $this->getTypeById($Type['Type']);

        if (!$Error && $tblType) {
            if ($State) {
                $tblState = $this->getStateById($State);
            } else {
                $tblState = null;
            }

            $tblCity = (new Data($this->getBinding()))->createCity(
                $City['Code'], $City['Name'], $City['District']
            );
            $tblAddress = (new Data($this->getBinding()))->createAddress(
                $tblState, $tblCity, $Street['Name'], $Street['Number'], '', $County, $Nation
            );

            if ((new Data($this->getBinding()))->addAddressToPerson($tblPerson, $tblAddress, $tblType,
                $Type['Remark'])
            ) {
                return new Success(new \SPHERE\Common\Frontend\Icon\Repository\Success().' Die Adresse wurde erfolgreich hinzugefügt')
                    .new Redirect('/People/Person', Redirect::TIMEOUT_SUCCESS,
                        array('Id' => $tblPerson->getId(), 'Group' => $Group));
            } else {
                return new Danger(new Ban().' Die Adresse konnte nicht hinzugefügt werden')
                    .new Redirect('/People/Person', Redirect::TIMEOUT_ERROR,
                        array('Id' => $tblPerson->getId(), 'Group' => $Group));
            }
        }
        return $Form;
    }

    /**
     * @param TblPerson $tblPerson
     * @param array     $Street
     * @param array     $City
     * @param integer   $State
     * @param array     $Type
     * @param string    $County
     * @param string    $Nation
     *
     * @return IFormInterface|string|TblToPerson
     */
    public function createAddressToPersonByApi(
        TblPerson $tblPerson,
        $Street = array(),
        $City = array(),
        $State,
        $Type,
        $County,
        $Nation
    ) {

        $tblType = $this->getTypeById($Type['Type']);
        if ($tblType) {
            if ($State) {
                $tblState = $this->getStateById($State);
            } else {
                $tblState = null;
            }
            $tblCity = (new Data($this->getBinding()))->createCity(
                $City['Code'], $City['Name'], $City['District']
            );
            $tblAddress = (new Data($this->getBinding()))->createAddress(
                $tblState, $tblCity, $Street['Name'], $Street['Number'], '', $County, $Nation
            );

            if ($tblType->getName() == 'Hauptadresse'
                && $tblToPersonList = Address::useService()->getAddressAllByPersonAndType($tblPerson, $tblType)
            ) {
                $tblToPerson = current($tblToPersonList);
                if ($tblToPerson->getServiceTblPerson()) {
                    // Update current if exist
                    if ((new Data($this->getBinding()))->updateAddressToPerson(
                        $tblToPerson,
                        $tblAddress,
                        $tblType,
                        $Type['Remark'])
                    ) {
                        return true;
                    }
                }
            }
            // Create if not exist
            if ((new Data($this->getBinding()))->addAddressToPerson($tblPerson, $tblAddress, $tblType,
                $Type['Remark'])
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param integer $Id
     *
     * @return bool|TblType
     */
    public function getTypeById($Id)
    {

        return (new Data($this->getBinding()))->getTypeById($Id);
    }

    /**
     * @param string $Name
     *
     * @return bool|TblType
     */
    public function getTypeByName($Name)
    {

        return (new Data($this->getBinding()))->getTypeByName($Name);
    }

    /**
     * @param integer $Id
     *
     * @return bool|TblState
     */
    public function getStateById($Id)
    {

        return (new Data($this->getBinding()))->getStateById($Id);
    }

    /**
     * @param TblPerson $tblPerson
     * @param $StreetName
     * @param $StreetNumber
     * @param $CityCode
     * @param $CityName
     * @param $CityDistrict
     * @param $PostOfficeBox
     * @param string $County
     * @param string $Nation
     * @param TblState $tblState
     *
     * @return TblToPerson
     */
    public function insertAddressToPerson(
        TblPerson $tblPerson,
        $StreetName,
        $StreetNumber,
        $CityCode,
        $CityName,
        $CityDistrict,
        $PostOfficeBox,
        $County = '',
        $Nation = '',
        TblState $tblState = null
    ) {

        $tblCity = (new Data($this->getBinding()))->createCity($CityCode, $CityName, $CityDistrict);
//        $tblState = null;

        $tblAddress = (new Data($this->getBinding()))->createAddress(
            $tblState,
            $tblCity,
            $StreetName,
            $StreetNumber,
            $PostOfficeBox,
            $County,
            $Nation
        );

        return (new Data($this->getBinding()))->addAddressToPerson($tblPerson, $tblAddress,
            Address::useService()->getTypeById(1), '');
    }

    /**
     * @param TblCompany $tblCompany
     * @param $StreetName
     * @param $StreetNumber
     * @param $CityCode
     * @param $CityName
     * @param $CityDistrict
     * @param $PostOfficeBox
     * @param string $County
     * @param string $Nation
     *
     * @return TblToCompany
     */
    public function insertAddressToCompany(
        TblCompany $tblCompany,
        $StreetName,
        $StreetNumber,
        $CityCode,
        $CityName,
        $CityDistrict,
        $PostOfficeBox,
        $County = '',
        $Nation = ''
    ) {

        $tblCity = (new Data($this->getBinding()))->createCity($CityCode, $CityName, $CityDistrict);
        $tblState = null;

        $tblAddress = (new Data($this->getBinding()))->createAddress(
            $tblState,
            $tblCity,
            $StreetName,
            $StreetNumber,
            $PostOfficeBox,
            $County,
            $Nation
        );

        $tblType = Address::useService()->getTypeById(1);
        // Nur eine Hauptadresse
        if ($this->getAddressAllByCompanyAndType($tblCompany, $tblType)) {
            return ( new Data($this->getBinding()) )->addAddressToCompany($tblCompany, $tblAddress,
                Address::useService()->getTypeById(2), '');
        }
        return ( new Data($this->getBinding()) )->addAddressToCompany($tblCompany, $tblAddress, $tblType, '');
    }

    /**
     * @param IFormInterface $Form
     * @param TblToPerson $tblToPerson
     * @param array $Street
     * @param array $City
     * @param int $State
     * @param array $Type
     * @param                $County
     * @param                $Nation
     * @param $Group
     *
     * @return IFormInterface|string
     */
    public function updateAddressToPerson(
        IFormInterface $Form,
        TblToPerson $tblToPerson,
        $Street,
        $City,
        $State,
        $Type,
        $County,
        $Nation,
        $Group
    ) {

        /**
         * Skip to Frontend
         */
        if (null === $Street
            && null === $City
            && null === $State
        ) {
            return $Form;
        }

        $Error = false;

        if (isset( $Street['Name'] ) && empty( $Street['Name'] )) {
            $Form->setError('Street[Name]', 'Bitte geben Sie eine Strasse an');
            $Error = true;
        } else {
            $Form->setSuccess('Street[Name]');
        }
        if (isset( $Street['Number'] ) && empty( $Street['Number'] )) {
            $Form->setError('Street[Number]', 'Bitte geben Sie eine Hausnummer an');
            $Error = true;
        } else {
            $Form->setSuccess('Street[Number]');
        }

        if (isset( $City['Code'] ) && empty( $City['Code'] )) {
            $Form->setError('City[Code]', 'Bitte geben Sie eine Postleitzahl ein');
            $Error = true;
        } else {
            $Form->setSuccess('City[Code]');
        }
        if (isset( $City['Name'] ) && empty( $City['Name'] )) {
            $Form->setError('City[Name]', 'Bitte geben Sie einen Namen ein');
            $Error = true;
        } else {
            $Form->setSuccess('City[Name]');
        }
        if (!( $tblType = $this->getTypeById($Type['Type']) )) {
            $Form->setError('Type[Type]', 'Bitte geben Sie einen Typ ein');
            $Error = true;
        } else {
            $Form->setSuccess('Type[Type]');
        }

        if (!$Error) {
            $tblState = $this->getStateById($State);
            if (!$tblState) {
                $tblState = null;
            }
            $tblCity = (new Data($this->getBinding()))->createCity(
                $City['Code'], $City['Name'], $City['District']
            );
            $tblAddress = (new Data($this->getBinding()))->createAddress(
                $tblState, $tblCity, $Street['Name'], $Street['Number'], '', $County, $Nation
            );
            if ($tblToPerson->getServiceTblPerson()) {
                // Update current
                if (( new Data($this->getBinding()) )->updateAddressToPerson(
                    $tblToPerson,
                    $tblAddress,
                    $tblType,
                    $Type['Remark'])
                ) {
                    return new Success(new \SPHERE\Common\Frontend\Icon\Repository\Success().' Die Adresse wurde erfolgreich geändert')
                    .new Redirect('/People/Person', Redirect::TIMEOUT_SUCCESS,
                        array('Id' => $tblToPerson->getServiceTblPerson()->getId(), 'Group' => $Group));
                } else {
                    return new Danger(new Ban().' Die Adresse konnte nicht geändert werden')
                    .new Redirect('/People/Person', Redirect::TIMEOUT_ERROR,
                        array('Id' => $tblToPerson->getServiceTblPerson()->getId(), 'Group' => $Group));
                }
            } else {
                new Danger('Person nicht gefunden', new Ban());
            }
        }
        return $Form;
    }

    /**
     * @param IFormInterface $Form
     * @param TblToCompany $tblToCompany
     * @param array $Street
     * @param array $City
     * @param int $State
     * @param array $Type
     * @param                $County
     * @param                $Nation
     * @param $Group
     *
     * @return IFormInterface|string
     */
    public function updateAddressToCompany(
        IFormInterface $Form,
        TblToCompany $tblToCompany,
        $Street,
        $City,
        $State,
        $Type,
        $County,
        $Nation,
        $Group
    ) {

        /**
         * Skip to Frontend
         */
        if (null === $Street
            && null === $City
            && null === $State
        ) {
            return $Form;
        }

        $Error = false;

        if (isset( $Street['Name'] ) && empty( $Street['Name'] )) {
            $Form->setError('Street[Name]', 'Bitte geben Sie eine Strasse an');
            $Error = true;
        } else {
            $Form->setSuccess('Street[Name]');
        }
        if (isset( $Street['Number'] ) && empty( $Street['Number'] )) {
            $Form->setError('Street[Number]', 'Bitte geben Sie eine Hausnummer an');
            $Error = true;
        } else {
            $Form->setSuccess('Street[Number]');
        }

        if (isset( $City['Code'] ) && empty( $City['Code'] )) {
            $Form->setError('City[Code]', 'Bitte geben Sie eine Postleitzahl ein');
            $Error = true;
        } else {
            $Form->setSuccess('City[Code]');
        }
        if (isset( $City['Name'] ) && empty( $City['Name'] )) {
            $Form->setError('City[Name]', 'Bitte geben Sie einen Namen ein');
            $Error = true;
        } else {
            $Form->setSuccess('City[Name]');
        }
        if (!( $tblType = $this->getTypeById($Type['Type']) )) {
            $Form->setError('Type[Type]', 'Bitte geben Sie einen Typ ein');
            $Error = true;
        } else {
            $Form->setSuccess('Type[Type]');
        }

        if (!$Error) {
            $tblState = $this->getStateById($State);
            if (!$tblState) {
                $tblState = null;
            }
            $tblCity = (new Data($this->getBinding()))->createCity(
                $City['Code'], $City['Name'], $City['District']
            );
            $tblAddress = (new Data($this->getBinding()))->createAddress(
                $tblState, $tblCity, $Street['Name'], $Street['Number'], '', $County, $Nation
            );
            // Remove current
            (new Data($this->getBinding()))->removeAddressToCompany($tblToCompany);

            if ($tblToCompany->getServiceTblCompany()) {
                // Add new
                if ((new Data($this->getBinding()))->addAddressToCompany($tblToCompany->getServiceTblCompany(),
                    $tblAddress,
                    $tblType,
                    $Type['Remark'])
                ) {
                    return new Success(new \SPHERE\Common\Frontend\Icon\Repository\Success().' Die Adresse wurde erfolgreich geändert')
                    .new Redirect('/Corporation/Company', Redirect::TIMEOUT_SUCCESS,
                        array('Id' => $tblToCompany->getServiceTblCompany()->getId(), 'Group' => $Group));
                } else {
                    return new Danger(new Ban().' Die Adresse konnte nicht geändert werden')
                    .new Redirect('/Corporation/Company', Redirect::TIMEOUT_ERROR,
                        array('Id' => $tblToCompany->getServiceTblCompany()->getId(), 'Group' => $Group));
                }
            } else {
                new Danger('Institution nicht gefunden', new Ban());
            }
        }
        return $Form;
    }

    /**
     * @param IFormInterface $Form
     * @param TblCompany $tblCompany
     * @param array $Street
     * @param array $City
     * @param integer $State
     * @param array $Type
     * @param                $County
     * @param                $Nation
     * @param $Group
     *
     * @return IFormInterface|string
     */
    public function createAddressToCompany(
        IFormInterface $Form,
        TblCompany $tblCompany,
        $Street,
        $City,
        $State,
        $Type,
        $County,
        $Nation,
        $Group
    ) {

        /**
         * Skip to Frontend
         */
        if (null === $Street
            && null === $City
            && null === $State
        ) {
            return $Form;
        }

        $Error = false;

        if (isset( $Street['Name'] ) && empty( $Street['Name'] )) {
            $Form->setError('Street[Name]', 'Bitte geben Sie eine Strasse an');
            $Error = true;
        } else {
            $Form->setSuccess('Street[Name]');
        }
        if (isset( $Street['Number'] ) && empty( $Street['Number'] )) {
            $Form->setError('Street[Number]', 'Bitte geben Sie eine Hausnummer an');
            $Error = true;
        } else {
            $Form->setSuccess('Street[Number]');
        }

        if (isset( $City['Code'] ) && empty( $City['Code'] )) {
            $Form->setError('City[Code]', 'Bitte geben Sie eine Postleitzahl ein');
            $Error = true;
        } else {
            $Form->setSuccess('City[Code]');
        }
        if (isset( $City['Name'] ) && empty( $City['Name'] )) {
            $Form->setError('City[Name]', 'Bitte geben Sie einen Namen ein');
            $Error = true;
        } else {
            $Form->setSuccess('City[Name]');
        }
        if (!( $tblType = $this->getTypeById($Type['Type']) )) {
            $Form->setError('Type[Type]', 'Bitte geben Sie einen Typ ein');
            $Error = true;
        } else {
            $Form->setSuccess('Type[Type]');
        }

        if (!$Error) {
            if ($State) {
                $tblState = $this->getStateById($State);
            } else {
                $tblState = null;
            }
            $tblCity = (new Data($this->getBinding()))->createCity(
                $City['Code'], $City['Name'], $City['District']
            );
            $tblAddress = (new Data($this->getBinding()))->createAddress(
                $tblState, $tblCity, $Street['Name'], $Street['Number'], '', $County, $Nation
            );

            if ((new Data($this->getBinding()))->addAddressToCompany($tblCompany, $tblAddress, $tblType,
                $Type['Remark'])
            ) {
                return new Success(new \SPHERE\Common\Frontend\Icon\Repository\Success().' Die Adresse wurde erfolgreich hinzugefügt')
                .new Redirect('/Corporation/Company', Redirect::TIMEOUT_SUCCESS,
                    array('Id' => $tblCompany->getId(), 'Group' => $Group));
            } else {
                return new Danger(new Ban().' Die Adresse konnte nicht hinzugefügt werden')
                .new Redirect('/Corporation/Company', Redirect::TIMEOUT_ERROR,
                    array('Id' => $tblCompany->getId(), 'Group' => $Group));
            }
        }
        return $Form;
    }

    /**
     * @param TblPerson $tblPerson
     *
     * @return bool|TblToPerson[]
     */
    public function getAddressAllByPerson(TblPerson $tblPerson)
    {

        return (new Data($this->getBinding()))->getAddressAllByPerson($tblPerson);
    }

    /**
     * @param TblPerson $tblPerson
     * @param TblType   $tblType
     *
     * @return bool|TblToPerson[]
     */
    public function getAddressAllByPersonAndType(TblPerson $tblPerson, TblType $tblType)
    {

        return (new Data($this->getBinding()))->getAddressAllByPersonAndType($tblPerson, $tblType);
    }


    /** get Main Address (Type ID 1)
     *
     * @param TblPerson $tblPerson
     * @param bool $isForced
     *
     * @return bool|TblAddress
     */
    public function getAddressByPerson(TblPerson $tblPerson, $isForced = false)
    {

        return (new Data($this->getBinding()))->getAddressByPerson($tblPerson, $isForced);
    }

    /** get Main Address (Type ID 1)
     *
     * @param TblPerson $tblPerson
     *
     * @return false|TblToPerson
     */
    public function getAddressToPersonByPerson(TblPerson $tblPerson)
    {

        return ( new Data($this->getBinding()) )->getAddressToPersonByPerson($tblPerson);
    }

    /**
     * @param TblCompany $tblCompany
     *
     * @return bool|TblAddress
     */
    public function getAddressByCompany(TblCompany $tblCompany)
    {

        return (new Data($this->getBinding()))->getAddressByCompany($tblCompany);
    }

    /**
     * @param TblCompany $tblCompany
     *
     * @return bool|TblToCompany[]
     */
    public function getAddressAllByCompany(TblCompany $tblCompany)
    {

        return (new Data($this->getBinding()))->getAddressAllByCompany($tblCompany);
    }

    /**
     * @param TblCompany $tblCompany
     * @param TblType    $tblType
     *
     * @return bool|TblToCompany[]
     */
    public function getAddressAllByCompanyAndType(TblCompany $tblCompany, TblType $tblType)
    {

        return (new Data($this->getBinding()))->getAddressAllByCompanyAndType($tblCompany, $tblType);
    }

    /**
     * @param integer $Id
     *
     * @return bool|TblToPerson
     */
    public function getAddressToPersonById($Id)
    {

        return (new Data($this->getBinding()))->getAddressToPersonById($Id);
    }


    /**
     * @param integer $Id
     *
     * @return bool|TblToCompany
     */
    public function getAddressToCompanyById($Id)
    {

        return (new Data($this->getBinding()))->getAddressToCompanyById($Id);
    }

    /**
     * @param TblToPerson $tblToPerson
     * @param bool $IsSoftRemove
     *
     * @return bool
     */
    public function removeAddressToPerson(TblToPerson $tblToPerson, $IsSoftRemove = false)
    {

        return (new Data($this->getBinding()))->removeAddressToPerson($tblToPerson, $IsSoftRemove);
    }

    /**
     * @param TblToCompany $tblToCompany
     *
     * @return bool
     */
    public function removeAddressToCompany(TblToCompany $tblToCompany)
    {

        return (new Data($this->getBinding()))->removeAddressToCompany($tblToCompany);
    }

    /**
     * @param TblPerson $tblPerson
     *
     * @return array of TblAddress->Id
     */
    public function fetchIdAddressAllByPerson(TblPerson $tblPerson)
    {

        return (new Data($this->getBinding()))->fetchIdAddressAllByPerson($tblPerson);
    }

    /**
     * @param array $IdArray of TblAddress->Id
     *
     * @return TblAddress[]
     */
    public function fetchAddressAllByIdList($IdArray)
    {

        return (new Data($this->getBinding()))->fetchAddressAllByIdList($IdArray);
    }

    /**
     * @param TblPerson $tblPerson
     * @param bool $IsSoftRemove
     */
    public function removeAddressAllByPerson(TblPerson $tblPerson, $IsSoftRemove = false)
    {

        if (($tblAddressToPersonList = $this->getAddressAllByPerson($tblPerson))){
            foreach($tblAddressToPersonList as $tblToPerson){
                $this->removeAddressToPerson($tblToPerson, $IsSoftRemove);
            }
        }
    }
}

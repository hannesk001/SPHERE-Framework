<?php
namespace SPHERE\Application\Setting\User\Account\Service;

use SPHERE\Application\People\Person\Service\Entity\TblPerson;
use SPHERE\Application\Platform\Gatekeeper\Authorization\Account\Service\Entity\TblAccount;
use SPHERE\Application\Platform\System\Protocol\Protocol;
use SPHERE\Application\Setting\User\Account\Service\Entity\TblUserAccount;
use SPHERE\System\Database\Binding\AbstractData;

/**
 * Class Data
 *
 * @package SPHERE\Application\Setting\User\Account\Service
 */
class Data extends AbstractData
{

    public function setupDatabaseContent()
    {

    }

    /**
     * @param integer $Id
     *
     * @return bool|TblUserAccount
     */
    public function getUserAccountById($Id)
    {

        return $this->getCachedEntityById(__METHOD__, $this->getConnection()->getEntityManager(),
            'TblUserAccount', $Id);
    }

    /**
     * @param TblPerson $tblPerson
     *
     * @return false|TblUserAccount
     */
    public function getUserAccountByPerson(TblPerson $tblPerson)
    {

        return $this->getCachedEntityBy(__METHOD__, $this->getConnection()->getEntityManager(), 'TblUserAccount',
            array(
                TblUserAccount::ATTR_SERVICE_TBL_PERSON => $tblPerson->getId()
            ));
    }

    /**
     * @param TblAccount $tblAccount
     *
     * @return false|TblUserAccount
     */
    public function getUserAccountByAccount(TblAccount $tblAccount)
    {

        return $this->getCachedEntityBy(__METHOD__, $this->getConnection()->getEntityManager(), 'TblUserAccount',
            array(
                TblUserAccount::ATTR_SERVICE_TBL_ACCOUNT => $tblAccount->getId()
            ));
    }

    /**
     * @return bool|TblUserAccount[]
     */
    public function getUserAccountAll()
    {

        return $this->getCachedEntityList(__METHOD__, $this->getConnection()->getEntityManager(),
            'TblUserAccount');
    }

    /**
     * @param string $Type
     *
     * @return bool|TblUserAccount[]
     */
    public function getUserAccountAllByType($Type)
    {

        return $this->getCachedEntityListBy(__METHOD__, $this->getConnection()->getEntityManager(), 'TblUserAccount',
            array(
                TblUserAccount::ATTR_TYPE => $Type
            ));
    }

    /**
     * @param \DateTime $dateTime
     *
     * @return false|TblUserAccount[]
     */
    public function getUserAccountByTimeGroup(\DateTime $dateTime)
    {

        return $this->getCachedEntityListBy(__METHOD__, $this->getConnection()->getEntityManager(), 'TblUserAccount',
            array(
                TblUserAccount::ATTR_GROUP_BY_TIME => $dateTime
            ));
    }

    /**
     * @param string $Type
     *
     * @return bool|TblUserAccount[]
     */
    public function countUserAccountAllByType($Type)
    {

        return $this->getCachedCountBy(__METHOD__, $this->getConnection()->getEntityManager(), 'TblUserAccount',
            array(
                TblUserAccount::ATTR_TYPE => $Type
            ));
    }

    /**
     * @param TblAccount $tblAccount
     * @param TblPerson  $tblPerson
     * @param \DateTime  $TimeStamp
     * @param string     $userPassword
     * @param string     $Type STUDENT|CUSTODY
     *
     * @return TblUserAccount
     */
    public function createUserAccount(
        TblAccount $tblAccount,
        TblPerson $tblPerson,
        \DateTime $TimeStamp,
        $userPassword,
        $Type = 'STUDENT'
    ) {

        $Manager = $this->getConnection()->getEntityManager();
        /** @var TblUserAccount $Entity */
        $Entity = $Manager->getEntity('TblUserAccount')
            ->findOneBy(array(
                TblUserAccount::ATTR_SERVICE_TBL_ACCOUNT => $tblAccount->getId(),
            ));

        if (null === $Entity) {
            $Entity = new TblUserAccount();
            $Entity->setServiceTblAccount($tblAccount);
            $Entity->setServiceTblPerson($tblPerson);
            $Entity->setType($Type);
            $Entity->setUserPassword($userPassword);
            $Entity->setAccountPassword(hash('sha256', $userPassword));
//            $Entity->setExportDate(null);
            $Entity->setLastDownloadAccount('');
            $Entity->setGroupByTime($TimeStamp);
            $Manager->saveEntity($Entity);
            Protocol::useService()->createInsertEntry($this->getConnection()->getDatabase(), $Entity);
            return $Entity;
        }
        return $Entity;
    }

    /**
     * @param TblUserAccount[] $tblUserAccountList
     * @param \DateTime        $ExportDate
     * @param string           $UserName
     *
     * @return bool
     */
    public function updateDownloadBulk($tblUserAccountList, $ExportDate, $UserName)
    {

        $Manager = $this->getConnection()->getEntityManager();
        /** @var TblUserAccount $Entity */
        foreach ($tblUserAccountList as $tblUserAccount) {
            $Entity = $Manager->getEntityById('TblUserAccount', $tblUserAccount->getId());
            $Protocol = clone $Entity;
            if (null !== $Entity) {
                $Entity->setExportDate($ExportDate);
                $Entity->setLastDownloadAccount($UserName);
                $Manager->bulkSaveEntity($Entity);
                Protocol::useService()->createUpdateEntry($this->getConnection()->getDatabase(), $Protocol, $Entity,
                    true);
            }
        }
        $Manager->flushCache();
        Protocol::useService()->flushBulkEntries();
        return true;
    }

    /**
     * @param TblUserAccount[] $tblUserAccountList
     *
     * @return bool
     */
    public function updateUserAccountClearPassword($tblUserAccountList)
    {

        $Manager = $this->getConnection()->getEntityManager();
        foreach ($tblUserAccountList as $tblUserAccount) {
            /** @var TblUserAccount $Entity */
            $Entity = $Manager->getEntityById('TblUserAccount', $tblUserAccount->getId());
            $Protocol = clone $Entity;
            if (null !== $Entity) {
                $Entity->setUserPassword('');
                $Manager->bulkSaveEntity($Entity);
                Protocol::useService()->createUpdateEntry($this->getConnection()->getDatabase(), $Protocol, $Entity,
                    true);
            }
        }
        $Manager->flushCache();
        Protocol::useService()->flushBulkEntries();
        return true;
    }

    /**
     * @param TblUserAccount $tblUserAccount
     *
     * @return bool
     */
    public function removeUserAccount(TblUserAccount $tblUserAccount)
    {

        $Manager = $this->getConnection()->getEntityManager();
        /** @var TblUserAccount $Entity */
        $Entity = $Manager->getEntityById('TblUserAccount', $tblUserAccount->getId());
        if (null !== $Entity) {
            Protocol::useService()->createDeleteEntry($this->getConnection()->getDatabase(), $Entity);
            $Manager->killEntity($Entity);
            return true;
        }
        return false;
    }
}

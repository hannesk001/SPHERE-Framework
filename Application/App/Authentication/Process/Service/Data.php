<?php

namespace SPHERE\Application\App\Authentication\Process\Service;

use Exception;
use SPHERE\Application\App\Authentication\Process\Service\Entity\TblFactor;
use SPHERE\Application\App\Authentication\Process\Service\Entity\TblProcess;
use SPHERE\Application\App\Authentication\Process\Service\Entity\TblStep;
use SPHERE\Application\App\Authentication\Process\Service\Entity\TblToken;
use SPHERE\Application\Platform\Gatekeeper\Authorization\Account\Account;
use SPHERE\Application\Platform\Gatekeeper\Authorization\Account\Service\Entity\TblAccount;
use SPHERE\Application\Platform\Gatekeeper\Authorization\Account\Service\Entity\TblIdentification;
use SPHERE\Application\Platform\System\Protocol\Protocol;
use SPHERE\System\Database\Binding\AbstractData;

/**
 *
 */
class Data extends AbstractData
{
    public function setupDatabaseContent(): void
    {
        $tblFactorCredentials = $this->createFactor(TblFactor::NAME_CREDENTIALS, 'Benutzername & Passwort');
        $tblFactorAuthenticatorApp = $this->createFactor(TblFactor::NAME_AUTHENTICATOR_APP, 'Authenticator App');
        $tblFactorToken = $this->createFactor(TblFactor::NAME_TOKEN, 'Hardware-Schlüssel');

        $this->createStep(null, $tblFactorCredentials, 1);
        if (($tblIdentification = Account::useService()->getIdentificationByName(TblIdentification::NAME_SYSTEM))) {
            $this->createStep($tblIdentification, $tblFactorCredentials, 1);
            $this->createStep($tblIdentification, $tblFactorToken, 2);
        }
        if (($tblIdentification = Account::useService()->getIdentificationByName(TblIdentification::NAME_TOKEN))) {
            $this->createStep($tblIdentification, $tblFactorCredentials, 1);
            $this->createStep($tblIdentification, $tblFactorToken, 2);
        }
        if (($tblIdentification = Account::useService()->getIdentificationByName(TblIdentification::NAME_CREDENTIAL))) {
            $this->createStep($tblIdentification, $tblFactorCredentials, 1);
        }
        if (($tblIdentification = Account::useService()->getIdentificationByName(TblIdentification::NAME_USER_CREDENTIAL))) {
            $this->createStep($tblIdentification, $tblFactorCredentials, 1);
        }
        if (($tblIdentification = Account::useService()->getIdentificationByName(TblIdentification::NAME_AUTHENTICATOR_APP))) {
            $this->createStep($tblIdentification, $tblFactorCredentials, 1);
            $this->createStep($tblIdentification, $tblFactorAuthenticatorApp, 2);
        }
    }

    public function createFactor(string $name, ?string $description = null): ?TblFactor
    {
        $connection = $this->getConnection();
        if (null === $connection) {
            return null;
        }
        $manager = $connection->getEntityManager();
        $entity = $manager->getEntity('TblFactor')->findOneBy([TblFactor::ATTR_NAME => $name]);
        if (null === $entity) {
            $entity = new TblFactor();
            $entity->setName($name);
            $entity->setDescription($description);

            $manager->saveEntity($entity);
            Protocol::useService()->createInsertEntry($connection->getDatabase(), $entity);
        }
        return $entity;
    }

    /**
     * @param TblFactor $tblFactor
     * @param string $deviceFactor
     * @param TblAccount|null $tblAccount
     * @param bool|null $isSolved
     *
     * @return TblProcess|null
     */
    public function createProcess(TblFactor $tblFactor, string $deviceFactor, ?TblAccount $tblAccount, ?bool $isSolved): ?TblProcess
    {
        $connection = $this->getConnection();
        if (null === $connection) {
            return null;
        }
        $manager = $connection->getEntityManager();
        $entity = $manager->getEntity('TblProcess')->findOneBy([
            TblProcess::SERVICE_TBL_ACCOUNT => $tblAccount?->getId(),
            TblProcess::ATTR_TBL_FACTOR => $tblFactor->getId()
        ]);
        if (null === $entity) {
            $entity = new TblProcess();
            $entity->setServiceTblAccount($tblAccount);
            $entity->setTblFactor($tblFactor);
            $entity->setDeviceFactor($deviceFactor);
            $entity->setIsSolved($isSolved);

            $manager->saveEntity($entity);
            Protocol::useService()->createInsertEntry($connection->getDatabase(), $entity);
        }
        return $entity;
    }

    public function createStep(?TblIdentification $tblIdentification, TblFactor $tblFactor, ?int $sortOrder): ?TblStep
    {
        $connection = $this->getConnection();
        if (null === $connection) {
            return null;
        }
        $manager = $connection->getEntityManager();
        $entity = $manager->getEntity('TblStep')->findOneBy([
            TblStep::SERVICE_TBL_IDENTIFICATION => $tblIdentification?->getId(),
            TblStep::ATTR_TBL_FACTOR => $tblFactor->getId()
        ]);
        if (null === $entity) {
            $entity = new TblStep();
            $entity->setServiceTblIdentification($tblIdentification);
            $entity->setTblFactor($tblFactor);
            $entity->setSortOrder($sortOrder);

            $manager->saveEntity($entity);
            Protocol::useService()->createInsertEntry($connection->getDatabase(), $entity);
        }
        return $entity;
    }

    /**
     * @param TblAccount $tblAccount
     * @param string $authenticationToken
     *
     * @return TblToken|null
     */
    public function createToken(TblAccount $tblAccount, string $authenticationToken): ?TblToken
    {
        $connection = $this->getConnection();
        if (null === $connection) {
            return null;
        }
        $manager = $connection->getEntityManager();
        $entity = $manager->getEntity('TblToken')->findOneBy([
            TblToken::SERVICE_TBL_ACCOUNT => $tblAccount->getId(),
            TblToken::ATTR_AUTHENTICATION_TOKEN => $authenticationToken
        ]);
        if (null === $entity) {
            $entity = new TblToken();
            $entity->setServiceTblAccount($tblAccount);
            $entity->setAuthenticationToken($authenticationToken);
            // 1 month
            $timeout = 3600 * 24 * 30;
            $entity->setAuthenticationTimeout(time() + $timeout);

            $manager->saveEntity($entity);
            Protocol::useService()->createInsertEntry($connection->getDatabase(), $entity);
        }

        return $entity;
    }

    /**
     * @throws Exception
     */
    public function getFactorById(int $id): ?TblFactor
    {
        $connection = $this->getConnection();
        if (null === $connection) {
            return null;
        }
        /** @var TblFactor $entity */
        $entity = $this->getCachedEntityById(__METHOD__, $connection->getEntityManager(), 'TblFactor', $id);
        if (!$entity) {
            return null;
        }
        return $entity;
    }

    /**
     * @param TblIdentification|null $tblIdentification
     *
     * @return TblStep[]|null
     */
    public function getAllStepByIdentification(?TblIdentification $tblIdentification): ?array
    {
        $connection = $this->getConnection();
        if (null === $connection) {
            return null;
        }
        /** @var TblStep[] $entities */
        $entities = $this->getCachedEntityListBy(__METHOD__, $connection->getEntityManager(), 'TblStep', [
            TblStep::SERVICE_TBL_IDENTIFICATION => $tblIdentification?->getId()
        ], [TblStep::ATTR_SORT_ORDER => self::ORDER_ASC]);
        if (!$entities) {
            return null;
        }
        return $entities;
    }

    /**
     * @param string $deviceFactor
     * @param TblAccount|null $tblAccount
     *
     * @return TblProcess[]|null
     */
    public function getAllProcessByDeviceFactor(string $deviceFactor, ?TblAccount $tblAccount): ?array
    {
        $connection = $this->getConnection();
        if (null === $connection) {
            return null;
        }
        $criteria = [
            TblProcess::ATTR_DEVICE_FACTOR => $deviceFactor,
            TblProcess::SERVICE_TBL_ACCOUNT => $tblAccount?->getId()
        ];

//        if (null !== $isSolved) {
//            $criteria[TblProcess::ATTR_IS_SOLVED] = $isSolved;
//        }
        /** @var TblProcess[] $entities */
        $entities = $this->getCachedEntityListBy(__METHOD__, $connection->getEntityManager(), 'TblProcess', $criteria);
        if (!$entities) {
            return null;
        }
        return $entities;
    }

    /**
     * @param TblFactor $tblFactor
     * @param string $deviceFactor
     * @param TblAccount|null $tblAccount
     *
     * @return TblProcess|null
     */
    public function getProcessByFactor(TblFactor $tblFactor, string $deviceFactor, ?TblAccount $tblAccount): ?TblProcess
    {
        $criteria = [
            TblProcess::ATTR_TBL_FACTOR => $tblFactor->getId(),
            TblProcess::ATTR_DEVICE_FACTOR => $deviceFactor,
            TblProcess::SERVICE_TBL_ACCOUNT => $tblAccount?->getId()
        ];

        /** @var TblProcess $tblProcess */
        return ($tblProcess = $this->getCachedEntityBy(__METHOD__, $this->getEntityManager(), 'TblProcess', $criteria))
            ? $tblProcess
            : null;
    }

    /**
     * @param TblAccount $tblAccount
     * @param string $authenticationToken
     *
     * @return TblToken|null
     */
    public function getTokenByAccountAndAuthenticationToken(TblAccount $tblAccount, string $authenticationToken): ?TblToken
    {
        $criteria = [
            TblToken::SERVICE_TBL_ACCOUNT => $tblAccount->getId(),
            TblToken::ATTR_AUTHENTICATION_TOKEN => $authenticationToken
        ];

        /** @var TblToken $tblToken */
        return ($tblToken = $this->getCachedEntityBy(__METHOD__, $this->getEntityManager(), 'TblToken', $criteria))
            ? $tblToken
            : null;
    }
}

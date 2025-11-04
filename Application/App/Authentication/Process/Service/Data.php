<?php

namespace SPHERE\Application\App\Authentication\Process\Service;

use Exception;
use SPHERE\Application\App\Authentication\Authentication;
use SPHERE\Application\App\Authentication\Process\Service\Entity\Internal\Token;
use SPHERE\Application\App\Authentication\Process\Service\Entity\TblFactor;
use SPHERE\Application\App\Authentication\Process\Service\Entity\TblProcess;
use SPHERE\Application\App\Authentication\Process\Service\Entity\TblStep;
use SPHERE\Application\App\Authentication\Process\Service\Entity\TblToken;
use SPHERE\Application\Platform\Gatekeeper\Authorization\Account\Account;
use SPHERE\Application\Platform\Gatekeeper\Authorization\Account\Service\Entity\TblAccount;
use SPHERE\Application\Platform\Gatekeeper\Authorization\Account\Service\Entity\TblIdentification;
use SPHERE\Application\Platform\System\Protocol\Protocol;
use SPHERE\System\Database\Binding\AbstractData;
use SPHERE\System\Database\Fitting\Element;

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
        $tblFactorTokenOrAuthenticatorApp = $this->createFactor(TblFactor::NAME_TOKEN_OR_AUTHENTICATOR_APP, 'Hardware-Schlüssel oder Authenticator App');

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
        // in ssw the identifications are both set
        if (($tblIdentification = Authentication::useService()->getVirtualIdentificationTokenOrAuthenticatorApp())) {
            $this->createStep($tblIdentification, $tblFactorCredentials, 1);
            $this->createStep($tblIdentification, $tblFactorTokenOrAuthenticatorApp, 2);
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

    /**
     * @param TblProcess $tblProcess
     *
     * @return bool
     */
    public function updateProcess(TblProcess $tblProcess): bool
    {
        $Manager = $this->getEntityManager();
        /** @var TblProcess $Entity */
        $Entity = $Manager->getEntityById('TblProcess', $tblProcess->getId());
        $Protocol = clone $Entity;
        if (null !== $Entity) {
            $Entity->setServiceTblAccount($tblProcess->getServiceTblAccount());
            $Entity->setTblFactor($tblProcess->getTblFactor());
            $Entity->setDeviceFactor($tblProcess->getDeviceFactor());
            $Entity->setIsSolved($tblProcess->getIsSolved());

            $Manager->saveEntity($Entity);
            Protocol::useService()->createUpdateEntry($this->getConnection()->getDatabase(), $Protocol, $Entity);

            return true;
        }

        return false;
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
     * @param TblToken $tblToken
     *
     * @return TblToken
     */
    public function createToken(TblToken $tblToken): TblToken
    {
        /** @var TblToken $tblToken */
        $tblToken = $this->createEntity($tblToken);

        return $tblToken;
    }

    /**
     * @param TblToken $tblToken
     * @param Token $accessToken
     *
     * @return bool
     */
    public function updateToken(TblToken $tblToken, Token $accessToken): bool
    {
        $Manager = $this->getEntityManager();
        /** @var TblToken $Entity */
        $Entity = $Manager->getEntityById('TblToken', $tblToken->getId());
        $Protocol = clone $Entity;
        if (null !== $Entity) {
            $Entity->setAccessToken($accessToken->getToken());
            $Entity->setAccessTimeout($accessToken->getTimeout());

            $Manager->saveEntity($Entity);
            Protocol::useService()->createUpdateEntry($this->getConnection()->getDatabase(), $Protocol, $Entity);

            return true;
        }

        return false;
    }

    /**
     * @param array $tblEntityList
     *
     * @return bool
     */
    public function deleteEntityListBulk(array $tblEntityList): bool
    {
        return parent::deleteEntityListBulk($tblEntityList);
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
            TblProcess::ATTR_DEVICE_FACTOR => $deviceFactor
        ];
        if ($tblAccount) {
            $criteria[TblProcess::SERVICE_TBL_ACCOUNT] = $tblAccount->getId();
        }

        // challenge: isSolved can be null or false
//        if (null !== $isSolved) {
//            $criteria[TblProcess::ATTR_IS_SOLVED] = $isSolved;
//        }

        /** @var TblProcess[] $entities */
        $entities = $this->getCachedEntityListBy(__METHOD__, $connection->getEntityManager(), 'TblProcess', $criteria, [Element::ENTITY_CREATE => self::ORDER_ASC]);
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
     * @param string $authenticationToken
     *
     * @return TblToken|null
     */
    public function getTokenByAuthenticationToken(string $authenticationToken): ?TblToken
    {
        $criteria = [
            TblToken::ATTR_AUTHENTICATION_TOKEN => $authenticationToken
        ];

        /** @var TblToken $tblToken */
        return ($tblToken = $this->getCachedEntityBy(__METHOD__, $this->getEntityManager(), 'TblToken', $criteria))
            ? $tblToken
            : null;
    }

    /**
     * @param string $deviceFactor
     *
     * @return TblToken|null
     */
    public function getTokenByDeviceFactor(string $deviceFactor): ?TblToken
    {
        $criteria = [
            TblToken::ATTR_DEVICE_FACTOR => $deviceFactor
        ];

        /** @var TblToken $tblToken */
        return ($tblToken = $this->getCachedEntityBy(__METHOD__, $this->getEntityManager(), 'TblToken', $criteria))
            ? $tblToken
            : null;
    }

    /**
     * @param string $accessToken
     *
     * @return TblToken|null
     */
    public function getTokenByAccessToken(string $accessToken): ?TblToken
    {
        $criteria = [
            TblToken::ATTR_ACCESS_TOKEN => $accessToken
        ];

        /** @var TblToken $tblToken */
        return ($tblToken = $this->getCachedEntityBy(__METHOD__, $this->getEntityManager(), 'TblToken', $criteria))
            ? $tblToken
            : null;
    }
}

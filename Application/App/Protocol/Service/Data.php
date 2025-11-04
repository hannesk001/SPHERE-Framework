<?php

namespace SPHERE\Application\App\Protocol\Service;

use SPHERE\Application\App\Protocol\Service\Entity\TblRequest;
use SPHERE\Application\App\Protocol\Service\Entity\TblResponse;
use SPHERE\Application\Platform\Gatekeeper\Authorization\Account\Service\Entity\TblAccount;
use SPHERE\Application\Platform\System\Protocol\Protocol;
use SPHERE\System\Database\Binding\AbstractData;

class Data extends AbstractData
{
    public function setupDatabaseContent(): void
    {

    }

    /**
     * @param TblRequest $tblRequest
     *
     * @return TblRequest
     */
    public function createRequest(TblRequest $tblRequest): TblRequest
    {
        /** @var TblRequest $tblRequest */
        $tblRequest = $this->createEntity($tblRequest);

        return $tblRequest;
    }

    /**
     * @param TblRequest $tblRequest
     * @param TblAccount|null $tblAccount
     * @param string|null $deviceFactor
     *
     * @return bool
     */
    public function updateRequest(TblRequest $tblRequest, ?TblAccount $tblAccount, ?string $deviceFactor): bool
    {
        $Manager = $this->getEntityManager();
        /** @var TblRequest $Entity */
        $Entity = $Manager->getEntityById('TblRequest', $tblRequest->getId());
        $Protocol = clone $Entity;
        if (null !== $Entity) {
            $Entity->setServiceTblAccount($tblAccount);
            $Entity->setDeviceFactor($deviceFactor);

            $Manager->saveEntity($Entity);
            Protocol::useService()->createUpdateEntry($this->getConnection()->getDatabase(), $Protocol, $Entity);

            return true;
        }

        return false;
    }

    /**
     * @param TblResponse $tblResponse
     *
     * @return void
     */
    public function createResponse(TblResponse $tblResponse): void
    {
        $this->createEntity($tblResponse);
    }
}
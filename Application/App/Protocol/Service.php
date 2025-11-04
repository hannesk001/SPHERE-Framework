<?php

namespace SPHERE\Application\App\Protocol;

use SPHERE\Application\App\AppException;
use SPHERE\Application\App\Protocol\Service\Data;
use SPHERE\Application\App\Protocol\Service\Entity\TblRequest;
use SPHERE\Application\App\Protocol\Service\Entity\TblResponse;
use SPHERE\Application\App\Protocol\Service\Setup;
use SPHERE\Application\App\Response\ResponseInterface;
use SPHERE\Application\Platform\Gatekeeper\Authorization\Account\Account;
use SPHERE\Application\Platform\Gatekeeper\Authorization\Account\Service\Entity\TblAccount;
use SPHERE\System\Database\Binding\AbstractService;

class Service extends AbstractService
{
    /**
     * @param bool $doSimulation
     * @param bool $withData
     * @param bool $UTF8
     *
     * @return string
     * @throws AppException
     */
    public function setupService($doSimulation, $withData, $UTF8): string
    {
        $Protocol = '';
        if (!$withData) {
            $Protocol = (new Setup($this->getStructure()))->setupDatabaseSchema($doSimulation, $UTF8);
        }
        if (!$doSimulation && $withData) {
            (new Data($this->getBinding()))->setupDatabaseContent();
        }

        return $Protocol;
    }

    /**
     * @param string $path
     *
     * @return TblRequest
     */
    public function createRequest(string $path): TblRequest
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $params = $_GET;
        $data = $method == 'POST' ? json_decode(file_get_contents('php://input'), true) : null;

        $tblAccount = isset($params['credentialIdentifier']) ? Account::useService()->getAccountByUsername($params['credentialIdentifier']) : null;
        $headers = null;
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = ['Authorization' => $_SERVER['HTTP_AUTHORIZATION']];
        }

        if (isset($data['password'])) {
            $data['password'] = hash('sha256', $data['password']);
        }

        $tblRequest = (new TblRequest())
            ->setServiceTblAccount($tblAccount ?: null)
            ->setDeviceFactor($params['deviceFactor'] ?? '')
            ->setRoute($path)
            ->setMethod($method)
            ->setParams($params)
            ->setHeaders($headers)
            ->setData($data);

        return (new Data($this->getBinding()))->createRequest($tblRequest);
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
        var_dump($deviceFactor);
        return (new Data($this->getBinding()))->updateRequest($tblRequest, $tblAccount, $deviceFactor);
    }

    /**
     * @param ResponseInterface $response
     * @param TblRequest $tblRequest
     *
     * @return void
     */
    public function createResponse(ResponseInterface $response, TblRequest $tblRequest): void
    {
        $tblResponse = (new TblResponse())
            ->setTblRequest($tblRequest)
            ->setCode($response->getStatusCode())
            ->setContent($response->getContent());

        (new Data($this->getBinding()))->createResponse($tblResponse);
    }
}
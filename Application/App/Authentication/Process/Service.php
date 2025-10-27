<?php

namespace SPHERE\Application\App\Authentication\Process;

use Exception;
use SPHERE\Application\App\AppException;
use SPHERE\Application\App\Authentication\Process\Service\Data;
use SPHERE\Application\App\Authentication\Process\Service\Entity\Jwt;
use SPHERE\Application\App\Authentication\Process\Service\Entity\TblFactor;
use SPHERE\Application\App\Authentication\Process\Service\Entity\TblProcess;
use SPHERE\Application\App\Authentication\Process\Service\Entity\TblStep;
use SPHERE\Application\App\Authentication\Process\Service\Setup;
use SPHERE\Application\App\Response\Code\Response400;
use SPHERE\Application\App\Response\Code\Response401;
use SPHERE\Application\App\Response\ResponseInterface;
use SPHERE\Application\Platform\Gatekeeper\Authorization\Account\Account;
use SPHERE\Application\Platform\Gatekeeper\Authorization\Account\Service\Entity\TblAccount;
use SPHERE\Application\Platform\Gatekeeper\Authorization\Account\Service\Entity\TblIdentification;
use SPHERE\System\Database\Binding\AbstractService;

/**
 *
 */
class Service extends AbstractService
{
    // TODO: secret in config
    private string $secret = '';

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
     * @param TblFactor $tblFactor
     * @param string $deviceFactor
     * @param TblAccount|null $tblAccount
     * @param bool $isSolved
     *
     * @return TblProcess|null
     */
    public function createProcess(TblFactor $tblFactor, string $deviceFactor, ?TblAccount $tblAccount, ?bool $isSolved = null): ?TblProcess
    {
        return (new Data($this->getBinding()))->createProcess($tblFactor, $deviceFactor, $tblAccount, $isSolved);
    }

    /**
     * @param TblProcess $tblProcess
     *
     * @return bool
     */
    public function updateProcess(TblProcess $tblProcess): bool
    {
        return (new Data($this->getBinding()))->updateEntity($tblProcess);
    }

    /**
     * @param int $id
     *
     * @return TblFactor|null
     */
    public function getFactorById(int $id): ?TblFactor
    {
        return (new Data($this->getBinding()))->getFactorById($id);
    }

    /**
     * @param TblIdentification|null $tblIdentification
     *
     * @return TblStep[]|null
     */
    public function getAllStepByIdentification(?TblIdentification $tblIdentification): ?array
    {
        return (new Data($this->getBinding()))->getAllStepByIdentification($tblIdentification);
    }

    /**
     * @param string $deviceFactor
     * @param TblAccount|null $tblAccount
     *
     * @return TblProcess[]|null
     */
    public function getAllProcessByDeviceFactor(string $deviceFactor, ?TblAccount $tblAccount): ?array
    {
        return (new Data($this->getBinding()))->getAllProcessByDeviceFactor($deviceFactor, $tblAccount);
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
        return (new Data($this->getBinding()))->getProcessByFactor($tblFactor, $deviceFactor, $tblAccount);
    }

    /**
     * @param TblFactor $tblFactor
     *
     * @return array|null
     */
    public function getContextByFactor(TblFactor $tblFactor): ?array
    {
        $context = [
            'name' => $tblFactor->getName(),
            'description' => $tblFactor->getDescription()
        ];
        if (TblFactor::NAME_CREDENTIALS == $tblFactor->getName()) {
            $context['link'] = 'https://' . $_SERVER['HTTP_HOST'] . '/app/authentication/factor/credentials';
            $context['parameters'] = ['deviceFactor'];
            $context['data'] = ['username', 'password'];
        } else if (TblFactor::NAME_AUTHENTICATOR_APP == $tblFactor->getName()) {
            $context['link'] = 'https://' . $_SERVER['HTTP_HOST'] . '/app/authentication/factor/authenticatorApp';
            $context['parameters'] = ['deviceFactor', 'credentialIdentifier'];
            $context['data'] = ['password'];
        } else if (TblFactor::NAME_TOKEN == $tblFactor->getName()) {
            $context['link'] = 'https://' . $_SERVER['HTTP_HOST'] . '/app/authentication/factor/token';
            $context['parameters'] = ['deviceFactor', 'credentialIdentifier'];
            $context['data'] = ['password'];
        } else {
            return null;
        }

        return $context;
    }

    /**
     * @param TblAccount|null $tblAccount
     *
     * @return TblIdentification|null
     */
    public function getIdentificationByAccount(?TblAccount $tblAccount): ?TblIdentification
    {
        $tblIdentification = null;
        if ($tblAccount
            && ($tblAuthenticationList = Account::useService()->getAuthenticationListByAccount($tblAccount))
        ) {
            // TODO: parallel YubKey and AuthenticatorApp
            $tblIdentification = $tblAuthenticationList[0]->getTblIdentification();
        }

        return $tblIdentification;
    }

    /**
     * @param TblAccount $tblAccount
     * @param $deviceFactor
     *
     * @return string
     */
    public function createAuthenticationToken(TblAccount $tblAccount, $deviceFactor): string
    {

        // create authentication token
        $jwt = new Jwt($this->secret);
        // TODO: add date or something? otherwise the same jwt
        $payLoad = [
            'id' => $tblAccount->getId(),
            'username' => $tblAccount->getUsername(),
            'deviceFactor' => $deviceFactor
        ];
        $authenticationToken = $jwt->encode($payLoad);

        // save token
        (new Data($this->getBinding()))->createToken($tblAccount, $authenticationToken);

        return $authenticationToken;
    }

    /**
     * @param string $credentialDevice
     * @param string $credentialIdentifier
     *
     * @return ResponseInterface|bool
     */
    public function authenticateAuthenticationToken(string $credentialDevice, string $credentialIdentifier): ResponseInterface|bool
    {
        $headers = null;
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = ['Authorization' => $_SERVER['HTTP_AUTHORIZATION']];
        }
        if (!isset($headers['Authorization']) || !preg_match("/^Bearer\s+(.*)$/", $headers['Authorization'], $matches)) {
            return new Response400('Incomplete authorization header', ['Authorization' => $headers['Authorization'] ?? null]);
        }
        $authenticationToken = $matches[1];

        try {
            $data = (new Jwt($this->secret))->decode($authenticationToken);

            if ($data == null) {
                return new Response401('Invalid signature');
            }

            $id = $data['id'] ?? null;
            $username = $data['username'] ?? null;
            $deviceFactor = $data['deviceFactor'] ?? null;
            if (empty($id) || empty($username) || empty($deviceFactor)
                || (!$tblAccount = Account::useService()->getAccountByUsername($username))
                || $id != $tblAccount->getId()
                || $credentialDevice != $deviceFactor
                || $credentialIdentifier != $username
                || !($tblToken = (new Data($this->getBinding()))->getTokenByAccountAndAuthenticationToken($tblAccount, $authenticationToken))
            ) {
                return new Response401('Invalid Bearer token');
            }

            // for test expire
            $offset = 0; // 3600 * 24 * 30;
            // bearer token is expired
            if (time() + $offset > $tblToken->getAuthenticationTimeout()) {
                // TODO: delete bearer token?
                return new Response401('Bearer token is expired');
            }

            // TODO: create session
            // Session in SSW für DB Zugriff
//            if (($tblSessionList = Account::useService()->getSessionAllByAccount($tblAccount))) {
//                $tblSession = current($tblSessionList);
//                session_id($tblSession->getSession());
//                Account::useService()->refreshSession($tblSession->getSession());
//            } else {
//                $tblSession = Account::useService()->createSession($tblAccount);
//                session_id($tblSession->getSession());
//            }
        } catch (Exception $e) {

            return new Response400($e->getMessage());
        }

        return true;
    }
}

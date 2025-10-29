<?php

namespace SPHERE\Application\App\Authentication\Process;

use SPHERE\Application\App\AppException;
use SPHERE\Application\App\Authentication\Process\Service\Data;
use SPHERE\Application\App\Authentication\Process\Service\Entity\Internal\Jwt;
use SPHERE\Application\App\Authentication\Process\Service\Entity\Internal\Token;
use SPHERE\Application\App\Authentication\Process\Service\Entity\TblFactor;
use SPHERE\Application\App\Authentication\Process\Service\Entity\TblProcess;
use SPHERE\Application\App\Authentication\Process\Service\Entity\TblStep;
use SPHERE\Application\App\Authentication\Process\Service\Entity\TblToken;
use SPHERE\Application\App\Authentication\Process\Service\Setup;
use SPHERE\Application\Platform\Gatekeeper\Authorization\Account\Account;
use SPHERE\Application\Platform\Gatekeeper\Authorization\Account\Service\Entity\TblAccount;
use SPHERE\Application\Platform\Gatekeeper\Authorization\Account\Service\Entity\TblIdentification;
use SPHERE\System\App\App;
use SPHERE\System\Database\Binding\AbstractService;

/**
 *
 */
class Service extends AbstractService
{
    const AUTHENTICATION_TOKEN_EXPIRE_IN_DAYS = 30;
    const ACCESS_TOKEN_EXPIRE_IN_MINUTES = 10;

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
        return (new Data($this->getBinding()))->updateProcess($tblProcess);
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
     * @param string $authenticationToken
     *
     * @return TblToken|null
     */
    public function getTokenByAuthenticationToken(string $authenticationToken): ?TblToken
    {
        return (new Data($this->getBinding()))->getTokenByAuthenticationToken($authenticationToken);
    }

    /**
     * @param TblFactor $tblFactor
     *
     * @return array|null
     */
    public function getContextByFactor(TblFactor $tblFactor): ?array
    {
        $host = 'https://' . $_SERVER['HTTP_HOST'];
        $context = [
            'name' => $tblFactor->getName(),
            'description' => $tblFactor->getDescription()
        ];
        if (TblFactor::NAME_CREDENTIALS == $tblFactor->getName()) {
            $context['link'] = $host . '/app/authentication/factor/credentials';
            $context['parameters'] = ['deviceFactor'];
            $context['data'] = ['username', 'password'];
        } else if (TblFactor::NAME_AUTHENTICATOR_APP == $tblFactor->getName()) {
            $context['link'] = $host . '/app/authentication/factor/authenticator';
            $context['parameters'] = ['deviceFactor', 'credentialIdentifier'];
            $context['data'] = ['password'];
        } else if (TblFactor::NAME_TOKEN == $tblFactor->getName()) {
            $context['link'] = $host . '/app/authentication/factor/token';
            $context['parameters'] = ['deviceFactor', 'credentialIdentifier'];
            $context['data'] = ['password'];
        } else if (TblFactor::NAME_TOKEN_OR_AUTHENTICATOR_APP == $tblFactor->getName()) {
            $context['link'] = $host . '/app/authentication/factor/token-authenticator';
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
            $count = count($tblAuthenticationList);
            if ($count === 1) {
                $tblIdentification = $tblAuthenticationList[0]->getTblIdentification();
            } elseif ($count === 2) {
                // parallel Token and AuthenticatorApp
                if (($tblAccount->getHasAuthentication(TblIdentification::NAME_SYSTEM)
                        || $tblAccount->getHasAuthentication(TblIdentification::NAME_TOKEN))
                    && $tblAccount->getHasAuthentication(TblIdentification::NAME_AUTHENTICATOR_APP)
                ) {
                    return $this->getVirtualIdentificationTokenOrAuthenticatorApp();
                }
            }
        }

        return $tblIdentification;
    }

    /**
     * @return TblIdentification
     */
    public function getVirtualIdentificationTokenOrAuthenticatorApp(): TblIdentification
    {
        $tblIdentification = new TblIdentification('Hardware-Schlüssel oder Authenticator App');
        $tblIdentification->setId(-1);

        return $tblIdentification;
    }

    /**
     * @param TblAccount $tblAccount
     * @param $deviceFactor
     *
     * @return TblToken
     */
    public function createToken(TblAccount $tblAccount, $deviceFactor): TblToken
    {
        $AuthenticationToken = $this->createAuthenticationToken($tblAccount, $deviceFactor);
        $AccessToken = $this->createAccessToken($tblAccount, $deviceFactor);

        $tblToken = new TblToken();
        $tblToken->setServiceTblAccount($tblAccount);
        $tblToken->setAuthenticationToken($AuthenticationToken->getToken());
        $tblToken->setAuthenticationTimeout($AuthenticationToken->getTimeout());
        $tblToken->setAccessToken($AccessToken->getToken());
        $tblToken->setAccessTimeout($AccessToken->getTimeout());

        return (new Data($this->getBinding()))->createToken($tblToken);
    }

    /**
     * @param TblToken $tblToken
     * @param Token $accessToken
     *
     * @return bool
     */
    public function updateToken(TblToken $tblToken, Token $accessToken): bool
    {
        return (new Data($this->getBinding()))->updateToken($tblToken, $accessToken);
    }

    /**
     * @param TblAccount $tblAccount
     * @param $deviceFactor
     *
     * @return Token
     */
    public function createAuthenticationToken(TblAccount $tblAccount, $deviceFactor): Token
    {
        // create authentication token
        $jwt = new Jwt((new App())->getSecretAuthentication());
        $timeout = time() + 3600 * 24 * self::AUTHENTICATION_TOKEN_EXPIRE_IN_DAYS;
        $payLoad = [
            'accountId' => $tblAccount->getId(),
            'deviceFactor' => $deviceFactor,
            'timeout' => $timeout,
        ];

        return new Token($jwt->encode($payLoad), $timeout);
    }

    /**
     * @param TblAccount $tblAccount
     * @param $deviceFactor
     *
     * @return Token
     */
    public function createAccessToken(TblAccount $tblAccount, $deviceFactor): Token
    {
        $timeoutDiff = 60 * self::ACCESS_TOKEN_EXPIRE_IN_MINUTES;

        // create session in ssw
        $tblSession = Account::useService()->createSession($tblAccount, null, $timeoutDiff, $deviceFactor);
        session_id($tblSession->getSession());

        // create authentication token
        $jwt = new Jwt((new App())->getSecretAccess());
        $payLoad = [
            'accountId' => $tblAccount->getId(),
            'session' => $tblSession->getSession(),
            'timeout' =>  $tblSession->getTimeout(),
        ];

        return new Token($jwt->encode($payLoad), $tblSession->getTimeout());
    }
}

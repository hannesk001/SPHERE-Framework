<?php

namespace SPHERE\Application\App\Authentication\Process;

use SPHERE\Application\App\AppException;
use SPHERE\Application\App\Authentication\Authentication;
use SPHERE\Application\App\Authentication\Process\Service\Data;
use SPHERE\Application\App\Authentication\Process\Service\Entity\Internal\Jwt;
use SPHERE\Application\App\Authentication\Process\Service\Entity\Internal\Token;
use SPHERE\Application\App\Authentication\Process\Service\Entity\TblFactor;
use SPHERE\Application\App\Authentication\Process\Service\Entity\TblProcess;
use SPHERE\Application\App\Authentication\Process\Service\Entity\TblStep;
use SPHERE\Application\App\Authentication\Process\Service\Entity\TblToken;
use SPHERE\Application\App\Authentication\Process\Service\Setup;
use SPHERE\Application\App\Response\Code\Response201;
use SPHERE\Application\App\Response\Code\Response400;
use SPHERE\Application\App\Response\Code\Response401;
use SPHERE\Application\App\Response\Code\Response405;
use SPHERE\Application\App\Response\Code\Response415;
use SPHERE\Application\App\Response\ResponseInterface;
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
     * @param string $deviceFactor
     *
     * @return TblToken|null
     */
    public function getTokenByDeviceFactor(string $deviceFactor): ?TblToken
    {
        return (new Data($this->getBinding()))->getTokenByDeviceFactor($deviceFactor);
    }

    /**
     * @param string $accessToken
     *
     * @return TblToken|null
     */
    public function getTokenByAccessToken(string $accessToken): ?TblToken
    {
        return (new Data($this->getBinding()))->getTokenByAccessToken($accessToken);
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
            $context['link'] = $this->getLink('/app/authentication/factor/credentials', 'POST', ['deviceFactor'], null, ['username', 'password']);
        } else if (TblFactor::NAME_AUTHENTICATOR_APP == $tblFactor->getName()) {
            $context['link'] = $this->getLink('/app/authentication/factor/authenticator', 'POST', ['deviceFactor', 'credentialIdentifier'], null, ['otpCredentialKey']);
        } else if (TblFactor::NAME_TOKEN == $tblFactor->getName()) {
            $context['link'] = $this->getLink('/app/authentication/factor/token', 'POST', ['deviceFactor', 'credentialIdentifier'], null, ['otpCredentialKey']);
        } else if (TblFactor::NAME_TOKEN_OR_AUTHENTICATOR_APP == $tblFactor->getName()) {
            $context['link'] = $this->getLink('/app/authentication/factor/token-authenticator', 'POST', ['deviceFactor', 'credentialIdentifier'], null, ['otpCredentialKey']);
        } else {
            return null;
        }

        return $context;
    }

    /**
     * @param string $route
     * @param string $method
     * @param array|null $params
     * @param array|null $headers
     * @param array|null $data
     *
     * @return array
     */
    public function getLink(string $route, string $method = 'GET', ?array $params = null, ?array $headers = null, ?array $data = null): array
    {
        $host = 'https://' . $_SERVER['HTTP_HOST'];

        return [
            'route' => $host . $route,
            'method' => $method,
            'params' => $params,
            'headers' => $headers,
            'data' => $data,
        ];
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
        $tblIdentification = new TblIdentification('TokenOrAuthenticatorApp');
        $tblIdentification->setDescription('Hardware-Schlüssel oder Authenticator App');
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
        $tblToken->setDeviceFactor($deviceFactor);
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
            'deviceFactor' => $deviceFactor,
            'session' => $tblSession->getSession(),
            'timeout' =>  $tblSession->getTimeout(),
        ];

        return new Token($jwt->encode($payLoad), $tblSession->getTimeout());
    }

    /**
     * @param string $deviceFactor
     * @param TblAccount|null $tblAccount
     *
     * @return TblFactor|null
     */
    public function getNextUnSolvedFactor(string $deviceFactor, ?TblAccount $tblAccount = null): ?TblFactor
    {
        if (($tblProcesslist = $this->getAllProcessByDeviceFactor($deviceFactor, $tblAccount))) {
            foreach ($tblProcesslist as $tblProcess) {
                if ($tblProcess->getIsSolved() !== true) {
                    return $tblProcess->getTblFactor();
                }
            }
        }

        return null;
    }

    /**
     * @param string $factorName
     * @param string $deviceFactor
     * @param bool|null $isSolved
     * @param TblAccount|null $tblAccount
     */
    public function updateProcessByFactorName(string $factorName, string $deviceFactor, ?bool $isSolved, ?TblAccount $tblAccount = null): void
    {
        // by credentials is not yet set the account
        if (($tblProcesslist = $this->getAllProcessByDeviceFactor($deviceFactor, $factorName == TblFactor::NAME_CREDENTIALS ? null : $tblAccount))) {
            foreach ($tblProcesslist as $tblProcess) {
                if ($tblProcess->getTblFactor()->getName() == $factorName) {
                    $tblProcess->setServiceTblAccount($tblAccount);
                    $tblProcess->setIsSolved($isSolved);

                    $this->updateProcess($tblProcess);
                    break;
                }
            }
        }
    }

    /**
     * @param string $factorName
     * @param string|null $deviceFactor
     * @param string|null $credentialIdentifier
     * @param bool $isCredentialIdentifierRequired
     *
     * @return ResponseInterface|null
     */
    public function checkAuthentication(
        string $factorName, ?string $deviceFactor, ?string $credentialIdentifier = null, bool $isCredentialIdentifierRequired = false
    ): ?ResponseInterface {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return new Response405('Allowed: POST', [
                'method' => $_SERVER['REQUEST_METHOD'],
            ]);
        }
        $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
        if ($contentType !== 'application/json') {
            return new Response415('"Only JSON content is supported"', [
                'contentType' => $contentType,
            ]);
        }

        if (empty($deviceFactor)) {
            return new Response400('Device Factor not provided', [
                'deviceFactor' => $deviceFactor,
            ]);
        }

        $tblAccount = null;
        if ($isCredentialIdentifierRequired
            && (empty($credentialIdentifier) || !($tblAccount = Account::useService()->getAccountByUsername($credentialIdentifier)))
        ) {
            return new Response400('Credential Identifier not provided', [
                'credentialIdentifier' => $credentialIdentifier,
            ]);
        }

        // check if step is currently required, is it already solved or not yet processList for deviceFactor -> return status-link
        if (!($tblUnsolvedFactor = Authentication::useService()->getNextUnSolvedFactor($deviceFactor, $tblAccount))
            || $tblUnsolvedFactor->getName() != $factorName
        ) {
            return new Response400(@"$factorName not required", [
                'link' => Authentication::useService()->getLink('/app/authentication/status', 'GET', ['deviceFactor'])
            ]);
        }

        return null;
    }

    /**
     * @param string $factorName
     * @param string|null $deviceFactor
     * @param string|null $credentialIdentifier
     * @param string $identificationName
     * @param TblAccount|null $tblAccountOut
     * @param string|null $otpCredentialKeyOut
     *
     * @return ResponseInterface|null
     */
    public function checkAuthenticationOtpCredential(
        string $factorName, ?string $deviceFactor, ?string $credentialIdentifier, string $identificationName,
        ?TblAccount &$tblAccountOut, ?string &$otpCredentialKeyOut
    ): ?ResponseInterface {
        $response = Authentication::useService()->checkAuthentication($factorName, $deviceFactor, $credentialIdentifier, true);
        // Authentication failed
        if ($response instanceof ResponseInterface) {
            return $response;
        }

        // JSON content laden
        $data = json_decode(file_get_contents('php://input'), true);
        $otpCredentialKeyOut = $data['otpCredentialKey'] ?? null;

        // check identification
        if (!($tblAccountOut = Account::useService()->getAccountByUsername($credentialIdentifier))
            || !($tblIdentification = $this->getIdentificationByAccount($tblAccountOut))
            || $tblIdentification->getName() != $identificationName
        ) {
            Authentication::useService()->updateProcessByFactorName($factorName, $deviceFactor, false, $tblAccountOut);

            return new Response400(@"Account does not support $factorName");
        }

        if (empty($otpCredentialKeyOut)) {
            Authentication::useService()->updateProcessByFactorName($factorName, $deviceFactor, false, $tblAccountOut);

            return new Response400('OtpCredentialKey not provided');
        }

        return null;
    }

    /**
     * @param string $deviceFactor
     * @param TblAccount $tblAccount
     *
     * @return ResponseInterface
     */
    public function sendAuthentication(string $deviceFactor, TblAccount $tblAccount): ResponseInterface
    {
        // another step fo authentication is required
        if (($tblNextFactor = Authentication::useService()->getNextUnSolvedFactor($deviceFactor, $tblAccount))) {

            // only send credentialIdentifier -> than its necessary to get next step over status but logic is simpler
            return new Response201(['credentialIdentifier' => $tblAccount->getUsername()]); // + $tblNextFactor->getContext());
        }

        // authentication successful
        $tblToken = Authentication::useService()->createToken($tblAccount, $deviceFactor);

        return new Response201([
            'credentialIdentifier' => $tblAccount->getUsername(),
            'authenticationToken' => $tblToken->getAuthenticationToken(),
            'accessToken' => $tblToken->getAccessToken(),
        ]);
    }

    /**
     * @param string $deviceFactor
     * @param TblAccount|null $tblAccount
     *
     * @return void
     */
    public function signOut(string $deviceFactor, ?TblAccount $tblAccount): void
    {
        $deleteEntityList = [];

        // Remove App-Account-Tokens
        if (($tblToken = self::getTokenByDeviceFactor($deviceFactor))) {
            // Remove SSW-PHP-Session, technical not required, delete tblToken is enough
            if ($tblToken->getAccessToken()) {
                $data = (new Jwt((new App())->getSecretAccess()))->decode($tblToken->getAccessToken());
                $session = $data['session'] ?? null;
                Account::useService()->destroySession(null, $session);
            }

            $deleteEntityList[] = $tblToken;
        }
        // Remove App-Account-Process
        if (($tblProcessList = self::getAllProcessByDeviceFactor($deviceFactor, $tblAccount))) {
            $deleteEntityList = array_merge($deleteEntityList, $tblProcessList);
        }

        (new Data($this->getBinding()))->deleteEntityListBulk($deleteEntityList);
    }

    /**
     * @param string $deviceFactor
     * @param TblAccount|null $tblAccount
     *
     * @return array|null
     */
    public function createNewProcess(string $deviceFactor, ?TblAccount $tblAccount): ?array
    {
        $tblIdentification = $this->getIdentificationByAccount($tblAccount ?: null);
        if (($tblStepList = $this->getAllStepByIdentification($tblIdentification ?: null))) {
            $tblStep = $tblStepList[0];
            if (($tblFactor = $tblStep->getTblFactor())
                && ($context = $tblFactor->getContext())
            ) {
                // save process
                $this->createProcess($tblFactor, $deviceFactor, $tblAccount ?: null);

                return $context;
            }
        }

        return null;
    }

    /**
     * @param string $deviceFactor
     * @param TblAccount|null $tblAccount
     *
     * @return Response401
     */
    public function sendAuthenticationTokenExpired(string $deviceFactor, ?TblAccount $tblAccount): Response401
    {
        // authentication token expired -> delete TblToken, Process, session und Co
        $this->signOut($deviceFactor, $tblAccount);

        $context = $this->createNewProcess($deviceFactor, null);

        return new Response401('Authentication Token expired', $context);
    }
}

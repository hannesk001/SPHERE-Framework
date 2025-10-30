<?php

namespace SPHERE\Application\App\Authentication\Factor;

use Exception;
use SPHERE\Application\App\Authentication\Authentication;
use SPHERE\Application\App\Authentication\Process\Service\Entity\TblFactor;
use SPHERE\Application\App\ModuleInterface;
use SPHERE\Application\App\Response\Code\Response401;
use SPHERE\Application\App\Response\ResponseInterface;
use SPHERE\Application\Platform\Gatekeeper\Authorization\Account\Account;
use SPHERE\Application\Platform\Gatekeeper\Authorization\Account\Service\Entity\TblAccount;
use SPHERE\Common\Main;

class TokenOrAuthenticatorApp implements ModuleInterface
{
    /**
     * @return void
     */
    public static function registerModule(): void
    {
        Main::getDispatcher()::registerRoute(
            Main::getDispatcher()::createRoute(
                __NAMESPACE__ . '/token-authenticator', __CLASS__ . '::handleRequest'
            )
        );
    }

    /**
     * @param string|null $deviceFactor
     * @param string|null $credentialIdentifier
     *
     * @return ResponseInterface
     */
    public static function handleRequest(
        ?string $deviceFactor = null,
        ?string $credentialIdentifier = null
    ): ResponseInterface {

        $factorName = TblFactor::NAME_TOKEN_OR_AUTHENTICATOR_APP;
        $identificationName = Authentication::useService()->getVirtualIdentificationTokenOrAuthenticatorApp()->getName();

        $otpCredentialKey = null;
        $tblAccount = null;
        $response = Authentication::useService()->checkAuthenticationOtpCredential(
            $factorName, $deviceFactor, $credentialIdentifier, $identificationName, $tblAccount, $otpCredentialKey
        );
        // Authentication failed
        if ($response instanceof ResponseInterface) {
            return $response;
        }

        /** @var TblAccount $tblAccount */

        // Authenticator App
        if (strlen($otpCredentialKey) == 6) {
            if (!Account::useService()->getIsAuthenticatorAppCredentialKeyCorrect($tblAccount, $otpCredentialKey)) {
                Authentication::useService()->updateProcessByFactorName($factorName, $deviceFactor, false, $tblAccount);

                return new Response401('OtpCredentialKey not valid', [
                    'otpCredentialKey' => str_pad('', strlen($otpCredentialKey), '*')
                ]);
            }
        // Token
        } else {
            try {
                // tblAccount is here not null
                if (!Account::useService()->getIsTokenCredentialKeyCorrect($tblAccount, $otpCredentialKey)) {
                    Authentication::useService()->updateProcessByFactorName($factorName, $deviceFactor, false, $tblAccount);

                    return new Response401('OtpCredentialKey not valid', [
                        'otpCredentialKey' => str_pad('', strlen($otpCredentialKey), '*')
                    ]);
                }
            } catch (Exception $exception) {
                return new Response401('OtpCredentialKey not valid', [
                    'otpCredentialKey' => str_pad('', strlen($otpCredentialKey), '*'),
                    'exceptionMessage' => $exception->getMessage()
                ]);
            }
        }

        Authentication::useService()->updateProcessByFactorName($factorName, $deviceFactor, true, $tblAccount);

        return Authentication::useService()->sendAuthentication($deviceFactor, $tblAccount);
    }
}
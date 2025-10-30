<?php

namespace SPHERE\Application\App\Authentication\Factor;

use SPHERE\Application\App\Authentication\Authentication;
use SPHERE\Application\App\Authentication\Process\Service\Entity\TblFactor;
use SPHERE\Application\App\ModuleInterface;
use SPHERE\Application\App\Response\Code\Response400;
use SPHERE\Application\App\Response\Code\Response401;
use SPHERE\Application\App\Response\ResponseInterface;
use SPHERE\Application\Platform\Gatekeeper\Authorization\Account\Account;
use SPHERE\Common\Main;

/**
 *
 */
class Credentials implements ModuleInterface
{
    /**
     * @return void
     */
    public static function registerModule(): void
    {
        Main::getDispatcher()::registerRoute(
            Main::getDispatcher()::createRoute(
                __NAMESPACE__ . '/credentials', __CLASS__ . '::handleRequest'
            )
        );
    }

    /**
     * @param string|null $deviceFactor
     *
     * @return ResponseInterface
     */
    public static function handleRequest(
        ?string $deviceFactor = null
    ): ResponseInterface {

        $factorName = TblFactor::NAME_CREDENTIALS;

        $response = Authentication::useService()->checkAuthentication($factorName, $deviceFactor);
        // Authentication failed
        if ($response instanceof ResponseInterface) {
            return $response;
        }

        // JSON content laden
        $data = json_decode(file_get_contents('php://input'), true);
        $username = $data['username'] ?? null;
        $password = $data['password'] ?? null;

        if (empty($username) || empty($password)) {
            Authentication::useService()->updateProcessByFactorName($factorName, $deviceFactor, false);

            return new Response400('Credentials not provided', [
                'username' => $username,
                'password' => str_pad('', strlen($password), '*')
            ]);
        }

        if (!($tblAccount = Account::useService()->getAccountByCredential($username, $password))) {
            Authentication::useService()->updateProcessByFactorName($factorName, $deviceFactor, false);

            return new Response401('Credentials not valid', [
                'username' => $username,
                'password' => str_pad('', strlen($password), '*')
            ]);
        }

        // consumer lock
        if (!($tblConsumer = $tblAccount->getServiceTblConsumer())
            || ($tblConsumer->getAcronym() !='REF' && $tblConsumer->getAcronym() != 'DEMO')
        ) {
            return new Response401('No sign in available for consumer: ' . ($tblConsumer ? $tblConsumer->getAcronym() : ''), []);
        }

        // before the identification is unknown (only step is credentials) and there maybe more steps required
        if (($tblIdentification = Authentication::useService()->getIdentificationByAccount($tblAccount))
            && ($tblStepList = Authentication::useService()->getAllStepByIdentification($tblIdentification))
        ) {
            foreach ($tblStepList as $tblStep) {
                // tblAccount is null by existing steps
                if (!Authentication::useService()->getProcessByFactor($tblStep->getTblFactor(), $deviceFactor, null)) {
                    // create process
                    Authentication::useService()->createProcess($tblStep->getTblFactor(), $deviceFactor, $tblAccount);
                }
            }
        } else {

            return new Response401('No sign in available', []);
        }

        Authentication::useService()->updateProcessByFactorName($factorName, $deviceFactor, true, $tblAccount);

        return Authentication::useService()->sendAuthentication($deviceFactor, $tblAccount);
    }
}

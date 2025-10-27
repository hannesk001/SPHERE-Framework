<?php

namespace SPHERE\Application\App\Authentication\Factor;

use SPHERE\Application\App\Authentication\Authentication;
use SPHERE\Application\App\Authentication\Process\Service\Entity\TblFactor;
use SPHERE\Application\App\ModuleInterface;
use SPHERE\Application\App\Response\Code\Response201;
use SPHERE\Application\App\Response\Code\Response400;
use SPHERE\Application\App\Response\Code\Response401;
use SPHERE\Application\App\Response\Code\Response405;
use SPHERE\Application\App\Response\Code\Response415;
use SPHERE\Application\App\Response\Code\Response501;
use SPHERE\Application\App\Response\ResponseInterface;
use SPHERE\Application\Platform\Gatekeeper\Authorization\Account\Account;
use SPHERE\Application\Platform\Gatekeeper\Authorization\Account\Service\Entity\TblAccount;
use SPHERE\Common\Main;

/**
 *
 */
class Credentials implements ModuleInterface
{
    public static function registerModule(): void
    {
        Main::getDispatcher()::registerRoute(
            Main::getDispatcher()::createRoute(
                __NAMESPACE__ . '/credentials', __CLASS__ . '::handleRequest'
            )
        );
    }

    public static function handleRequest(
        ?string $deviceFactor = null
    ): ResponseInterface {

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

        // JSON content laden
        $data = json_decode(file_get_contents('php://input'), true);

        $username = $data['username'] ?? null;
        $password = $data['password'] ?? null;

        if (empty($username) || empty($password)) {
            self::updateProcess($deviceFactor, false);

            return new Response400('Credentials not provided', [
                'username' => $username,
                'password' => str_pad('', strlen($password), '*')
            ]);
        }

        if (!($tblAccount = Account::useService()->getAccountByCredential($username, $password))) {
            self::updateProcess($deviceFactor, false);

            return new Response401('Credentials not valid', [
                'username' => $username,
                'password' => str_pad('', strlen($password), '*')
            ]);
        }

        self::updateProcess($deviceFactor, true, $tblAccount);

        if (($tblIdentification = Authentication::useService()->getIdentificationByAccount($tblAccount))
            && ($tblStepList = Authentication::useService()->getAllStepByIdentification($tblIdentification))
        ) {
            foreach ($tblStepList as $tblStep) {
                if (!Authentication::useService()->getProcessByFactor($tblStep->getTblFactor(), $deviceFactor, $tblAccount)) {
                    // save process
                    Authentication::useService()->createProcess($tblStep->getTblFactor(), $deviceFactor, $tblAccount);

                    // TODO: set && get next step by MFA
                    return new Response501(null);
                }
            }
        } else {

            return new Response401('No sign in available', []);
        }

        return new Response201([
            'authenticationToken' => Authentication::useService()->createAuthenticationToken($tblAccount, $deviceFactor),
            'credentialIdentifier' => $tblAccount->getUsername()
        ]);
    }

    /**
     * @param string $deviceFactor
     * @param bool|null $isSolved
     * @param TblAccount|null $tblAccount
     */
    private static function updateProcess(string $deviceFactor, ?bool $isSolved, ?TblAccount $tblAccount = null): void
    {
        // TODO: check if step is currently required
        if (($tblProcesslist = Authentication::useService()->getAllProcessByDeviceFactor($deviceFactor, null))) {
            foreach ($tblProcesslist as $tblProcess) {
                if ($tblProcess->getTblFactor()->getName() == TblFactor::NAME_CREDENTIALS) {
                    $tblProcess->setServiceTblAccount($tblAccount);
                    $tblProcess->setIsSolved($isSolved);
                    Authentication::useService()->updateProcess($tblProcess);
                    break;
                }
            }
        }
    }
}

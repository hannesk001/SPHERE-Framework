<?php

namespace SPHERE\Application\App\Authorization;

use Exception;
use SPHERE\Application\App\Authentication\Authentication;
use SPHERE\Application\App\Authentication\Process\Service\Entity\Internal\Jwt;
use SPHERE\Application\App\Protocol\Protocol;
use SPHERE\Application\App\Protocol\Service\Entity\TblRequest;
use SPHERE\Application\App\Response\Code\Response400;
use SPHERE\Application\App\Response\Code\Response401;
use SPHERE\Application\App\Response\Code\Response403;
use SPHERE\Application\App\Response\Code\Response405;
use SPHERE\Application\App\Response\ResponseInterface;
use SPHERE\Application\Platform\Gatekeeper\Authorization\Access\Access;
use SPHERE\Application\Platform\Gatekeeper\Authorization\Account\Account;
use SPHERE\System\App\App;

class Authorization
{
    /**
     * @param TblRequest $tblRequest
     * @param string $method
     *
     * @return ResponseInterface|null
     */
    public static function checkAuthorization(TblRequest $tblRequest, string $method = 'GET'): ?ResponseInterface
    {
        $route = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        if ($_SERVER['REQUEST_METHOD'] !== $method) {

            return new Response405(@"Allow: $method", [
                'method' => $_SERVER['REQUEST_METHOD'],
            ]);
        }

        $headers = null;
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = ['Authorization' => $_SERVER['HTTP_AUTHORIZATION']];
        }
        if (!isset($headers['Authorization']) || !preg_match("/^Bearer\s+(.*)$/", $headers['Authorization'], $matches)) {
            return new Response400('Incomplete authorization header', ['Authorization' => $headers['Authorization'] ?? null]);
        }
        $accessToken = $matches[1];

        try {
            $data = (new Jwt((new App())->getSecretAccess()))->decode($accessToken);
        } catch (Exception $e) {

            return new Response400($e->getMessage());
        }

        if ($data == null) {

            return new Response401('Invalid signature');
        }

        // update tblRequest
        $deviceFactor = $data['deviceFactor'] ?? null;
        $accountId = $data['accountId'] ?? null;
        $tblAccount = $accountId ? Account::useService()->getAccountById($accountId) : null;
        Protocol::useService()->updateRequest($tblRequest, $tblAccount, $deviceFactor);

        // validate accessToken
        $session = $data['session'] ?? null;
        $timeout = $data['timeout'] ?? null;
        if ($session == null || $timeout == null || !Authentication::useService()->getTokenByAccessToken($accessToken)) {

            return new Response401('Invalid Bearer token');
        }

        // check if accessToken is expired
        if (time() > $timeout) {

            return new Response401('Access Token expired',
                Authentication::useService()->getLink('/app/authentication/access-token', 'POST', ['deviceFactor'], [], ['authenticationToken']));
        }

        // set session for services and access
        session_id($session);

        // check authorization for route
        if (!Access::useService()->hasAuthorization($route)) {

            return new Response403('Access denied');
        }

        return null;
    }
}
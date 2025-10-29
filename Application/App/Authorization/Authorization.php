<?php

namespace SPHERE\Application\App\Authorization;

use Exception;
use SPHERE\Application\App\Authentication\Process\Service\Entity\Internal\Jwt;
use SPHERE\Application\App\Response\Code\Response400;
use SPHERE\Application\App\Response\Code\Response401;
use SPHERE\Application\App\Response\Code\Response403;
use SPHERE\Application\App\Response\Code\Response405;
use SPHERE\Application\App\Response\ResponseInterface;
use SPHERE\Application\Platform\Gatekeeper\Authorization\Access\Access;
use SPHERE\System\App\App;

class Authorization
{
    /**
     * @param string $method
     *
     * @return ResponseInterface|null
     */
    public static function checkAuthorization(string $method = 'GET'): ?ResponseInterface
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

        $session = $data['session'] ?? null;
        $timeout = $data['timeout'] ?? null;
        if ($session == null || $timeout == null) {

            return new Response401('Invalid Bearer token');
        }

        // check if accessToken is expired
        if (time() > $timeout) {

            // TODO: give refresh link
            return new Response401('AccessToken expired');
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
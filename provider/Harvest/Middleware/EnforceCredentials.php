<?php
/**
 * This file is part of the Decode r7 framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Harvest\Middleware;

use DecodeLabs\Genesis;
use DecodeLabs\Harvest;
use DecodeLabs\R7\Config\Http as HttpConfig;
use DecodeLabs\R7\Legacy;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class EnforceCredentials implements Middleware
{
    /**
     * Process middleware
     */
    public function process(
        Request $request,
        Handler $next
    ): Response {
        if ($response = $this->enforce()) {
            return $response;
        }

        return $next->handle($request);
    }

    protected function enforce(): ?Response
    {
        $config = HttpConfig::load();
        $credentials = $config->getCredentials();

        // Check for credentials or dev mode
        if (
            $credentials === null ||
            !isset($credentials['username']) ||
            !isset($credentials['password']) ||
            Genesis::$environment->isDevelopment() ||
            Legacy::$http->isDfSelf()
        ) {
            return null;
        }

        // Check credentials
        if (
            !isset($_SERVER['PHP_AUTH_USER']) ||
            $_SERVER['PHP_AUTH_USER'] !== $credentials['username'] ||
            $_SERVER['PHP_AUTH_PW'] !== $credentials['password']
        ) {
            return Harvest::text('You need to authenticate to view this site', 401, [
                'WWW-Authenticate' => 'Basic realm="Developer Site"'
            ]);
        }

        return null;
    }
}

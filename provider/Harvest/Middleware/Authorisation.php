<?php
/**
 * This file is part of the Decode r7 framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Harvest\Middleware;

use DecodeLabs\Compass\Ip;
use DecodeLabs\Genesis;
use DecodeLabs\Harvest;
use DecodeLabs\R7\Config\Http as HttpConfig;
use DecodeLabs\R7\Legacy;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class Authorisation implements Middleware
{
    /**
     * Process middleware
     */
    public function process(
        Request $request,
        Handler $next
    ): Response {
        if ($response = $this->enforceCredentials()) {
            return $response;
        }

        if ($response = $this->enforceIpRanges($request)) {
            return $response;
        }

        return $next->handle($request);
    }

    protected function enforceCredentials(): ?Response
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



    protected function enforceIpRanges(
        Request $request
    ): ?Response {
        // Get ranges from config
        $config = HttpConfig::load();
        $ranges = $config->getIpRanges();

        if (
            empty($ranges) ||
            Legacy::$http->isDfSelf()
        ) {
            return null;
        }

        $ip = Harvest::extractIpFromRequest($request);

        // Apply
        $augmentor = Legacy::$http->getResponseAugmentor();
        $augmentor->setHeaderForAnyRequest('x-allow-ip', (string)$ip);

        foreach ($ranges as $range) {
            if ($range->contains($ip)) {
                $augmentor->setHeaderForAnyRequest('x-allow-ip-range', (string)$range);
                return null;
            }
        }

        // Loopback check
        if ($ip->isLoopback()) {
            $augmentor->setHeaderForAnyRequest('x-allow-ip-range', 'loopback');
            return null;
        }

        // Resolved IP check
        $current = Ip::parse(
            gethostbyname((string)gethostname())
        );

        if ($current->matches($ip)) {
            $augmentor->setHeaderForAnyRequest('x-allow-ip-range', 'loopback');
            return null;
        }

        $url = $request->getUri();
        $query = $request->getQueryParams();
        $cookies = $request->getCookieParams();
        $server = $request->getServerParams();

        // Test for passthrough requests
        if (str_starts_with(
            $url->getPath(),
            '/.well-known/pki-validation/'
        )) {
            return null;
        }


        // Authenticate
        if (
            isset($query['authenticate']) &&
            !isset($cookies['ipbypass'])
        ) {
            setcookie('ipbypass', '1', 0, '/');
        }

        if (
            isset($query['authenticate']) ||
            isset($server['PHP_AUTH_USER']) ||
            isset($cookies['ipbypass'])
        ) {
            static $salt = '3efcf3200384a9968a58841812d78f94d88a61b2e0cc57849a19707e0ebed065';
            static $username = 'e793f732b58b8c11ae4048214f9171392a864861d35c0881b3993d12001a78b0';
            static $password = '016ede424aa10ae5895c21c33d200c7b08aa33d961c05c08bfcf946cb7c53619';

            if (
                isset($server['PHP_AUTH_USER']) &&
                Legacy::hexHash($server['PHP_AUTH_USER'], $salt) == $username &&
                Legacy::hexHash($server['PHP_AUTH_PW'], $salt) == $password
            ) {
                return null;
            }

            return Harvest::text('You need to authenticate to view this site', 401, [
                'WWW-Authenticate' => 'Basic realm="Private Site"'
            ]);
        }


        // Not resolved
        $url = clone Legacy::$http->getUrl();
        $url->query->authenticate = null;

        return Harvest::html(
            '<html><head><title>Forbidden</title></head><body>' .
            '<p>Sorry, this site is protected by IP range.</p><p>Your IP is: <strong>' . $ip . '</strong></p>' .
            '<p><a href="' . $url . '">Developer access</a></p>',
            403,
        );
    }
}

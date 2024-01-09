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
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class Protocol implements Middleware
{
    public const METHODS = [
        'GET',
        'HEAD',
        'POST'
    ];

    /**
     * Process middleware
     */
    public function process(
        Request $request,
        Handler $next
    ): Response {
        $config = HttpConfig::load();

        // Check for HTTPS
        if (
            $config->isSecure() &&
            $request->getUri()->getScheme() !== 'https' &&
            Genesis::$environment->isProduction()
        ) {
            $url = $request->getUri()
                ->withScheme('https')
                ->withPort(null);

            return Harvest::redirect($url);
        }


        // Check HTTP method
        $method = $request->getMethod();

        if (!in_array($method, self::METHODS)) {
            $response = Harvest::text('', $method === 'OPTIONS' ? 200 : 405, [
                'allow' => 'OPTIONS, GET, HEAD, POST'
            ]);

            if ($method === 'OPTIONS') {
                // CORS
                $response = (new Headers())->handleAccessControl($request, $response);
            }

            return $response;
        }


        // Continue
        $response = $next->handle($request);


        // HSTS
        if ($config->isSecure()) {
            $response = $response->withHeader(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $response;
    }
}

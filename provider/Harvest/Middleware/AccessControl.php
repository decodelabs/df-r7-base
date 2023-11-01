<?php
/**
 * This file is part of the Decode r7 framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Harvest\Middleware;

use DecodeLabs\R7\Legacy;
use DecodeLabs\Singularity;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class AccessControl implements Middleware
{
    /**
     * Process middleware
     */
    public function process(
        Request $request,
        Handler $next
    ): Response {
        $response = $next->handle($request);

        if (!$request->hasHeader('origin')) {
            return $response;
        }

        $url = Singularity::url(
            $request->getHeaderLine('origin')
        );

        $router = Legacy::$http->getRouter();

        if (!$router->lookupDomain($url->getHost())) {
            return $response;
        }

        $response = $response->withHeader(
            'access-control-allow-origin',
            '*'
        );

        // Include from config
        $response = $response->withHeader(
            'access-control-allow-headers',
            'x-ajax-request-source, x-ajax-request-type'
        );

        return $response;
    }
}

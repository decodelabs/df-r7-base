<?php
/**
 * This file is part of the Decode r7 framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Harvest\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class AjaxResponseUrl implements Middleware
{
    /**
     * Process middleware
     */
    public function process(
        Request $request,
        Handler $next
    ): Response {
        $response = $next->handle($request);

        if (
            strtolower($request->getHeaderLine('x-requested-with')) === 'xmlhttprequest' ||
            $request->hasHeader('x-ajax-request-type')
        ) {
            $response = $response->withHeader(
                'x-response-url',
                (string)$request->getUri()
            );
        }

        return $response;
    }
}

<?php
/**
 * This file is part of the Decode r7 framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Harvest\Middleware;

use DecodeLabs\Harvest;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class HttpMethod implements Middleware
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
        $method = $request->getMethod();

        if (!in_array($method, self::METHODS)) {
            return Harvest::text('', $method === 'OPTIONS' ? 200 : 405, [
                'allow' => 'OPTIONS, GET, HEAD, POST'
            ]);
        }

        return $next->handle($request);
    }
}

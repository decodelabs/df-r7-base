<?php
/**
 * This file is part of the Decode r7 framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Harvest\Middleware;

use df\core\time\Date;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class ClientCache implements Middleware
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
            $request->getMethod() !== 'GET' ||
            !(
                $request->hasHeader('if-modified-since') ||
                $request->hasHeader('if-none-match')
            ) ||
            ($lastModified = $response->getHeaderLine('last-modified')) === ''
        ) {
            return $response;
        }

        $lastModified = Date::factory($lastModified)->toTimestamp();

        $modifiedSince = (new Date(
            explode(';', $request->getHeaderLine('if-modified-since'))[0]
        ))->toTimestamp();

        if ($lastModified > $modifiedSince) {
            return $response;
        }

        return $response->withStatus(304);
    }
}

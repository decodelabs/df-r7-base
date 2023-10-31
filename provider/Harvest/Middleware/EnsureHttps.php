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

class EnsureHttps implements Middleware
{
    /**
     * Process middleware
     */
    public function process(
        Request $request,
        Handler $next
    ): Response {
        $config = HttpConfig::load();

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

        return $next->handle($request);
    }
}

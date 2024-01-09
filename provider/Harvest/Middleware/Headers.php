<?php
/**
 * This file is part of the Decode r7 framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Harvest\Middleware;

use DecodeLabs\R7\Legacy;
use DecodeLabs\Singularity;
use df\core\time\Date;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class Headers implements Middleware
{
    /**
     * Process middleware
     */
    public function process(
        Request $request,
        Handler $next
    ): Response {
        $response = $next->handle($request);

        // Apply globally defined cookies, headers, etc
        $response = Legacy::$http->getResponseAugmentor()->applyPsr($response);

        $response = $this->handleAccessControl($request, $response);
        $response = $this->handleAjaxResponse($request, $response);
        $response = $this->handleClientCache($request, $response);

        return $response;
    }


    /**
     * Add access-control headers
     */
    protected function handleAccessControl(
        Request $request,
        Response $response
    ): Response {
        if (!$request->hasHeader('origin')) {
            return $response;
        }

        $url = Singularity::url(
            $request->getHeaderLine('origin')
        );

        $router = Legacy::$http->getRouter();

        if (!$router->lookupDomain($url->getHost())) {
            $response = $response->withHeader(
                'x-legacy-domain',
                $url->getHost()
            );

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


    /**
     * Add x-response-url header
     */
    protected function handleAjaxResponse(
        Request $request,
        Response $response
    ): Response {
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


    /**
     * Handle client cache
     */
    protected function handleClientCache(
        Request $request,
        Response $response
    ): Response {
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

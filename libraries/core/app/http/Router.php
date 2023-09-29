<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\app\http;

use DecodeLabs\Exceptional;
use DecodeLabs\Genesis;
use DecodeLabs\R7\Legacy;

use df\arch;
use df\arch\ForcedResponse;
use df\arch\Request;

use df\core;
use df\link;
use df\link\http\IRequest as HttpRequest;

class Router implements core\IRegistryObject
{
    public const REGISTRY_KEY = 'httpRouter';

    protected $_mapIn = [];
    protected $_mapOut = [];
    protected $_useHttps = false;

    protected $_rootUrl;
    protected $_baseUrl;
    protected $_baseMap;

    protected $_routeMatchCount = 0;
    protected $_routeCount = 0;
    protected $_routerCache = [];
    protected $_defaultRouteProtocol = null;

    protected $_rootNodeRouter = false;

    public function __construct(link\http\IUrl $rootUrl = null)
    {
        $config = Config::getInstance();

        if ($rootUrl !== null) {
            $map = ['*' => $rootUrl->getDomain() . '/' . ltrim($rootUrl->getPathString())];
            $this->_useHttps = $rootUrl->isSecure();
            $this->_defaultRouteProtocol = $this->_useHttps ? 'https' : 'http';
        } else {
            $map = $config->getBaseUrlMap();
            $this->_useHttps = $config->isSecure();
            $this->_defaultRouteProtocol = (isset($_SERVER['HTTPS']) && !strcasecmp($_SERVER['HTTPS'], 'on')) ? 'https' : 'http';
        }

        if (isset($_SERVER['HTTP_X_ORIGINAL_HOST'])) {
            $testHost = $_SERVER['HTTP_X_ORIGINAL_HOST'];
        } elseif (isset($_SERVER['HTTP_HOST'])) {
            $testHost = $_SERVER['HTTP_HOST'];
        } else {
            $testHost = null;
        }

        foreach ($map as $area => $domain) {
            if ($area === 'front') {
                throw Exceptional::Setup(
                    'Front area must be mapped to root url'
                );
            }

            $entry = new Router_Map($area, $domain);

            $this->_mapIn[$entry->getInDomain()] = $entry;

            if (!isset($this->_mapOut[$entry->area])
            || ($testHost !== null && $testHost == $entry->domain)) {
                $this->_mapOut[$entry->area] = $entry;
            }
        }
    }

    public function getRegistryObjectKey(): string
    {
        return self::REGISTRY_KEY;
    }





    // Mapping
    public function lookupDomain($domain)
    {
        if (isset($this->_mapIn[$domain])) {
            return $this->_mapIn[$domain];
        }

        $parts = explode('.', $domain);
        $sub = array_shift($parts);

        if ($sub == 'www') {
            $domain = implode('.', $parts);
            $sub = array_shift($parts);
        }

        $test = implode('.', $parts);

        if (isset($this->_mapIn['*.' . $test])) {
            $output = clone $this->_mapIn['*.' . $test];
            $output->mappedDomain = $domain;
            $output->mappedKey = $sub;
            return $output;
        }
    }

    public function getMapIn(string $area): ?Router_Map
    {
        if (!isset($this->_mapIn[$area])) {
            return null;
        }

        return $this->_mapIn[$area];
    }

    public function getMapOut(string $area): ?Router_Map
    {
        if (!isset($this->_mapOut[$area])) {
            return null;
        }

        return $this->_mapOut[$area];
    }

    public function getRootMap()
    {
        if (isset($this->_mapOut['*'])) {
            return $this->_mapOut['*'];
        } elseif (isset($this->_mapOut['front'])) {
            return $this->_mapOut['front'];
        } else {
            throw Exceptional::Setup(
                'No root map defined'
            );
        }
    }

    public function shouldUseHttps()
    {
        return $this->_useHttps;
    }

    public function countMaps(): int
    {
        return count($this->_mapIn);
    }


    public function setBase(Router_Map $map)
    {
        if ($map->isSecure) {
            $this->_useHttps = true;
        }

        $this->_baseMap = $map;
        $this->_baseUrl = $map->toUrl($this->_useHttps);

        return $this;
    }

    public function getBaseUrl()
    {
        if (!$this->_baseUrl) {
            $this->_applyDefaultBaseMap();
        }

        return $this->_baseUrl;
    }

    public function getBaseMap()
    {
        if (!$this->_baseMap) {
            $this->_applyDefaultBaseMap();
        }

        return $this->_baseMap;
    }

    protected function _applyDefaultBaseMap()
    {
        $this->setBase($this->getRootMap());
    }

    public function getRootUrl()
    {
        if (!$this->_rootUrl) {
            $rootMap = $this->getRootMap();
            $this->_rootUrl = $rootMap->toUrl($this->_useHttps);
        }

        return $this->_rootUrl;
    }

    public function isBaseRoot()
    {
        $base = $this->getBaseMap();
        $root = $this->getRootMap();

        return $base === $root;
    }

    public function isBaseInRoot(): bool
    {
        $base = $this->getBaseMap();
        $root = $this->getRootMap();

        if ($base === $root) {
            return true;
        }

        if (!$base->mappedDomain) {
            return false;
        }

        $baseDomain = $base->mappedDomain;
        $rootDomain = $root->domain;

        if (substr($rootDomain, 0, 4) == 'www.') {
            $rootDomain = substr($rootDomain, 4);
        }
        if (substr($baseDomain, 0, 4) == 'www.') {
            $baseDomain = substr($baseDomain, 4);
        }

        return substr($baseDomain, -strlen($rootDomain)) == $rootDomain;
    }


    public function applyBaseMapToRelativeRequest(Request $request)
    {
        if (!$this->_baseMap) {
            return $request;
        }

        if ($this->_baseMap->isWild) {
            $request->query->{$this->_baseMap->area} = $this->_baseMap->mappedKey;
        }

        return $request;
    }



    // Routing
    public function countRoutes()
    {
        return $this->_routeCount;
    }

    public function countRouteMatches()
    {
        return $this->_routeMatchCount;
    }

    public function requestToUrl(Request $request)
    {
        $origRequest = $request;
        $request = $this->routeOut(clone $request);
        $area = $request->getArea();

        if (isset($this->_mapOut[$area])) {
            $map = $this->_mapOut[$area];
        } elseif (isset($this->_mapOut['*'])) {
            $map = $this->_mapOut['*'];
        } else {
            $map = null;
        }

        return link\http\Url::fromDirectoryRequest(
            $request,
            $this->_defaultRouteProtocol,
            $map,
            $origRequest
        );
    }

    public function urlToRequest(link\http\IUrl $url)
    {
        if (!$map = $this->lookupDomain($url->getDomain())) {
            throw Exceptional::Runtime(
                'Unable to map url domain'
            );
        }

        $path = clone $url->getPath();

        if (!$map->mapPath($path)) {
            throw Exceptional::Runtime(
                'Unable to map url path'
            );
        }

        $request = new arch\Request();
        $request->setPath($path);
        $map->mapArea($request);

        $request->setFragment($url->getFragment());

        if ($url->hasQuery()) {
            $request->setQuery(clone $url->getQuery());
        }

        if ($map->mappedKey) {
            $request->query->{$map->area} = $map->mappedKey;
        }

        $request = $this->routeIn($request);
        return $request;
    }

    public function routeIn(Request $request): Request
    {
        $this->_routeCount++;
        $location = $request->getDirectoryLocation();

        if ($location == 'front') {
            $root = $this->_getRootNodeRouter();

            if ($root && ($output = $root->routeIn($request))) {
                return $output;
            }
        }

        if ($router = $this->_getRouterFor($request, $location)) {
            $this->_routeMatchCount++;
            $output = $router->routeIn($request);

            if ($output instanceof Request) {
                $request = $output;
            }
        }

        return $request;
    }

    public function routeOut(Request $request)
    {
        $this->_routeCount++;
        $location = $request->getDirectoryLocation();

        if ($router = $this->_getRouterFor($request, $location)) {
            $this->_routeMatchCount++;
            $output = $router->routeOut($request);

            if ($output instanceof Request) {
                $request = $output;
            }
        }

        return $request;
    }

    protected function _getRouterFor(Request $request, string $location): ?arch\IRouter
    {
        if (isset($this->_routerCache[$location])) {
            return $this->_routerCache[$location];
        }

        $keys[] = $location;
        $parts = explode('/', $location);
        $output = null;

        while (!empty($parts)) {
            $class = 'df\\apex\\directory\\' . implode('\\', $parts) . '\\HttpRouter';
            $keys[] = implode('/', $parts);

            if (class_exists($class)) {
                $output = new $class();
                break;
            }

            array_pop($parts);
        }

        foreach ($keys as $key) {
            $this->_routerCache[$key] = $output;
        }

        return $output;
    }

    protected function _getRootNodeRouter(): ?arch\IRouter
    {
        if ($this->_rootNodeRouter === false) {
            $class = 'df\\apex\\directory\\front\\HttpRootNodeRouter';

            if (class_exists($class)) {
                $this->_rootNodeRouter = new $class();
            } else {
                $this->_rootNodeRouter = null;
            }
        }

        return $this->_rootNodeRouter;
    }




    /**
     * Prepare directory request
     */
    public function prepareDirectoryRequest(
        HttpRequest $httpRequest
    ): Request {
        $pathValid = $valid = true;
        $redirectPath = '/';

        $url = $httpRequest->getUrl();
        $path = clone $url->getPath();

        if (!$map = $this->lookupDomain($url->getDomain())) {
            $tempMap = $this->getRootMap();

            if (!$tempMap->mapPath($path)) {
                $pathValid = false;
            }

            $valid = false;
        } else {
            if (
                $map->mappedDomain !== null &&
                $map->mappedDomain !== $url->getDomain()
            ) {
                $valid = false;
            }

            $this->setBase($map);

            if (!$map->mapPath($path)) {
                $pathValid = $valid = false;
            }

            if (
                $pathValid &&
                $valid &&
                $map->area === '*'
            ) {
                $area = (string)$path->get(0);

                if (substr($area, 0, 1) != '~') {
                    $area = 'front';
                } else {
                    $area = substr($area, 1);
                }

                $mapOut = $this->getMapOut($area);

                if (
                    $mapOut &&
                    $mapOut->area !== $map->area
                ) {
                    $valid = false;
                }
            }
        }

        if (
            $pathValid &&
            !$path->isEmpty()
        ) {
            $redirectPath = (string)$path;
        }

        if (!$valid) {
            $redirectRequest = (new Request($redirectPath))
                ->setQuery($url->getQuery());

            if (
                $map &&
                $map->area !== '*'
            ) {
                $redirectRequest->setArea($map->area);
                $redirectRequest->query->{$map->area} = $map->mappedKey;
            }

            $baseUrl = $this->requestToUrl($redirectRequest);

            if ($this->shouldUseHttps()) {
                $baseUrl->isSecure(true);
            }

            $baseUrl = (string)$baseUrl;

            if (Genesis::$environment->isDevelopment()) {
                $response = Legacy::$http->stringResponse(
                    '<html><head><title>Bad request</title></head><body>' .
                    '<p>Sorry, you are not in the right place!</p>' .
                    '<p>Go here instead: <a href="' . $baseUrl . '">' . $baseUrl . '</a></p>',
                    'text/html'
                );

                $response->getHeaders()->setStatusCode(404);
            } else {
                $response = Legacy::$http->redirect($baseUrl);
                $response->isPermanent(true);
            }

            throw new ForcedResponse($response);
        }


        // Build init request
        $request = new Request();

        if ($path !== null) {
            if (preg_match('/^\~[a-zA-Z0-9_]+$/i', (string)$path)) {
                $orig = (string)$url;
                $url->getPath()->shouldAddTrailingSlash(true);

                if ((string)$url != $orig) {
                    $response = Legacy::$http->redirect($url);
                    //$response->isPermanent(true);
                    throw new ForcedResponse($response);
                }
            }

            $request->setPath($path);
        }

        $map->mapArea($request);

        if ($url->hasQuery()) {
            $request->setQuery(clone $url->getQuery());
        }

        if ($map->mappedKey) {
            $request->query->{$map->area} = $map->mappedKey;
        }

        $request->setFragment($url->getFragment());

        if ($httpRequest->getHeaders()->has('x-ajax-request-type')) {
            $request->setType($httpRequest->getHeaders()->get('x-ajax-request-type'));
        }

        $request = $this->routeIn($request);
        return $request;
    }
}


class Router_Map
{
    public $domain;
    public $port;
    public $isSecure = false;
    public $isWild = false;
    public $area;
    public $path;
    public $mappedDomain;
    public $mappedKey;

    public function __construct($area, $domain)
    {
        if (is_int($area)) {
            $area = '*';
        }

        if (substr($area, 0, 1) == '*') {
            $area = '*';
        }

        $this->area = ltrim($area, '~');
        $domain = trim($domain, '/');
        $parts = explode('://', $domain, 2);
        $domain = (string)array_pop($parts);
        $scheme = array_shift($parts) ?? 'http';

        if (strtolower((string)$scheme) === 'https') {
            $this->isSecure = true;
        }

        $parts = explode('/', $domain);
        $this->domain = (string)array_shift($parts);
        $this->path = $parts;

        if (substr($this->domain, 0, 2) == '*.') {
            $this->domain = substr($this->domain, 2);
            $this->isWild = true;
        }

        if (false !== strpos($this->domain, ':')) {
            list($this->domain, $this->port) = explode(':', $this->domain, 2);
        }
    }

    public function getInDomain()
    {
        $output = $this->domain;

        if ($this->isWild) {
            $output = '*.' . $output;
        }

        return $output;
    }


    public function mapPath(core\uri\IPath $path = null)
    {
        if (!$path) {
            return false;
        }

        $output = true;

        foreach ($this->path as $part) {
            if ($part != $path->shift()) {
                $output = false;
                break;
            }
        }

        return $output;
    }

    public function mapArea(Request $request)
    {
        if ($this->area !== '*' && $this->area !== 'front') {
            $path = $request->getPath();

            if ($path->isEmpty()) {
                $path->shouldAddTrailingSlash(true);
            }

            $path->unshift(arch\Request::AREA_MARKER . $this->area);
        }

        return $request;
    }

    public function toUrl($useHttps = false)
    {
        if ($this->mappedDomain) {
            $url = $this->mappedDomain;
        } else {
            $url = $this->domain;
        }

        if ($this->port) {
            $url .= ':' . $this->port;
        }

        $url .= '/';

        if (!empty($this->path)) {
            $url .= implode('/', $this->path) . '/';
        }

        return (new link\http\Url($url))->isSecure($useHttps);
    }
}

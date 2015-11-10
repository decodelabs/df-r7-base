<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\application\http;

use df;
use df\core;
use df\arch;
use df\link;

class Router implements core\IRegistryObject {

    const REGISTRY_KEY = 'httpRouter';

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

    protected $_rootActionRouter;

    public static function getInstance() {
        $application = df\Launchpad::getApplication();

        if(!$output = $application->getRegistryObject(self::REGISTRY_KEY)) {
            $output = new self();
            $application->setRegistryObject($output);
        }

        return $output;
    }

    public function __construct(link\http\IUrl $rootUrl=null) {
        $config = core\application\http\Config::getInstance();

        if($rootUrl !== null) {
            $map = ['*' => $rootUrl->getDomain().'/'.ltrim($rootUrl->getPathString())];
            $this->_useHttps = $rootUrl->isSecure();
            $this->_defaultRouteProtocol = $this->_useHttps ? 'https' : 'http';
        } else {
            $map = $config->getBaseUrlMap();
            $this->_useHttps = $config->isSecure();
            $this->_defaultRouteProtocol = (isset($_SERVER['HTTPS']) && !strcasecmp($_SERVER['HTTPS'], 'on')) ? 'https' : 'http';
        }

        foreach($map as $area => $domain) {
            if($area === 'front') {
                throw new core\RuntimeException(
                    'Front area must be mapped to root url'
                );
            }

            $entry = new Router_Map($area, $domain);

            $this->_mapIn[$entry->getInDomain()] = $entry;
            $this->_mapOut[$entry->area] = $entry;
        }
    }

    public function getRegistryObjectKey() {
        return self::REGISTRY_KEY;
    }





// Mapping
    public function lookupDomain($domain) {
        if(isset($this->_mapIn[$domain])) {
            return $this->_mapIn[$domain];
        }

        $parts = explode('.', $domain);
        $sub = array_shift($parts);
        $test = implode('.', $parts);

        if(isset($this->_mapIn['*.'.$test])) {
            $output = clone $this->_mapIn['*.'.$test];
            $output->mappedDomain = $domain;
            $output->mappedKey = $sub;
            return $output;
        }
    }

    public function getRootMap() {
        if(isset($this->_mapOut['*'])) {
            return $this->_mapOut['*'];
        } else if(isset($this->_mapOut['front'])) {
            return $this->_mapOut['front'];
        } else {
            throw new core\RuntimeException(
                'No root map defined'
            );
        }
    }

    public function shouldUseHttps() {
        return $this->_useHttps;
    }


    public function setBase(Router_Map $map) {
        $this->_baseMap = $map;
        $this->_baseUrl = $map->toUrl($this->_useHttps);
        return $this;
    }

    public function getBaseUrl() {
        if(!$this->_baseUrl) {
            $this->_applyDefaultBaseMap();
        }

        return $this->_baseUrl;
    }

    public function getBaseMap() {
        if(!$this->_baseMap) {
            $this->_applyDefaultBaseMap();
        }

        return $this->_baseMap;
    }

    protected function _applyDefaultBaseMap() {
        $this->setBase($this->getRootMap());
    }

    public function getRootUrl() {
        if(!$this->_rootUrl) {
            $rootMap = $this->getRootMap();
            $this->_rootUrl = $rootMap->toUrl($this->_useHttps);
        }

        return $this->_rootUrl;
    }

    public function isBaseRoot() {
        $base = $this->getBaseMap();
        $root = $this->getRootMap();

        return $base === $root;
    }


    public function applyBaseMapToRelativeRequest(arch\IRequest $request) {
        if(!$this->_baseMap) {
            return $request;
        }

        if($this->_baseMap->isWild) {
            $request->query->{$this->_baseMap->area} = $this->_baseMap->mappedKey;
        }

        return $request;
    }



// Routing
    public function countRoutes() {
        return $this->_routeCount;
    }

    public function countRouteMatches() {
        return $this->_routeMatchCount;
    }

    public function requestToUrl(arch\IRequest $request) {
        $origRequest = $request;
        $request = $this->routeOut(clone $request);
        $area = $request->getArea();

        if(isset($this->_mapOut[$area])) {
            $map = $this->_mapOut[$area];
        } else if(isset($this->_mapOut['*'])) {
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

    public function urlToRequest(link\http\IUrl $url) {
        if(!$map = $this->lookupDomain($url->getDomain())) {
            throw new core\RuntimeException('Unable to map url domain');
        }

        $path = clone $url->getPath();

        if(!$map->mapPath($path)) {
            throw new core\RuntimeException('Unable to map url path');
        }

        $request = new arch\Request();
        $request->setPath($path);
        $map->mapArea($request);

        $request->setFragment($url->getFragment());

        if($url->hasQuery()) {
            $request->setQuery(clone $url->getQuery());
        }

        if($map->mappedKey) {
            $request->query->{$map->area} = $map->mappedKey;
        }

        $request = $this->routeIn($request);
        return $request;
    }

    public function routeIn(arch\IRequest $request) {
        $this->_routeCount++;
        $location = $request->getDirectoryLocation();

        if($location == 'front') {
            $root = $this->_getRootActionRouter();

            if($root && ($output = $root->routeIn($request))) {
                return $output;
            }
        }

        if($router = $this->_getRouterFor($request, $location)) {
            $this->_routeMatchCount++;
            $output = $router->routeIn($request);

            if($output instanceof arch\IRequest) {
                $request = $output;
            }
        }

        return $request;
    }

    public function routeOut(arch\IRequest $request) {
        $this->_routeCount++;
        $location = $request->getDirectoryLocation();

        if($router = $this->_getRouterFor($request, $location)) {
            $this->_routeMatchCount++;
            $output = $router->routeOut($request);

            if($output instanceof arch\IRequest) {
                $request = $output;
            }
        }

        return $request;
    }

    protected function _getRouterFor(arch\IRequest $request, $location) {
        if(isset($this->_routerCache[$location])) {
            return $this->_routerCache[$location];
        }

        $keys[] = $location;
        $parts = explode('/', $location);
        $output = false;

        while(!empty($parts)) {
            $class = 'df\\apex\\directory\\'.implode('\\', $parts).'\\HttpRouter';
            $keys[] = implode('/', $parts);

            if(class_exists($class)) {
                $output = new $class();
                break;
            }

            array_pop($parts);
        }

        foreach($keys as $key) {
            $this->_routerCache[$key] = $output;
        }

        return $output;
    }

    protected function _getRootActionRouter() {
        if($this->_rootActionRouter === null) {
            $class = 'df\\apex\\directory\\front\\HttpRootActionRouter';

            if(class_exists($class)) {
                $this->_rootActionRouter = new $class();
            } else {
                $this->_rootActionRouter = false;
            }
        }

        return $this->_rootActionRouter;
    }
}


class Router_Map {

    public $domain;
    public $port;
    public $isWild = false;
    public $area;
    public $path;
    public $mappedDomain;
    public $mappedKey;

    public function __construct($area, $domain) {
        $this->area = ltrim($area, '~');
        $parts = explode('/', trim($domain, '/'));
        $this->domain = array_shift($parts);
        $this->path = $parts;

        if(substr($this->domain, 0, 2) == '*.') {
            $this->domain = substr($this->domain, 2);
            $this->isWild = true;
        }

        if(false !== strpos($this->domain, ':')) {
            list($this->domain, $this->port) = explode(':', $this->domain, 2);
        }
    }

    public function getInDomain() {
        $output = $this->domain;

        if($this->isWild) {
            $output = '*.'.$output;
        }

        return $output;
    }


    public function mapPath(core\uri\IPath $path=null) {
        if(!$path) {
            return false;
        }

        $output = true;

        foreach($this->path as $part) {
            if($part != $path->shift()) {
                $output = false;
                break;
            }
        }

        return $output;
    }

    public function mapArea(arch\IRequest $request) {
        if($this->area !== '*' && $this->area !== 'front') {
            $path = $request->getPath();

            if($path->isEmpty()) {
                $path->shouldAddTrailingSlash(true);
            }

            $path->unshift(arch\Request::AREA_MARKER.$this->area);
        }

        return $request;
    }

    public function toUrl($useHttps=false) {
        if($this->mappedDomain) {
            $url = $this->mappedDomain;
        } else {
            $url = $this->domain;
        }

        if($this->port) {
            $url .= ':'.$this->port;
        }

        $url .= '/';

        if(!empty($this->path)) {
            $url .= implode('/', $this->path).'/';
        }

        return (new link\http\Url($url))->isSecure($useHttps);
    }
}
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

    protected $_baseDomain;
    protected $_basePort;
    protected $_basePath;
    protected $_useHttps = false;

    protected $_areaDomainMap = [];
    protected $_mappedArea = null;
    protected $_mappedDomain = null;

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

    public function __construct(link\http\IUrl $url=null) {
        if($url) {
            $this->_basePath = $url->getPath()->toArray();
            $this->_baseDomain = (string)$url->getDomain();
            $this->_basePort = $url->getPort();
            $this->_useHttps = $url->isSecure();
            $this->_defaultRouteProtocol = $this->_useHttps ? 'https' : 'http';
        } else {
            $config = core\application\http\Config::getInstance();
            $this->_basePath = explode('/', $config->getBaseUrl());
            $domain = explode(':', array_shift($this->_basePath), 2);
            $this->_baseDomain = array_shift($domain);
            $this->_basePort = array_shift($domain);
            $this->_useHttps = $config->isSecure();
            $this->_areaDomainMap = $config->getAreaDomainMap();

            $this->_defaultRouteProtocol = (isset($_SERVER['HTTPS']) && !strcasecmp($_SERVER['HTTPS'], 'on')) ? 'https' : 'http';
        }
        

        if(!strlen($this->_basePort)) {
            $this->_basePort = null;
        }
    }

    public function getRegistryObjectKey() {
        return self::REGISTRY_KEY;
    }

// Base Url
    public function getBaseDomain() {
        return $this->_baseDomain;
    }

    public function setBasePort($port) {
        $this->_basePort = $port;
        return $this;
    }

    public function hasBasePort() {
        return $this->_basePort !== null;
    }

    public function getBasePort() {
        return $this->_basePort;
    }

    public function getBasePath() {
        return $this->_basePath;
    }

    public function shouldUseHttps() {
        return $this->_useHttps;
    }

    public function getBaseUrl() {
        if($this->_mappedDomain) {
            $url = $this->_mappedDomain;
        } else {
            $url = $this->_baseDomain.':'.$this->_basePort.'/'.implode('/', $this->_basePath).'/';
        }

        return (new link\http\Url($url))->isSecure($this->_useHttps);
    }

    public function mapDomain($domain) {
        $this->_mappedArea = null;
        $output = true;

        if($domain != $this->_baseDomain) {
            $map = array_flip($this->_areaDomainMap);

            if(isset($map[$domain])) {
                $this->_mappedArea = ltrim($map[$domain], arch\Request::AREA_MARKER);
                $this->_mappedDomain = $domain;
            //} else if(df\Launchpad::$application->isDevelopment()) {
                //$this->_baseDomain = $domain;
            } else {
                $output = false;
            }
        }

        return $output;
    }

    public function mapPath(core\uri\IPath $path=null) {
        $output = true;

        if(!empty($this->_basePath) && !$this->_mappedArea) {
            if(!$path) {
                $output = false;
            } else {
                foreach($this->_basePath as $part) {
                    if($part != $path->shift()) {
                        $output = false;
                        break;
                    }
                }
            }
        }

        return $output;
    }

    public function mapArea(arch\IRequest $request) {
        if($this->_mappedArea) {
            $path = $request->getPath();

            if($path->isEmpty()) {
                $path->shouldAddTrailingSlash(true);
            }

            $path->unshift(arch\Request::AREA_MARKER.$this->_mappedArea);
        }

        return $request;
    }


    public function unmapLocalUrl($url) {
        $output = $this->_baseDomain.'/';

        if(!empty($this->_basePath)) {
            $output .= trim(implode('/', $this->_basePath), '/').'/';
        }

        $output .= ltrim($url, '/');
        return new link\http\Url($output);
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

        $domain = $this->_baseDomain;
        $port = $this->_basePort;
        $path = $this->_basePath;
        $area = $request->getArea();

        if(isset($this->_areaDomainMap[$area])) {
            $domain = $this->_areaDomainMap[$area];
            $path = explode('/', $domain);
            $domain = array_shift($path);
        } else {
            $area = null;
        }

        return link\http\Url::fromDirectoryRequest(
            $request,
            $this->_defaultRouteProtocol,
            $domain, 
            $port, 
            $path,
            $area,
            $origRequest
        );
    }

    public function urlToRequest(link\http\IUrl $url) {
        $path = $url->getPath();
        $this->mapPath($path);
        $this->mapDomain($url->getDomain());
        $request = new arch\Request();
        $request->setPath($path);
        $this->mapArea($request);
            
        if($url->hasQuery()) {
            $request->setQuery(clone $url->getQuery());
        }
        
        $request->setFragment($url->getFragment());
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
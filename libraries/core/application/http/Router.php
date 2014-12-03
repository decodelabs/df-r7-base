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
            $output = new self($application);
            $application->setRegistryObject($output);
        }

        return $output;
    }

    public function __construct() {
        $config = core\application\http\Config::getInstance();
        $this->_basePath = explode('/', $config->getBaseUrl());
        $domain = explode(':', array_shift($this->_basePath), 2);
        $this->_baseDomain = array_shift($domain);
        $this->_basePort = array_shift($domain);
        $this->_useHttps = $config->isSecure();
        $this->_areaDomainMap = $config->getAreaDomainMap();

        $this->_defaultRouteProtocol = (isset($_SERVER['HTTPS']) && !strcasecmp($_SERVER['HTTPS'], 'on')) ? 'https' : 'http';

        if(!strlen($this->_basePort)) {
            $this->_basePort = null;
        }
    }

    public function getRegistryObjectKey() {
        return self::REGISTRY_KEY;
    }

    public function onApplicationShutdown() {}

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
        return new link\http\Url($this->_baseDomain.':'.$this->_basePort.'/'.implode('/', $this->_basePath).'/');
    }


    public function mapPath(core\uri\IPath $path=null) {
        $output = true;

        if(!empty($this->_basePath)) {
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

    public function mapDomain($domain) {
        $this->_mappedArea = null;
        $output = true;

        if($domain != $this->_baseDomain) {
            if(isset($this->_areaDomainMap[$domain])) {
                $this->_mappedArea = ltrim($this->_areaDomainMap[$domain], arch\Request::AREA_MARKER);
                $this->_mappedDomain = $domain;
            } else if(df\Launchpad::$application->isDevelopment()) {
                $this->_baseDomain = $domain;
            } else {
                $output = false;
            }
        }

        return $output;
    }

    public function mapArea(arch\IRequest $request) {
        if($this->_mappedArea) {
            $request->getPath()->unshift(arch\Request::AREA_MARKER.$this->_mappedArea);
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

        $domain = $this->_baseDomain;
        $port = $this->_basePort;
        $path = $this->_basePath;

        if($this->_mappedArea) {
            $area = $request->getArea();

            if($area == $this->_mappedArea) {
                $domain = $this->_mappedDomain;
                $path = [];
            }
        }

        return link\http\Url::fromDirectoryRequest(
            $request,
            $this->_defaultRouteProtocol,
            $domain, 
            $port, 
            $path,
            $this->_mappedArea,
            $origRequest
        );
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
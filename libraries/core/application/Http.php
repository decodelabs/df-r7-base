<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\application;

use df;
use df\core;
use df\halo;
use df\arch;

class Http extends Base {
    
    const RUN_MODE = 'Http';
    
    protected $_baseDomain;
    protected $_basePort;
    protected $_basePath;
    
    protected $_httpRequest;
    protected $_responseAugmentor;
    
    protected $_routeMatchCount = 0;
    protected $_routeCount = 0;
    protected $_routers = array();
    
    
    protected function __construct() {
        parent::__construct();
        
        $envConfig = core\Environment::getInstance($this);
        $this->_basePath = explode('/', $envConfig->getHttpBaseUrl());
        $domain = explode(':', array_shift($this->_basePath), 2);
        $this->_baseDomain = array_shift($domain);
        $this->_basePort = array_shift($domain);
    }
    
    
    public function getBaseUrl() {
        return new halo\protocol\http\Url($this->_baseDomain.':'.$this->_basePort.'/'.implode('/', $this->_basePath).'/');
    }
    
// Routing
    public function requestToUrl(arch\IRequest $request) {
        $request = $this->_routeOut($request);
        
        // TODO: detect secure connection
        
        return halo\protocol\http\Url::fromDirectoryRequest(
            $request,
            'http', 
            $this->_baseDomain, 
            $this->_basePort, 
            $this->_basePath
        );
    }
    
    public function countRoutes() {
        return $this->_routeCount;
    }
    
    public function countRouteMatches() {
        return $this->_routeMatchCount;
    }
    
    protected function _routeIn(arch\IRequest $request) {
        $this->_routeCount++;

        return $request;
    }
    
    protected function _routeOut(arch\IRequest $request) {
        $this->_routeCount++;

        return $request;
    }
    
    
// Execute
    public function dispatch(halo\protocol\http\IRequest $httpRequest=null) {
        $this->_beginDispatch();
        
        if($httpRequest !== null) {
            $this->_httpRequest = $httpRequest;
        } else {
            $this->_httpRequest = new halo\protocol\http\request\Base(null, true);
        }
        
        if(empty($this->_basePort)) {
            $this->_basePort = $this->_httpRequest->getUrl()->getPort();
        }
        
        
        $response = false;
        $previousError = false;
        $valid = true;
        
        $path = null;
        $url = $this->_httpRequest->getUrl();
        
        if($url->hasPath()) {
            $path = clone $url->getPath();
        }
        
        
        // Trim basePath from init request
        if(!empty($this->_basePath)) {
            foreach($this->_basePath as $part) {
                if($part != $path->shift()) {
                    $valid = false;
                    break;
                }
            }
        }
        
        if($url->getDomain() != $this->_baseDomain) {
            $valid = false;
        }
        
        if(!$valid) {
            $baseUrl = (string)$this->requestToUrl(new arch\Request('/'));
                    
            $response = new halo\protocol\http\response\String(
                '<html><head><title>Bad request</title></head><body>'.
                '<p>Sorry, you are not in the right place!</p>'.
                '<p>Go here instead: <a href="'.$baseUrl.'">'.$baseUrl.'</a></p>',
                'text/html'
            );
            
            $response->getHeaders()->setStatusCode(404);
            return $response;
        }
        
        
        // Build init request
        $request = new arch\Request();
        
        if($path) {
            $request->setPath($path);
        }
            
        if($url->hasQuery()) {
            $request->setQuery(clone $url->getQuery());
        }
        
        $request->setFragment($url->getFragment());
        $request = $this->_routeIn($request);
        
        // Start dispatch loop
        while(true) {
            try {
                while(true) {
                    $response = $this->_dispatchRequest($request);
                    
                    // Is this a forward?
                    if($response instanceof arch\IRequest) {
                        $request = $response;
                        continue;
                    }
                    
                    break;
                }
            } catch(\Exception $e) {
                if($previousError) {
                    throw $previousError;
                }
                
                while(ob_get_level()) {
                    ob_end_clean();
                }
                
                $previousError = $e;
                $response = null;
                $request = null;
                
                try {
                    if($this->_context) {
                        $request = clone $this->_context->getRequest();
                    }
                } catch(\Exception $e) {
                    $request = null;
                }
                
                $request = new arch\ErrorRequest($e->getCode(), $e, $request);
                continue;
            }
            
            break;
        }

        return $response;
    }
    
    
    protected function _dispatchRequest(arch\IRequest $request) {
        core\stub($request);
    }
    
    
    private function _runBuildTask() {
        $task = 'util build';
        $r = shell_exec('/mnt/dev/php/php-5.4.0.rc8/bin/php '.$this->getApplicationPath().'/entry/btc-pc.php '.$task);
        echo '<pre>'.$r.'</pre>';
    }


    // Payload
    public function launchPayload($response) {
        // Make sure we're actually in HTTP!
        if(!isset($_SERVER['HTTP_HOST'])) {
            throw new core\RuntimeException(
                'Cannot run http app - this php process does not appear to be running from a http connection'
            );
        }
        
        
        if(!$response instanceof halo\protocol\http\IResponse) {
            echo (string)$response;
            return;
        }
        
        $isFile = $response instanceof halo\protocol\http\IFileResponse;
        
        if($response->hasCookies()) {
            $response->getCookies()->applyTo($response->getHeaders());
        }
        
        $sendData = true;
        
        if($this->_httpRequest->isCachedByClient()) {
            $headers = $response->getHeaders();
            
            if($headers->isCached($this->_httpRequest)) {
                $headers->setStatusCode(304);
                $sendData = false;
            }
        }
        
        
        // TODO: Implement X-Sendfile capabilities
        //if($isFile && $sendData && $response->isStaticFile()) {
            
        //}
        
        if($response->hasHeaders()) {
            $response->getHeaders()->send();
        }
        
        if($sendData) {
            while(ob_get_level()) {
                ob_end_clean();
            }
            
            set_time_limit(0);
            
            // TODO: Seek to resume header location if requested
            
            if($isFile) {
                $file = $response->getContentFileStream();
            
                while(!$file->eof()) {
                    echo $file->read(8192);
                }
                
                $file->close();
                
                // DELETE ME
                df\Launchpad::shutdown();
            } else {
                echo $response->getContent();
            }
        }
    }
    
// Environment
    public function getDebugTransport() {
        $output = parent::getDebugTransport();
        
        if($this->_responseAugmentor) {
            $output->setResponseAugmentor($this->_responseAugmentor);
        }
        
        return $output;
    }
    
    protected function _getNewDebugTransport() {
        return new core\debug\transport\Http();
    }
}

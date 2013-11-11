<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\application;

use df;
use df\core;
use df\halo;
use df\flow;
use df\arch;

class Http extends Base implements arch\IRoutedDirectoryRequestApplication, halo\protocol\http\IResponseAugmentorProvider {
    
    const RUN_MODE = 'Http';
    
    protected $_baseDomain;
    protected $_basePort;
    protected $_basePath;
    
    protected $_httpRequest;
    protected $_responseAugmentor;
    protected $_sendFileHeader;
    
    protected $_context;
    
    protected $_routeMatchCount = 0;
    protected $_routeCount = 0;
    protected $_routers = array();
    protected $_defaultRouteProtocol = null;
    
    
    protected function __construct() {
        parent::__construct();
        
        $envConfig = core\Environment::getInstance($this);
        $this->_basePath = explode('/', $envConfig->getHttpBaseUrl());
        $domain = explode(':', array_shift($this->_basePath), 2);
        $this->_baseDomain = array_shift($domain);
        $this->_basePort = array_shift($domain);
        $this->_sendFileHeader = $envConfig->getSendFileHeader();
    }
    
    
// Base Url
    public function getBaseUrl() {
        return new halo\protocol\http\Url($this->_baseDomain.':'.$this->_basePort.'/'.implode('/', $this->_basePath).'/');
    }
    
    
// Http request
    public function setHttpRequest(halo\protocol\http\IRequest $request) {
        $this->_httpRequest = $request;
        return $this;
    }
    
    public function getHttpRequest() {
        if(!$this->_httpRequest) {
            throw new core\RuntimeException(
                'The http request is not available until the application has been dispatched'
            );
        }
        
        return $this->_httpRequest;    
    }
    
    
// Response augmentor
    public function getResponseAugmentor() {
        if(!$this->_responseAugmentor) {
            $this->_responseAugmentor = new halo\protocol\http\response\Augmentor();
        }
        
        return $this->_responseAugmentor;
    }
    
    
// Context
    public function getContext() {
        if(!$this->_context) {
            throw new core\RuntimeException(
                'A context is not available until the application has been dispatched'
            );
        }
        
        return $this->_context;
    }

    public function hasContext() {
        return $this->_context !== null;
    }
    
    public function getDispatchRequest() {
        return $this->getContext()->request;
    }
    
    
    
// Routing
    public function requestToUrl(arch\IRequest $request) {
        $request = $this->_routeOut($request);

        if($this->_defaultRouteProtocol === null) {
            $this->_defaultRouteProtocol = (isset($_SERVER['HTTPS']) && !strcasecmp($_SERVER['HTTPS'], 'on')) ? 'https' : 'http';
        }

        return halo\protocol\http\Url::fromDirectoryRequest(
            $request,
            $this->_defaultRouteProtocol,
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

        if($router = $this->_getRouterFor($request)) {
            $this->_routeMatchCount++;
            $output = $router->routeIn($request);

            if($output instanceof arch\IRequest) {
                $request = $output;
            }
        }

        return $request;
    }
    
    protected function _routeOut(arch\IRequest $request) {
        $this->_routeCount++;

        if($router = $this->_getRouterFor($request)) {
            $this->_routeMatchCount++;
            $output = $router->routeOut($request);

            if($output instanceof arch\IRequest) {
                $request = $output;
            }
        }

        return $request;
    }

    protected $_routerCache = array();

    protected function _getRouterFor(arch\IRequest $request) {
        $location = $request->getDirectoryLocation();

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
                $output = new $class($this->_application);
                break;
            }

            array_pop($parts);
        }
        
        foreach($keys as $key) {
            $this->_routerCache[$key] = $output;
        }

        return $output;
    }

    public function getDefaultDirectoryAccess() {
        return arch\IAccess::NONE;
    }
    
    
// Execute
    public function dispatch(halo\protocol\http\IRequest $httpRequest=null) {
        $this->_beginDispatch();

        if($this->isDevelopment()) {
            $envConfig = core\Environment::getInstance($this);

            if($credentials = $envConfig->getDeveloperCredentials()) {
                if(!isset($_SERVER['PHP_AUTH_USER'])
                || $_SERVER['PHP_AUTH_USER'] != $credentials['user']
                || $_SERVER['PHP_AUTH_PW'] != $credentials['password']) {
                    header('WWW-Authenticate: Basic realm="Developer Site"');
                    header('HTTP/1.0 401 Unauthorized');
                    echo 'You need to authenticate to view this development site';
                    exit;
                }
            }
        }
        
        if($httpRequest !== null) {
            $this->_httpRequest = $httpRequest;
        } else {
            $this->_httpRequest = new halo\protocol\http\request\Base(null, true);
        }

        if($this->_httpRequest->hasCookie('debug')) {
            df\Launchpad::$isTesting = true;
            df\Launchpad::$debug = $this->createDebugContext();

            flow\Manager::getInstance($this)->flashNow(
                    'global.debug', 
                    'Currently in enforced debug mode', 
                    'debug'
                )
                ->setLink('~devtools/application/debug-mode', 'Change debug settings');
        }

        if(empty($this->_basePort)) {
            $this->_basePort = $this->_httpRequest->getUrl()->getPort();
        }
        
        
        $response = false;
        $previousError = false;
        $valid = true;
        $redirectPath = '/';
        
        $path = null;
        $url = $this->_httpRequest->getUrl();
        
        if($url->hasPath()) {
            $path = clone $url->getPath();
        }
        
        
        // Trim basePath from init request
        if(!empty($this->_basePath)) {
            if(!$path) {
                $valid = false;
            } else {
                foreach($this->_basePath as $part) {
                    if($part != $path->shift()) {
                        $valid = false;
                        break;
                    }
                }
            }
        }

        if($valid) {
            $redirectPath = (string)$path;
        }

        if($url->getDomain() != $this->_baseDomain) {
            $valid = false;
        }
        
        if(!$valid) {
            $baseUrl = (string)$this->requestToUrl(new arch\Request($redirectPath));

            if($this->isDevelopment()) {        
                $response = new halo\protocol\http\response\String(
                    '<html><head><title>Bad request</title></head><body>'.
                    '<p>Sorry, you are not in the right place!</p>'.
                    '<p>Go here instead: <a href="'.$baseUrl.'">'.$baseUrl.'</a></p>',
                    'text/html'
                );
                
                $response->getHeaders()->setStatusCode(404);
                return $response;
            } else {
                $response = new halo\protocol\http\response\Redirect($baseUrl);
                $response->isPermanent(true);
                return $response;
            }
        }
        
        
        // Build init request
        $request = new arch\Request();
        
        if($path) {
            if(preg_match('/^\~[a-zA-Z0-9_]+$/i', $path)) {
                $orig = (string)$url;
                $url->getPath()->shouldAddTrailingSlash(true);

                if((string)$url != $orig) {
                    $response = new halo\protocol\http\response\Redirect($url);
                    $response->isPermanent(true);
                    return $response;
                }
            }

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
                    core\debug()->error('CALAMITY: We appear to have caused an error in trying to render the error pages!');
                    throw $e;
                }
                
                while(ob_get_level()) {
                    ob_end_clean();
                }
                
                $previousError = $e;
                $response = null;
                $request = null;
                
                try {
                    if($this->_context) {
                        $request = clone $this->_context->request;
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
        // Ensure a debug context
        if($this->isDevelopment()) {
            core\debug();
        }

        if($this->_responseAugmentor) {
            $this->_responseAugmentor->resetCurrent();
        }
        
        $this->removeRegistryObject('breadcrumbs');

        $this->_context = null;
        $this->_context = arch\Context::factory($this, clone $request);
        
        $action = arch\Action::factory($this->_context);
        $response = $action->dispatch();
        
        // Dereference proxy responses
        while($response instanceof arch\IProxyResponse) {
            $response = $response->toResponse();
        }

        
        // Forwarding
        if($response instanceof arch\IRequest) {
            return $response;
        }
        
        
        // Empty response
        if($response === null && $this->isDevelopment()) {
            $this->_context->throwError(
                500,
                'No response was returned by action: '.$this->_context->request
            );
        }
        
        
        // Basic response
        if(!$response instanceof halo\protocol\http\IResponse) {
            $response = (string)$response;
            
            if($this->_responseAugmentor) {
                $response = new halo\protocol\http\response\String(
                    $response, 
                    core\io\Type::extToMime($this->_context->request->getType())
                );
            } else {
                return $response;
            }
        }
        
        
        // Permissions
        /*
        if($response instanceof arch\response\IPermissionEnforcer) {
            $user = $this->_context->getUserManager();
         
            foreach($response->getAccessLocks() as $lock) {
                if(!$user->canAccess($lock)) {
                    $this->_context->throwError(
                        401, 'Insufficient permissions'
                    );
                }
            }
        }
        */
       
        $response->onDispatchComplete();

        return $response;
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

        // Apply globally defined cookies, headers, etc
        if($this->_responseAugmentor) {
            $this->_responseAugmentor->apply($response);
        }

        // Make sure cookies are in headers
        if($response->hasCookies()) {
            $response->getCookies()->applyTo($response->getHeaders());
        }

        // Only send data if needed
        $isFile = $response instanceof halo\protocol\http\IFileResponse;
        $sendData = true;
        
        if($this->_httpRequest->isCachedByClient()) {
            $headers = $response->getHeaders();
            
            if($headers->isCached($this->_httpRequest)) {
                $headers->setStatusCode(304);
                $sendData = false;
            }
        }
        
        // Redirect to x-sendfile header
        if($this->_sendFileHeader && $isFile && $sendData && $response->isStaticFile()) {
            $response->getHeaders()->set($this->_sendFileHeader, $response->getStaticFilePath());
            $sendData = false;
        }
        
        // Send headers
        if($response->hasHeaders()) {
            $response->getHeaders()->send();
        }
        
        // Send data
        if($sendData) {
            while(ob_get_level()) {
                ob_end_clean();
            }
            
            set_time_limit(0);
            
            // TODO: Seek to resume header location if requested
            
            if($isFile) {
                $file = $response->getContentFileStream();
            
                while(!$file->eof()) {
                    echo $file->readChunk(8192);
                }
                
                $file->close();
            } else {
                echo $response->getContent();
            }
        }
    }


// Debug
    public function createDebugContext() {
        $output = new core\debug\Context();

        if($this->isTesting()) {
            if($this->_httpRequest) {
                $headers = $this->_httpRequest->getHeaders();
            } else {
                $headers = halo\protocol\http\request\HeaderCollection::fromEnvironment();
            }

            if(core\log\writer\FirePhp::isAvailable($headers)) {
                $output->addWriter(new core\log\writer\FirePhp());
            } 
            /*else if(core\log\writer\ChromePhp::isAvailable($headers)) {
                $output->addWriter(new core\log\writer\ChromePhp());
            }*/
        }

        return $output;
    }

    public function renderDebugContext(core\debug\IContext $context) {
        $renderer = new core\debug\renderer\Html($context);
        
        if(!headers_sent()) {
            if(strncasecmp(PHP_SAPI, 'cgi', 3)) {
                header('HTTP/1.1 501');
            } else {
                header('Status: 501');
            }

            header('Content-Type: text/html; charset=UTF-8');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            
            try {
                if($this->_responseAugmentor) {
                    $cookies = $this->_responseAugmentor->getCookieCollectionForCurrentRequest();
                    
                    foreach($cookies->toArray() as $cookie) {
                        header('Set-Cookie: '.$cookie->toString());
                    }
                    
                    foreach($cookies->getRemoved() as $cookie) {
                        header('Set-Cookie: '.$cookie->toInvalidateString());
                    }
                }
            } catch(\Exception $e) {}
        }
        
        echo $renderer->render();
        return $this;
    }
}

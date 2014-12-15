<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\application;

use df;
use df\core;
use df\link;
use df\flow;
use df\arch;
use df\halo;

class Http extends Base implements arch\IDirectoryRequestApplication, link\http\IResponseAugmentorProvider {
    
    const RUN_MODE = 'Http';
    
    protected $_httpRequest;
    protected $_responseAugmentor;
    protected $_sendFileHeader;
    protected $_manualChunk = false;
    protected $_credentials = null;

    protected $_context;
    protected $_router;
    
    protected function __construct() {
        parent::__construct();
        
        $config = core\application\http\Config::getInstance();
        $this->_sendFileHeader = $config->getSendFileHeader();
        $this->_manualChunk = $config->shouldChunkManually();
        $this->_credentials = $config->getCredentials();

        $this->_router = core\application\http\Router::getInstance();
    }
    
    
// Router
    public function getRouter() {
        return $this->_router;
    }

// Http request
    public function setHttpRequest(link\http\IRequest $request) {
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
            $this->_responseAugmentor = new link\http\response\Augmentor();
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
    
    public function getDefaultDirectoryAccess() {
        return arch\IAccess::NONE;
    }
    
    
// Execute
    public function dispatch() {
        $this->_beginDispatch();

        if($response = $this->_prepareHttpRequest()) {
            return $response;
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
        
        if(!$this->_router->mapPath($path)) {
            $valid = false;
        }

        if($valid) {
            $redirectPath = (string)$path;
        }

        $domain = $url->getDomain();
        
        if(!$this->_router->mapDomain($domain)) {
            if(!$this->isDevelopment()) {
                $valid = false;
            }
        }
        
        if(!$valid) {
            $baseUrl = (string)$this->_router->requestToUrl(new arch\Request($redirectPath));

            if($this->isDevelopment()) {        
                $response = new link\http\response\String(
                    '<html><head><title>Bad request</title></head><body>'.
                    '<p>Sorry, you are not in the right place!</p>'.
                    '<p>Go here instead: <a href="'.$baseUrl.'">'.$baseUrl.'</a></p>',
                    'text/html'
                );
                
                $response->getHeaders()->setStatusCode(404);
                return $response;
            } else {
                $response = new link\http\response\Redirect($baseUrl);
                //$response->isPermanent(true);
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
                    $response = new link\http\response\Redirect($url);
                    $response->isPermanent(true);
                    return $response;
                }
            }

            $request->setPath($path);
        }

        $this->_router->mapArea($request);
            
        if($url->hasQuery()) {
            $request->setQuery(clone $url->getQuery());
        }
        
        $request->setFragment($url->getFragment());

        if($this->_httpRequest->getHeaders()->has('x-ajax-request-type')) {
            $request->setType($this->_httpRequest->getHeaders()->get('x-ajax-request-type'));
        }

        $request = $this->_router->routeIn($request);
        
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
    
    protected function _prepareHttpRequest() {
        $this->_enforceCredentials();
        $this->_httpRequest = new link\http\request\Base(null, true);

        if($response = $this->_checkIpRanges($this->_httpRequest->getIp())) {
            return $response;
        }

        if($this->_router->shouldUseHttps() && !$this->_httpRequest->getUrl()->isSecure() && $this->isProduction()) {
            $response = new link\http\response\Redirect(
                $this->_httpRequest->getUrl()
                    ->isSecure(true)
                    ->setPort(null)
            );

            $response->isPermanent(true);
            return $response;
        }

        if(!$this->_router->hasBasePort()) {
            $this->_router->setBasePort($this->_httpRequest->getUrl()->getPort());
        }

        if($this->_httpRequest->hasCookie('debug')) {
            df\Launchpad::$isTesting = true;
            df\Launchpad::$debug = $this->createDebugContext();

            flow\Manager::getInstance()->flashNow(
                    'global.debug', 
                    'Currently in enforced debug mode', 
                    'debug'
                )
                ->setLink('~devtools/application/debug-mode', 'Change debug settings');
        }

        return null;
    }

    protected function _enforceCredentials() {
        if(!$this->_credentials) {
            return true;
        }

        if(!isset($_SERVER['PHP_AUTH_USER'])
        || $_SERVER['PHP_AUTH_USER'] != $this->_credentials['username']
        || $_SERVER['PHP_AUTH_PW'] != $this->_credentials['password']) {
            header('WWW-Authenticate: Basic realm="Developer Site"');
            header('HTTP/1.0 401 Unauthorized');
            echo 'You need to authenticate to view this site';
            return true;
        }

        return false;
    }

    protected function _checkIpRanges(link\IIp $ip) {
        $config = core\application\http\Config::getInstance();
        $ranges = $config->getIpRanges();

        if(empty($ranges)) {
            return;
        }

        $augmentor = $this->getResponseAugmentor();
        $augmentor->setHeaderForAnyRequest('x-allow-ip', (string)$ip);

        foreach($ranges as $range) {
            if($range->check($ip)) {
                $augmentor->setHeaderForAnyRequest('x-allow-ip-range', (string)$range);
                return;
            }
        }

        if($ip->isLoopback()) {
            $augmentor->setHeaderForAnyRequest('x-allow-ip-range', 'loopback');
            return;
        }

        $response = new link\http\response\String(
            '<html><head><title>Forbidden</title></head><body>'.
            '<p>Sorry, this site is protected by IP range.</p><p>Your IP is: <strong>'.$ip.'</strong></p>',
            'text/html'
        );
        
        $response->getHeaders()->setStatusCode(403);
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
        $this->_context = arch\Context::factory(clone $request);
        
        $action = arch\Action::factory(
            $this->_context,
            arch\Controller::factory($this->_context)
        );

        if(!$action->shouldOptimize()) {
            $this->_doTheDirtyWork();
        }
        
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
        if(!$response instanceof link\http\IResponse) {
            $response = new link\http\response\String(
                (string)$response, 
                core\io\Type::extToMime(strtolower($this->_context->request->getType()))
            );

            //$response->getHeaders()->setCacheExpiration(60);
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
    


    protected function _doTheDirtyWork() {
        halo\daemon\Manager::getInstance()->ensureActivity();
    }


    // Payload
    public function launchPayload($response) {
        // Make sure we're actually in HTTP!
        if(!isset($_SERVER['HTTP_HOST'])) {
            throw new core\RuntimeException(
                'Cannot run http app - this php process does not appear to be running from a http connection'
            );
        }
        
        
        if(!$response instanceof link\http\IResponse) {
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
        $isFile = $response instanceof link\http\IFileResponse;
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
            
            flush();
            ob_implicit_flush(true);

            set_time_limit(0);
            $channel = new core\io\channel\Stream('php://output');
            
            // TODO: Seek to resume header location if requested
            
            if($isFile) {
                $file = $response->getContentFileStream();
            
                while(!$file->eof()) {
                    //echo $file->readChunk(8192);
                    $channel->write($file->readChunk(8192));
                }
                
                $file->close();
            } else if($response instanceof link\http\IGeneratorResponse) {
                $response->shouldChunkManually($this->_manualChunk);
                $response->generate($channel);
            } else {
                //echo $response->getContent();
                $channel->write($response->getContent());
            }

            $channel->close();
        }
    }


// Debug
    public function createDebugContext() {
        $output = new core\debug\Context();

        if($this->isTesting()) {
            if($this->_httpRequest) {
                $headers = $this->_httpRequest->getHeaders();
            } else {
                $headers = link\http\request\HeaderCollection::fromEnvironment();
            }

            /*
            if(core\log\writer\FirePhp::isAvailable($headers)) {
                $output->addWriter(new core\log\writer\FirePhp());
            } else if(core\log\writer\ChromePhp::isAvailable($headers)) {
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
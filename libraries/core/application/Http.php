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

class Http extends Base implements core\IContextAware, link\http\IResponseAugmentorProvider, arch\IRequestOrientedApplication {

    const RUN_MODE = 'Http';

    private static $_init = false;

    protected $_httpRequest;
    protected $_baseUrl;

    protected $_responseAugmentor;
    protected $_sendFileHeader;
    protected $_manualChunk = false;
    protected $_credentials = null;
    protected $_dispatchRequest;

    protected $_context;
    protected $_router;

    public function __construct() {
        if(!self::$_init) {
            // If you're on apache, it sometimes hides some env variables = v. annoying
            if(function_exists('apache_request_headers')) {
                foreach(apache_request_headers() as $key => $value) {
                    $_SERVER['HTTP_'.strtoupper(str_replace('-', '_', $key))] = $value;
                }
            }

            if(isset($_SERVER['CONTENT_TYPE'])) {
                $_SERVER['HTTP_CONTENT_TYPE'] = $_SERVER['CONTENT_TYPE'];
            }

            // Normalize REQUEST_URI
            if(isset($_SERVER['HTTP_X_ORIGINAL_URL'])) {
                $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_ORIGINAL_URL'];
            }

            self::$_init = true;
        }

        df\Launchpad::$application = $this;

        $config = core\application\http\Config::getInstance();
        $this->_sendFileHeader = $config->getSendFileHeader();
        $this->_manualChunk = $config->shouldChunkManually();
        $this->_credentials = $config->getCredentials();

        $this->_httpRequest = new link\http\request\Base(null, true);
        $this->_router = core\application\http\Router::getInstance();
    }


// Router
    public function getRouter() {
        return $this->_router;
    }

// Http request
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
            $this->_responseAugmentor = new link\http\response\Augmentor(
                $this->_router->getBaseUrl()
            );
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
        return $this->_dispatchRequest;
    }


// Dispatch
    public function dispatch() {
        try {
            $ip = $this->_httpRequest->getIp();

            $this->_prepareHttpRequest();
            $this->_handleDebugMode();

            $request = $this->_prepareDirectoryRequest();

            $this->_enforceCredentials($ip);
            $this->_checkIpRanges($ip, $request);

            $response = $this->_dispatchRequest($request);
        } catch(arch\IForcedResponse $e) {
            $response = $this->_normalizeResponse($e->getResponse());
        }

        if(df\Launchpad::$debug) {
            df\Launchpad::$debug->execute();
        }

        $this->_sendResponse($response);
    }


// Credentials
    protected function _enforceCredentials(link\IIp $ip) {
        if(!$this->_credentials || $ip->isLoopback()) {
            return true;
        }

        if(!isset($_SERVER['PHP_AUTH_USER'])
        || $_SERVER['PHP_AUTH_USER'] != $this->_credentials['username']
        || $_SERVER['PHP_AUTH_PW'] != $this->_credentials['password']) {
            header('WWW-Authenticate: Basic realm="Developer Site"');
            header('HTTP/1.0 401 Unauthorized');
            echo 'You need to authenticate to view this site';
            exit;
        }

        return false;
    }


// IP check
    protected function _checkIpRanges(link\IIp $ip, arch\IRequest $request=null) {
        $config = core\application\http\Config::getInstance();

        if($request) {
            $ranges = $config->getIpRangesForArea($request->getArea());
        } else {
            $ranges = $config->getIpRanges();
        }

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

        $current = link\Ip::factory(gethostbyname(gethostname()));

        if($current->toString() == $ip->toString()) {
            $augmentor->setHeaderForAnyRequest('x-allow-ip-range', 'loopback');
            return;
        }

        if($request && isset($request['authenticate']) && !isset($_COOKIE['ipbypass'])) {
            setcookie('ipbypass', 1);
        }

        if($request && (isset($request['authenticate']) || isset($_SERVER['PHP_AUTH_USER']) || isset($_COOKIE['ipbypass']))) {
            $context = new arch\Context($request);
            static $salt = '3efcf3200384a9968a58841812d78f94d88a61b2e0cc57849a19707e0ebed065';
            static $username = 'e793f732b58b8c11ae4048214f9171392a864861d35c0881b3993d12001a78b0';
            static $password = '016ede424aa10ae5895c21c33d200c7b08aa33d961c05c08bfcf946cb7c53619';

            if(isset($_SERVER['PHP_AUTH_USER'])
            && $context->data->hexHash($_SERVER['PHP_AUTH_USER'], $salt) == $username
            && $context->data->hexHash($_SERVER['PHP_AUTH_PW'], $salt) == $password) {
                return;
            } else {
                header('WWW-Authenticate: Basic realm="Private Site"');
                header('HTTP/1.0 401 Unauthorized');
                echo 'You need to authenticate to view this site';
                exit;
            }
        }

        $url = clone $this->_httpRequest->url;
        $url->query->authenticate = null;

        $response = new link\http\response\Stream(
            '<html><head><title>Forbidden</title></head><body>'.
            '<p>Sorry, this site is protected by IP range.</p><p>Your IP is: <strong>'.$ip.'</strong></p>'.
            '<p><a href="'.$url.'">Developer access</a></p>',
            'text/html'
        );

        $response->getHeaders()->setStatusCode(403);
        throw new arch\ForcedResponse($response);
    }


// Prepare http request
    protected function _prepareHttpRequest() {
        if($this->_router->shouldUseHttps() && !$this->_httpRequest->getUrl()->isSecure() && $this->isProduction()) {
            $response = new link\http\response\Redirect(
                $this->_httpRequest->getUrl()
                    ->isSecure(true)
                    ->setPort(null)
            );

            $response->isPermanent(true);
            throw new arch\ForcedResponse($response);
        }
    }


// Debug mode
    protected function _handleDebugMode() {
        if($this->_httpRequest->hasCookie('debug')) {
            df\Launchpad::$environmentMode = 'testing';

            flow\Manager::getInstance()->flashNow(
                    'global.debug',
                    'Currently in enforced debug mode',
                    'debug'
                )
                ->setLink('~devtools/application/debug-mode', 'Change debug settings');
        }
    }


// Directory request
    protected function _prepareDirectoryRequest() {
        $pathValid = $valid = true;
        $redirectPath = '/';

        $url = $this->_httpRequest->getUrl();
        $path = clone $url->getPath();

        if(!$map = $this->_router->lookupDomain($url->getDomain())) {
            $valid = false;
        } else {
            $this->_router->setBase($map);
        }

        if($map && !$map->mapPath($path)) {
            $pathValid = $valid = false;
        }

        if($pathValid && !$path->isEmpty()) {
            $redirectPath = (string)$path;
        }

        if(!$valid) {
            $baseUrl = $this->_router->requestToUrl(new arch\Request($redirectPath));
            $baseUrl->setQuery($url->getQuery());
            $baseUrl = (string)$baseUrl;

            if($this->isDevelopment()) {
                $response = new link\http\response\Stream(
                    '<html><head><title>Bad request</title></head><body>'.
                    '<p>Sorry, you are not in the right place!</p>'.
                    '<p>Go here instead: <a href="'.$baseUrl.'">'.$baseUrl.'</a></p>',
                    'text/html'
                );

                $response->getHeaders()->setStatusCode(404);
            } else {
                $response = new link\http\response\Redirect($baseUrl);
            }

            throw new arch\ForcedResponse($response);
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
                    throw new arch\ForcedResponse($response);
                }
            }

            $request->setPath($path);
        }

        $map->mapArea($request);

        if($url->hasQuery()) {
            $request->setQuery(clone $url->getQuery());
        }

        if($map->mappedKey) {
            $request->query->{$map->area} = $map->mappedKey;
        }

        $request->setFragment($url->getFragment());

        if($this->_httpRequest->getHeaders()->has('x-ajax-request-type')) {
            $request->setType($this->_httpRequest->getHeaders()->get('x-ajax-request-type'));
        }

        $request = $this->_router->routeIn($request);

        if(df\Launchpad::$isMaintenance
        && !$request->matches('assets/')
        && !$request->matches('theme/')) {
            return new arch\Request('site-maintenance');
        }

        return $request;
    }


// Dispatch request
    protected function _dispatchRequest(arch\IRequest $request) {
        $this->_dispatchRequest = clone $request;

        try {
            $response = $this->_dispatchNode($request);
            $response = $this->_normalizeResponse($response);
        } catch(\Throwable $e) {
            while(ob_get_level()) {
                ob_end_clean();
            }

            if($e instanceof arch\IForcedResponse) {
                $response = $this->_normalizeResponse($e->getResponse());
            } else {
                $this->_dispatchException = $e;

                try {
                    $response = $this->_dispatchNode(new arch\Request('error/'));
                    $response = $this->_normalizeResponse($response);
                } catch(\Throwable $f) {
                    throw $e;
                }
            }
        }

        return $response;
    }


// Dispatch node
    protected function _dispatchNode(arch\IRequest $request) {
        if($this->_responseAugmentor) {
            $this->_responseAugmentor->resetCurrent();
        }

        $this->_context = null;
        $this->_context = arch\Context::factory(clone $request);

        try {
            $node = arch\node\Base::factory(
                $this->_context,
                arch\Controller::factory($this->_context)
            );
        } catch(arch\RuntimeException $e) {
            // See if the url just needs a /
            $url = $this->_httpRequest->getUrl();
            $testUrl = null;

            if(!$url->path->shouldAddTrailingSlash() && $url->path->getFilename() != 'index') {
                $testUrl = clone $url;
                $testUrl->path->shouldAddTrailingSlash(true);
            } else if($url->path->shouldAddTrailingSlash()) {
                $testUrl = clone $url;
                $testUrl->path->shouldAddTrailingSlash(false);
            }

            if($testUrl) {
                $context = clone $this->_context;
                $context->location = $context->request = $this->_router->urlToRequest($testUrl);

                if($context->apex->nodeExists($context->request)) {
                    return $context->http->redirect($context->request)->isPermanent(true);
                }
            }

            throw $e;
        }

        foreach($this->_registry as $object) {
            if($object instanceof core\IDispatchAware) {
                $object->onApplicationDispatch($node);
            }
        }

        if(!$node->shouldOptimize()) {
            $this->_doTheDirtyWork();
        }

        return $node->dispatch();
    }


// Normalize response
    protected function _normalizeResponse($response) {
        // Callback
        if($response instanceof \Closure
        || $response instanceof core\lang\ICallback) {
            $response = $response();
        }

        // Dereference proxy responses
        while($response instanceof arch\IProxyResponse) {
            $response = $response->toResponse();
        }

        // Forwarding
        if($response instanceof arch\IRequest) {
            $response = $this->_context->http->redirect($response);
        }

        // Empty response
        if($response === null && $this->isDevelopment()) {
            $this->_context->throwError(
                500,
                'No response was returned by node: '.$this->_context->request
            );
        }

        // Basic response
        if(!$response instanceof link\http\IResponse) {
            $response = new link\http\response\Stream(
                (string)$response,
                core\fs\Type::extToMime(strtolower($this->_context->request->getType()))
            );

            //$response->getHeaders()->setCacheExpiration(60);
        }

        $response->onDispatchComplete();

        if($this->_context && $this->_context->http->isAjaxRequest()) {
            $response->getHeaders()->set('x-response-url', $this->_httpRequest->url);
        }

        return $response;
    }



    protected function _doTheDirtyWork() {
        halo\daemon\Manager::getInstance()->ensureActivity();
    }


// Send response
    protected function _sendResponse(link\http\IResponse $response) {
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


        // HEAD request
        if($this->_httpRequest->getMethod() == 'head') {
            $sendData = false;
        }


        if(!$sendData) {
            // Send headers
            if($response->hasHeaders()) {
                $response->getHeaders()->send();
            }
        } else {
            // Send data with triggered headers
            $channel = new core\io\Stream('php://output');
            set_time_limit(0);

            $channel->setWriteCallback(function() use($response) {
                while(ob_get_level()) {
                    ob_end_clean();
                }

                flush();
                ob_implicit_flush(true);

                if($response->hasHeaders()) {
                    $response->getHeaders()->send();
                }
            });

            // TODO: Seek to resume header location if requested

            if($isFile) {
                $file = $response->getContentFileStream();

                while(!$file->eof()) {
                    $channel->write($file->readChunk(8192));
                }

                $file->close();
            } else if($response instanceof link\http\IGeneratorResponse) {
                $response->shouldChunkManually($this->_manualChunk);
                $response->generate($channel);
            } else {
                $channel->write($response->getContent());
            }

            $channel->close();
        }
    }


// Debug
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

            if($this->_httpRequest && $this->_httpRequest->method == 'head') {
                header('X-Dump: '.json_encode($context->toString()));
            }


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
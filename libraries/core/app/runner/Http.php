<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\app\runner;

use df;
use df\core;
use df\link;
use df\flow;
use df\arch;
use df\halo;

use DecodeLabs\Compass\Ip;
use DecodeLabs\Deliverance;
use DecodeLabs\Exceptional;
use DecodeLabs\Genesis;
use DecodeLabs\Glitch;
use DecodeLabs\Typify;
use DecodeLabs\R7\Legacy;

class Http extends Base implements core\IContextAware, link\http\IResponseAugmentorProvider, arch\IRequestOrientedRunner
{
    private static $_init = false;

    protected $_httpRequest;
    protected $_baseUrl;

    protected $_responseAugmentor;
    protected $_sendFileHeader;
    protected $_credentials = null;
    protected $_dispatchRequest;

    protected $_context;
    protected $_router;

    public function __construct()
    {
        if (!self::$_init) {
            // If you're on apache, it sometimes hides some env variables = v. annoying
            if (function_exists('apache_request_headers') && false !== ($apache = apache_request_headers())) {
                foreach ($apache as $key => $value) {
                    $_SERVER['HTTP_'.strtoupper(str_replace('-', '_', $key))] = $value;
                }
            }

            if (isset($_SERVER['CONTENT_TYPE'])) {
                $_SERVER['HTTP_CONTENT_TYPE'] = $_SERVER['CONTENT_TYPE'];
            }

            // Normalize REQUEST_URI
            if (isset($_SERVER['HTTP_X_ORIGINAL_URL'])) {
                $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_ORIGINAL_URL'];
            }


            // Normalize Cloudflare proxy
            if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
                $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
            }

            self::$_init = true;
        }

        $config = namespace\http\Config::getInstance();
        $this->_sendFileHeader = $config->getSendFileHeader();

        if (isset($_SERVER['HTTP_X_SENDFILE_TYPE'])) {
            if ($_SERVER['HTTP_X_SENDFILE_TYPE'] === 'X-Accel-Redirect') {
                $this->_sendFileHeader = null;
            } else {
                $this->_sendFileHeader = $_SERVER['HTTP_X_SENDFILE_TYPE'];
            }
        }

        $this->_credentials = $config->getCredentials();

        $this->_httpRequest = new link\http\request\Base(null, true);
        $this->_router = namespace\http\Router::getInstance();

        Glitch::setHeaderBufferSender([$this, 'sendGlitchDebugHeaders']);
    }


    // Router
    public function getRouter()
    {
        return $this->_router;
    }

    // Http request
    public function getHttpRequest()
    {
        if (!$this->_httpRequest) {
            throw Exceptional::Logic(
                'The http request is not available until the application has been dispatched'
            );
        }

        return $this->_httpRequest;
    }



    // Response augmentor
    public function getResponseAugmentor()
    {
        if (!$this->_responseAugmentor) {
            $this->_responseAugmentor = new link\http\response\Augmentor(
                $this->_router
            );
        }

        return $this->_responseAugmentor;
    }


    // Context
    public function getContext()
    {
        if (!$this->_context) {
            throw Exceptional::NoContext(
                'A context is not available until the application has been dispatched'
            );
        }

        return $this->_context;
    }

    public function hasContext()
    {
        return $this->_context !== null;
    }

    public function getDispatchRequest(): ?arch\IRequest
    {
        return $this->_dispatchRequest;
    }


    // Dispatch
    public function dispatch(): void
    {
        try {
            $ip = $this->_httpRequest->getIp();
            $request = $this->_prepareDirectoryRequest();
            $this->_prepareHttpRequest();

            $this->_enforceCredentials($ip);
            $this->_checkIpRanges($ip, $request);

            $response = $this->_dispatchRequest($request);
        } catch (arch\IForcedResponse $e) {
            $response = $this->_normalizeResponse($e->getResponse());
        }

        $this->_sendResponse($response);
    }


    // Credentials
    protected function _enforceCredentials(Ip $ip): bool
    {
        // Check for credentials or loopback
        if (!$this->_credentials || $ip->isLoopback()) {
            return true;
        }

        // Check for passthrough header
        if ($this->_httpRequest->headers->has('x-df-self')) {
            $key = $this->_httpRequest->headers->get('x-df-self');

            if ($key === md5(Legacy::getPassKey())) {
                return true;
            }
        }

        if (!isset($_SERVER['PHP_AUTH_USER'])
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
    protected function _checkIpRanges(
        Ip $ip,
        arch\IRequest $request=null
    ) {
        // Get ranges from config
        $config = namespace\http\Config::getInstance();

        if ($request) {
            $ranges = $config->getIpRangesForArea($request->getArea());
        } else {
            $ranges = $config->getIpRanges();
        }


        if (empty($ranges)) {
            return;
        }


        // Check for passthrough header
        if ($this->_httpRequest->headers->has('x-df-self')) {
            $key = $this->_httpRequest->headers->get('x-df-self');

            if ($key === md5(Legacy::getPassKey())) {
                return;
            }
        }


        // Apply
        $augmentor = $this->getResponseAugmentor();
        $augmentor->setHeaderForAnyRequest('x-allow-ip', (string)$ip);

        foreach ($ranges as $range) {
            if ($range->contains($ip)) {
                $augmentor->setHeaderForAnyRequest('x-allow-ip-range', (string)$range);
                return;
            }
        }


        // Loopback check
        if ($ip->isLoopback()) {
            $augmentor->setHeaderForAnyRequest('x-allow-ip-range', 'loopback');
            return;
        }


        // Resolved IP check
        $current = Ip::parse(
            gethostbyname((string)gethostname())
        );

        if ($current->matches($ip)) {
            $augmentor->setHeaderForAnyRequest('x-allow-ip-range', 'loopback');
            return;
        }


        // Apply request
        if ($request) {
            // Test for passthrough requests
            if ($request->matches('.well-known/pki-validation/')) {
                return;
            }

            // Authenticate
            if (
                isset($request['authenticate']) &&
                !isset($_COOKIE['ipbypass'])
            ) {
                setcookie('ipbypass', '1');
            }

            if (
                isset($request['authenticate']) ||
                isset($_SERVER['PHP_AUTH_USER']) ||
                isset($_COOKIE['ipbypass'])
            ) {
                $context = new arch\Context($request);
                static $salt = '3efcf3200384a9968a58841812d78f94d88a61b2e0cc57849a19707e0ebed065';
                static $username = 'e793f732b58b8c11ae4048214f9171392a864861d35c0881b3993d12001a78b0';
                static $password = '016ede424aa10ae5895c21c33d200c7b08aa33d961c05c08bfcf946cb7c53619';

                if (isset($_SERVER['PHP_AUTH_USER'])
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
    protected function _prepareHttpRequest()
    {
        if (
            $this->_router->shouldUseHttps() &&
            !$this->_httpRequest->getUrl()->isSecure() &&
            Genesis::$environment->isProduction()
        ) {
            $response = new link\http\response\Redirect(
                $this->_httpRequest->getUrl()
                    ->isSecure(true)
                    ->setPort(null)
            );

            $response->isPermanent(true);
            throw new arch\ForcedResponse($response);
        }

        if ($this->_httpRequest->getMethod() == 'options') {
            throw new arch\ForcedResponse(
                (new link\http\response\Stream('content'))->withHeaders(function ($headers) {
                    $headers->set('allow', 'OPTIONS, GET, HEAD, POST');
                })
            );
        }
    }




    // Directory request
    protected function _prepareDirectoryRequest()
    {
        $pathValid = $valid = true;
        $redirectPath = '/';

        $url = $this->_httpRequest->getUrl();
        $path = clone $url->getPath();

        if (!$map = $this->_router->lookupDomain($url->getDomain())) {
            $tempMap = $this->_router->getRootMap();

            if (!$tempMap->mapPath($path)) {
                $pathValid = false;
            }

            $valid = false;
        } else {
            if ($map->mappedDomain !== null && $map->mappedDomain !== $url->getDomain()) {
                $valid = false;
            }

            $this->_router->setBase($map);

            if (!$map->mapPath($path)) {
                $pathValid = $valid = false;
            }

            if ($pathValid && $valid && $map->area === '*') {
                $area = $path->get(0);

                if (substr($area, 0, 1) != '~') {
                    $area = 'front';
                } else {
                    $area = substr($area, 1);
                }

                $mapOut = $this->_router->getMapOut($area);

                if ($mapOut && $mapOut->area !== $map->area) {
                    $valid = false;
                }
            }
        }

        if ($pathValid && !$path->isEmpty()) {
            $redirectPath = (string)$path;
        }

        if (!$valid) {
            $redirectRequest = (new arch\Request($redirectPath))
                ->setQuery($url->getQuery());

            if ($map && $map->area !== '*') {
                $redirectRequest->setArea($map->area);
                $redirectRequest->query->{$map->area} = $map->mappedKey;
            }

            $baseUrl = $this->_router->requestToUrl($redirectRequest);

            if ($this->_router->shouldUseHttps()) {
                $baseUrl->isSecure(true);
            }

            $baseUrl = (string)$baseUrl;

            if (Genesis::$environment->isDevelopment()) {
                $response = new link\http\response\Stream(
                    '<html><head><title>Bad request</title></head><body>'.
                    '<p>Sorry, you are not in the right place!</p>'.
                    '<p>Go here instead: <a href="'.$baseUrl.'">'.$baseUrl.'</a></p>',
                    'text/html'
                );

                $response->getHeaders()->setStatusCode(404);
            } else {
                $response = new link\http\response\Redirect($baseUrl);
                $response->isPermanent(true);
            }

            throw new arch\ForcedResponse($response);
        }


        // Build init request
        $request = new arch\Request();

        if ($path) {
            if (preg_match('/^\~[a-zA-Z0-9_]+$/i', $path)) {
                $orig = (string)$url;
                $url->getPath()->shouldAddTrailingSlash(true);

                if ((string)$url != $orig) {
                    $response = new link\http\response\Redirect($url);
                    //$response->isPermanent(true);
                    throw new arch\ForcedResponse($response);
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

        if ($this->_httpRequest->getHeaders()->has('x-ajax-request-type')) {
            $request->setType($this->_httpRequest->getHeaders()->get('x-ajax-request-type'));
        }

        $request = $this->_router->routeIn($request);
        return $request;
    }


    // Dispatch request
    protected function _dispatchRequest(arch\IRequest $request)
    {
        $this->_dispatchRequest = clone $request;

        try {
            $response = $this->_dispatchNode($request);
            $response = $this->_normalizeResponse($response);
        } catch (\Throwable $e) {
            while (ob_get_level()) {
                ob_end_clean();
            }

            if ($e instanceof arch\IForcedResponse) {
                $response = $this->_normalizeResponse($e->getResponse());
            } else {
                $this->_dispatchException = $e;

                try {
                    $response = $this->_dispatchNode(new arch\Request('error/'));
                    $response = $this->_normalizeResponse($response);
                } catch (\Throwable $f) {
                    if ($f instanceof arch\IForcedResponse) {
                        $response = $this->_normalizeResponse($f->getResponse());
                    } else {
                        core\logException($f);
                        throw $e;
                    }
                }
            }
        }

        return $response;
    }


    // Dispatch node
    protected function _dispatchNode(arch\IRequest $request)
    {
        if ($this->_responseAugmentor) {
            $this->_responseAugmentor->resetCurrent();
        }

        $this->_context = null;
        $this->_context = arch\Context::factory(clone $request);

        try {
            $node = arch\node\Base::factory($this->_context);
        } catch (arch\node\NotFoundException $e) {
            // See if the url just needs a /
            $url = $this->_httpRequest->getUrl();
            $testUrl = null;

            if (!$url->path->shouldAddTrailingSlash() && $url->path->getFilename() != 'index') {
                $testUrl = clone $url;
                $testUrl->path->shouldAddTrailingSlash(true);
            } elseif ($url->path->shouldAddTrailingSlash()) {
                $testUrl = clone $url;
                $testUrl->path->shouldAddTrailingSlash(false);
            }

            if ($testUrl) {
                $context = clone $this->_context;
                $context->location = $context->request = $this->_router->urlToRequest($testUrl);

                if ($context->apex->nodeExists($context->request)) {
                    return $context->http->redirect($context->request)
                        //->isPermanent(true)
                        ;
                }
            }

            throw $e;
        }

        foreach (Legacy::getRegistryObjects() as $object) {
            if ($object instanceof core\IDispatchAware) {
                $object->onAppDispatch($node);
            }
        }

        if (!$node->shouldOptimize()) {
            $this->_doTheDirtyWork();
        }

        return $node->dispatch();
    }


    // Normalize response
    protected function _normalizeResponse($response)
    {
        // Callback
        if (
            is_callable($response) &&
            (
                $response instanceof \Closure ||
                $response instanceof core\lang\ICallback
            )
        ) {
            $response = $response();
        }

        // Dereference proxy responses
        while ($response instanceof arch\IProxyResponse) {
            $response = $response->toResponse();
        }

        // Forwarding
        if ($response instanceof arch\IRequest) {
            $response = $this->_context->http->redirect($response);
        }

        // Empty response
        if (
            $response === null &&
            Genesis::$environment->isDevelopment()
        ) {
            throw Exceptional::NotImplemented([
                'message' => 'No response was returned by node: '.$this->_context->request,
                'http' => 501
            ]);
        }

        // Basic response
        if (!$response instanceof link\http\IResponse) {
            $response = new link\http\response\Stream(
                (string)$response,
                Typify::detect(strtolower($this->_context->request->getType()))
            );

            //$response->getHeaders()->setCacheExpiration(60);
        }

        $response->onDispatchComplete();
        $headers = $response->getHeaders();

        if ($this->_context && $this->_context->http->isAjaxRequest()) {
            $headers->set('x-response-url', $this->_httpRequest->url);
        }


        // Access control
        if ($this->_httpRequest->headers->has('origin')) {
            $url = new link\http\Url($this->_httpRequest->headers->get('origin'));
            $domain = $url->getDomain();

            if ($this->_router->lookupDomain($domain)) {
                $headers->set('access-control-allow-origin', '*');

                // Include from config
                $headers->set('access-control-allow-headers', 'x-ajax-request-source, x-ajax-request-type');
            }
        }


        // Csp
        $contentType = explode(';', $response->getContentType());
        $contentType = trim(array_shift($contentType));

        if ($csp = Legacy::app()->getCsp($contentType)) {
            $response->getHeaders()->import($csp->exportHeaders());
        }


        return $response;
    }



    protected function _doTheDirtyWork()
    {
        halo\daemon\Manager::getInstance()->ensureActivity();
    }


    // Send response
    protected function _sendResponse(link\http\IResponse $response)
    {
        // Apply globally defined cookies, headers, etc
        if ($this->_responseAugmentor) {
            $this->_responseAugmentor->apply($response);
        }

        // HSTS
        if (
            $this->_router->shouldUseHttps() &&
            Genesis::$environment->isProduction()
        ) {
            $headers = $response->getHeaders();

            if (!$headers->has('Strict-Transport-Security')) {
                $headers->set('Strict-Transport-Security', 'max-age=31536000');
            }
        }

        // Make sure cookies are in headers
        if ($response->hasCookies()) {
            $response->getCookies()->applyTo($response->getHeaders());
        }

        // Only send data if needed
        $sendData = true;

        if ($this->_httpRequest->isCachedByClient()) {
            $headers = $response->getHeaders();

            if ($headers->isCached($this->_httpRequest)) {
                $headers->setStatusCode(304);
                $sendData = false;
            }
        }

        // Redirect to x-sendfile header
        if (
            $this->_sendFileHeader &&
            $response instanceof link\http\IFileResponse &&
            $sendData &&
            $response->isStaticFile()
        ) {
            $response->getHeaders()->set($this->_sendFileHeader, $response->getStaticFilePath());
            $sendData = false;
        }


        // HEAD request
        if ($this->_httpRequest->getMethod() == 'head') {
            $sendData = false;
        }


        if (!$sendData) {
            // Send headers
            if ($response->hasHeaders()) {
                $response->getHeaders()->send();
            }
        } else {
            $stream = Deliverance::openStream('php://output', 'a+');
            set_time_limit(0);

            if ($response instanceof link\http\IGeneratorResponse) {
                // Generator
                $response->setWriteCallback(function ($response) {
                    $response->getHeaders()->send();
                });

                $response->generate($stream);
            } else {
                // Standard
                if ($response->hasHeaders()) {
                    $response->getHeaders()->send();
                }

                if ($response instanceof link\http\IFileResponse) {
                    // File
                    $file = $response->getContentFileStream();

                    while (!$file->isAtEnd()) {
                        $stream->write($file->read(8192));
                    }

                    $file->close();
                } else {
                    // Generic
                    $stream->write($response->getContent());
                }
            }

            $stream->close();
        }
    }


    // Debug
    public function sendGlitchDebugHeaders()
    {
        try {
            if ($this->_responseAugmentor) {
                $cookies = $this->_responseAugmentor->getCookieCollectionForCurrentRequest();

                foreach ($cookies->toArray() as $cookie) {
                    header('Set-Cookie: '.$cookie->toString());
                }

                foreach ($cookies->getRemoved() as $cookie) {
                    header('Set-Cookie: '.$cookie->toInvalidateString());
                }
            }
        } catch (\Throwable $e) {
        }
    }
}

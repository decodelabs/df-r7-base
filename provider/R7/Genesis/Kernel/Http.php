<?php
/**
 * This file is part of the Decode r7 framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Genesis\Kernel;

use Closure;

use DecodeLabs\Compass\Ip;
use DecodeLabs\Deliverance;
use DecodeLabs\Exceptional;
use DecodeLabs\Genesis;
use DecodeLabs\Genesis\Kernel;
use DecodeLabs\Glitch;
use DecodeLabs\R7\Genesis\KernelTrait;
use DecodeLabs\R7\Legacy;
use DecodeLabs\Typify;
use df\arch\Context;
use df\arch\ForcedResponse;
use df\arch\IForcedResponse;
use df\arch\IProxyResponse;
use df\arch\node\Base as NodeBase;
use df\arch\node\NotFoundException as NodeNotFoundException;
use df\arch\Request;
use df\core\app\http\Config as HttpConfig;

use df\core\app\http\Router as HttpRouter;
use df\core\IDispatchAware;
use df\core\lang\ICallback;
use df\halo\daemon\Manager as DaemonManager;
use df\link\http\IFileResponse;
use df\link\http\IGeneratorResponse;
use df\link\http\IRequest as HttpRequest;
use df\link\http\IResponse as Response;
use df\link\http\Url;

use Throwable;

class Http implements Kernel
{
    use KernelTrait;

    protected HttpRouter $router;
    protected HttpRequest $httpRequest;


    /**
     * Initialize platform systems
     */
    public function initialize(): void
    {
        // Http env
        $this->initializeHttpEnv();

        // Routing
        $this->router = Legacy::$http->getRouter();
        $this->httpRequest = Legacy::$http->initializeRequest();

        // Glitch
        $this->initializeGlitchSender();
    }


    protected function initializeHttpEnv(): void
    {
        // If you're on apache, it sometimes hides some env variables = v. annoying
        if (
            function_exists('apache_request_headers') &&
            false !== ($apache = apache_request_headers())
        ) {
            foreach ($apache as $key => $value) {
                $_SERVER['HTTP_' . strtoupper(str_replace('-', '_', (string)$key))] = $value;
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
    }

    protected function initializeGlitchSender(): void
    {
        Glitch::setHeaderBufferSender(function () {
            $augmentor = Legacy::$http->getResponseAugmentor();
            $cookies = $augmentor->getCookieCollectionForCurrentRequest();

            foreach ($cookies->toArray() as $cookie) {
                header('Set-Cookie: ' . $cookie->toString());
            }

            foreach ($cookies->getRemoved() as $cookie) {
                header('Set-Cookie: ' . $cookie->toInvalidateString());
            }
        });
    }



    /**
     * Get run mode
     */
    public function getMode(): string
    {
        return 'Http';
    }

    /**
     * Run app
     */
    public function run(): void
    {
        try {
            $request = $this->router->prepareDirectoryRequest($this->httpRequest);
            $this->prepareHttpRequest();

            $ip = $this->httpRequest->getIp();
            $this->enforceCredentials($ip);
            $this->checkIpRanges($ip, $request);

            $response = $this->dispatchRequest($request);
        } catch (IForcedResponse $e) {
            $response = $this->normalizeResponse($e->getResponse());
        }

        $this->sendResponse($response);
    }





    /**
     * Prepare HTTP request
     */
    protected function prepareHttpRequest(): void
    {
        // HTTPS redirect
        if (
            $this->router->shouldUseHttps() &&
            !$this->httpRequest->getUrl()->isSecure() &&
            Genesis::$environment->isProduction()
        ) {
            $response = Legacy::$http->redirect(
                $this->httpRequest->getUrl()
                    ->isSecure(true)
                    ->setPort(null)
            );

            $response->isPermanent(true);
            throw new ForcedResponse($response);
        }


        // Options request
        if (Legacy::$http->getMethod() == 'options') {
            throw new ForcedResponse(
                (Legacy::$http->stringResponse(''))->withHeaders(function ($headers) {
                    $headers->set('allow', 'OPTIONS, GET, HEAD, POST');
                })
            );
        }

        // Propfind request
        if (Legacy::$http->getMethod() == 'propfind') {
            throw new ForcedResponse(
                (Legacy::$http->stringResponse('Propfind is not supported'))->withHeaders(function ($headers) {
                    $headers->setStatusCode(405);
                    $headers->set('allow', 'OPTIONS, GET, HEAD, POST');
                })
            );
        }
    }



    /**
     * Enforce credentials
     */
    protected function enforceCredentials(Ip $ip): bool
    {
        $config = HttpConfig::getInstance();
        $credentials = $config->getCredentials();

        // Check for credentials or loopback
        if (
            $credentials === null ||
            !isset($credentials['username']) ||
            !isset($credentials['password']) ||
            $ip->isLoopback() ||
            Legacy::$http->isDfSelf()
        ) {
            return true;
        }

        // Check credentials
        if (
            !isset($_SERVER['PHP_AUTH_USER']) ||
            $_SERVER['PHP_AUTH_USER'] != $credentials['username'] ||
            $_SERVER['PHP_AUTH_PW'] != $credentials['password']
        ) {
            header('WWW-Authenticate: Basic realm="Developer Site"');
            header('HTTP/1.0 401 Unauthorized');
            echo 'You need to authenticate to view this site';
            exit;
        }

        return false;
    }



    /**
     * Check IP range
     */
    protected function checkIpRanges(
        Ip $ip,
        Request $request
    ): void {
        // Get ranges from config
        $config = HttpConfig::getInstance();
        $ranges = $config->getIpRangesForArea($request->getArea());

        if (empty($ranges)) {
            return;
        }


        // Check for passthrough header
        if (Legacy::$http->isDfSelf()) {
            return;
        }


        // Apply
        $augmentor = Legacy::$http->getResponseAugmentor();
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
            $context = new Context($request);
            static $salt = '3efcf3200384a9968a58841812d78f94d88a61b2e0cc57849a19707e0ebed065';
            static $username = 'e793f732b58b8c11ae4048214f9171392a864861d35c0881b3993d12001a78b0';
            static $password = '016ede424aa10ae5895c21c33d200c7b08aa33d961c05c08bfcf946cb7c53619';

            if (
                isset($_SERVER['PHP_AUTH_USER']) &&
                $context->data->hexHash($_SERVER['PHP_AUTH_USER'], $salt) == $username &&
                $context->data->hexHash($_SERVER['PHP_AUTH_PW'], $salt) == $password
            ) {
                return;
            } else {
                header('WWW-Authenticate: Basic realm="Private Site"');
                header('HTTP/1.0 401 Unauthorized');
                echo 'You need to authenticate to view this site';
                exit;
            }
        }


        // Not resolved
        $url = clone Legacy::$http->getUrl();
        $url->query->authenticate = null;

        $response = Legacy::$http->stringResponse(
            '<html><head><title>Forbidden</title></head><body>' .
            '<p>Sorry, this site is protected by IP range.</p><p>Your IP is: <strong>' . $ip . '</strong></p>' .
            '<p><a href="' . $url . '">Developer access</a></p>',
            'text/html'
        );

        $response->getHeaders()->setStatusCode(403);
        throw new ForcedResponse($response);
    }



    /**
     * Dispatch request
     */
    protected function dispatchRequest(Request $request): Response
    {
        Legacy::$http->setDispatchRequest(clone $request);

        try {
            $response = $this->dispatchNode($request);
            $response = $this->normalizeResponse($response);
        } catch (Throwable $e) {
            while (ob_get_level()) {
                ob_end_clean();
            }

            if ($e instanceof IForcedResponse) {
                $response = $this->normalizeResponse($e->getResponse());
            } else {
                Legacy::$http->setDispatchException($e);

                try {
                    $response = $this->dispatchNode(new Request('error/'));
                    $response = $this->normalizeResponse($response);
                } catch (Throwable $f) {
                    if ($f instanceof IForcedResponse) {
                        $response = $this->normalizeResponse($f->getResponse());
                    } else {
                        Glitch::logException($f);
                        throw $e;
                    }
                }
            }
        }

        return $response;
    }


    /**
     * Dispatch node
     */
    protected function dispatchNode(Request $request): mixed
    {
        Legacy::$http->getResponseAugmentor()->resetCurrent();

        /** @var Context $context */
        $context = Context::factory(clone $request);
        Legacy::setActiveContext($context);

        try {
            $node = NodeBase::factory($context);
        } catch (NodeNotFoundException $e) {
            // See if the url just needs a /
            $url = $this->httpRequest->getUrl();
            $testUrl = null;

            if (
                !$url->path->shouldAddTrailingSlash() &&
                $url->path->getFilename() != 'index'
            ) {
                $testUrl = clone $url;
                $testUrl->path->shouldAddTrailingSlash(true);
            } elseif ($url->path->shouldAddTrailingSlash()) {
                $testUrl = clone $url;
                $testUrl->path->shouldAddTrailingSlash(false);
            }

            if ($testUrl) {
                $context = clone $context;
                $context->location = $context->request = $this->router->urlToRequest($testUrl);

                if ($context->apex->nodeExists($context->request)) {
                    return Legacy::$http->redirect($context->request)
                        //->isPermanent(true)
                    ;
                }
            }

            throw $e;
        }

        foreach (Legacy::getRegistryObjects() as $object) {
            if ($object instanceof IDispatchAware) {
                $object->onAppDispatch($node);
            }
        }

        if (!$node->shouldOptimize()) {
            DaemonManager::getInstance()->ensureActivity();
        }

        return $node->dispatch();
    }



    /**
     * Normalize response
     */
    protected function normalizeResponse(mixed $response): Response
    {
        // Callback
        if (
            is_callable($response) &&
            (
                $response instanceof Closure ||
                $response instanceof ICallback
            )
        ) {
            $response = $response();
        }

        // Dereference proxy responses
        while ($response instanceof IProxyResponse) {
            $response = $response->toResponse();
        }

        // Forwarding
        if ($response instanceof Request) {
            $response = Legacy::$http->redirect($response);
        }

        // Empty response
        if (
            $response === null &&
            Genesis::$environment->isDevelopment()
        ) {
            throw Exceptional::NotImplemented([
                'message' => 'No response was returned by node: ' . Legacy::getContext()->request,
                'http' => 501
            ]);
        }

        // Basic response
        if (!$response instanceof Response) {
            $response = Legacy::$http->stringResponse(
                (string)$response,
                Typify::detect(strtolower(Legacy::getContext()->request->getType()))
            );

            //$response->getHeaders()->setCacheExpiration(60);
        }

        $response->onDispatchComplete();
        $headers = $response->getHeaders();

        if (Legacy::$http->isAjaxRequest()) {
            $headers->set('x-response-url', Legacy::$http->getUrl());
        }


        // Access control
        if ($this->httpRequest->getHeaders()->has('origin')) {
            $url = new Url($this->httpRequest->getHeaders()->get('origin'));
            $domain = $url->getDomain();

            if ($this->router->lookupDomain($domain)) {
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


    /**
     * Send response
     */
    protected function sendResponse(Response $response): void
    {
        // Apply globally defined cookies, headers, etc
        Legacy::$http->getResponseAugmentor()->apply($response);

        // HSTS
        if (
            $this->router->shouldUseHttps() &&
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

        if ($this->httpRequest->isCachedByClient()) {
            $headers = $response->getHeaders();

            if ($headers->isCached($this->httpRequest)) {
                $headers->setStatusCode(304);
                $sendData = false;
            }
        }

        // Redirect to x-sendfile header
        if (
            $response instanceof IFileResponse &&
            $sendData &&
            $response->isStaticFile() &&
            null !== ($sendFileHeader = $this->initializeSendFile())
        ) {
            $response->getHeaders()->set($sendFileHeader, $response->getStaticFilePath());
            $sendData = false;
        }


        // HEAD request
        if (Legacy::$http->getMethod() == 'head') {
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

            if ($response instanceof IGeneratorResponse) {
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

                if ($response instanceof IFileResponse) {
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


    protected function initializeSendFile(): ?string
    {
        $config = HttpConfig::getInstance();
        $sendFileHeader = $config->getSendFileHeader();

        if (isset($_SERVER['HTTP_X_SENDFILE_TYPE'])) {
            if ($_SERVER['HTTP_X_SENDFILE_TYPE'] === 'X-Accel-Redirect') {
                $sendFileHeader = null;
            } else {
                $sendFileHeader = $_SERVER['HTTP_X_SENDFILE_TYPE'];
            }
        }

        return $sendFileHeader;
    }
}

<?php
/**
 * This file is part of the Decode r7 framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Harvest\Middleware;

use Closure;
use DecodeLabs\Deliverance;
use DecodeLabs\Exceptional;
use DecodeLabs\Genesis;
use DecodeLabs\Glitch;
use DecodeLabs\R7\Config\Http as HttpConfig;
use DecodeLabs\R7\Legacy;
use DecodeLabs\Typify;
use df\arch\Context;
use df\arch\IForcedResponse;
use df\arch\IProxyResponse;
use df\arch\node\Base as NodeBase;
use df\arch\node\NotFoundException as NodeNotFoundException;
use df\arch\Request;
use df\core\app\http\Router as HttpRouter;
use df\core\IDispatchAware;
use df\core\lang\ICallback;
use df\halo\daemon\Manager as DaemonManager;
use df\link\http\IFileResponse;
use df\link\http\IGeneratorResponse;
use df\link\http\IRequest as HttpRequest;
use df\link\http\IResponse as Response;
use df\link\http\Url;
use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as PsrHandler;
use Throwable;

class LegacyKernel implements Middleware
{
    protected HttpRouter $router;
    protected HttpRequest $httpRequest;

    public function __construct()
    {
        // Routing
        $this->router = Legacy::$http->getRouter();
        $this->httpRequest = Legacy::$http->initializeRequest();
    }


    /**
     * Process middleware
     */
    public function process(
        PsrRequest $request,
        PsrHandler $next
    ): PsrResponse {
        try {
            $request = $this->router->prepareDirectoryRequest($this->httpRequest);
            $response = $this->dispatchRequest($request);
        } catch (IForcedResponse $e) {
            $response = $this->normalizeResponse($e->getResponse());
        }

        $this->sendResponse($response);
        exit;
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
        $config = HttpConfig::load();
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

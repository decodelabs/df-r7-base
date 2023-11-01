<?php
/**
 * This file is part of the Decode r7 framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Harvest\Middleware;

use Closure;
use DecodeLabs\Exceptional;
use DecodeLabs\Genesis;
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
use df\link\http\IResponse as Response;
use df\link\http\Url;
use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as PsrHandler;
use Throwable;

class LegacyKernel implements Middleware
{
    protected PsrRequest $psrRequest;
    protected HttpRouter $router;

    public function __construct()
    {
        // Routing
        $this->router = Legacy::$http->getRouter();
    }


    /**
     * Process middleware
     */
    public function process(
        PsrRequest $request,
        PsrHandler $next
    ): PsrResponse {
        $this->psrRequest = $request;

        try {
            $directoryRequest = $this->router->prepareDirectoryRequest($request);
            $response = $this->dispatchRequest($directoryRequest);
        } catch (IForcedResponse $e) {
            $response = $this->normalizeResponse($e->getResponse());
        }

        return $response;
    }


    /**
     * Dispatch request
     */
    protected function dispatchRequest(
        Request $request
    ): PsrResponse {
        Legacy::$http->setDispatchRequest(clone $request);

        if (($e = $this->psrRequest->getAttribute('error')) instanceof Throwable) {
            Legacy::$http->setDispatchException($e);
        }

        try {
            $response = $this->dispatchNode($request);
            $response = $this->normalizeResponse($response);
        } catch (IForcedResponse $e) {
            $response = $this->normalizeResponse($e->getResponse());
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
            return $this->handleNotFound($context, $e);
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
     * Attempt to find node with trailing slash
     */
    protected function handleNotFound(
        Context $context,
        NodeNotFoundException $e
    ): Response|PsrResponse {
        // See if the url just needs a /
        $url = new Url((string)$this->psrRequest->getUri());
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
                return Legacy::$http->redirect($context->request);
            }
        }

        throw $e;
    }


    /**
     * Normalize response
     */
    protected function normalizeResponse(
        mixed $response
    ): PsrResponse {
        if ($response instanceof PsrResponse) {
            return $response;
        }

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
        }

        $response->onDispatchComplete();
        return $response->toPsrResponse();
    }
}

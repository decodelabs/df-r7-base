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
use df\arch\node\INode;
use df\arch\node\NotFoundException as NodeNotFoundException;
use df\arch\Request;
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
    /**
     * Process middleware
     */
    public function process(
        PsrRequest $request,
        PsrHandler $next
    ): PsrResponse {
        try {
            $router = Legacy::$http->getRouter();
            $directoryRequest = $router->prepareDirectoryRequest($request);
            $this->resetContext($request, $directoryRequest);

            try {
                $node = $this->loadNode($directoryRequest);
            } catch (NodeNotFoundException $e) {
                return $this->handleNotFound($request, $directoryRequest, $e);
            }

            $response = $node->dispatch();
        } catch (IForcedResponse $e) {
            $response = $e->getResponse();
        }

        $response = $this->normalizeResponse($response);
        return $response;
    }


    /**
     * Reset context
     */
    protected function resetContext(
        PsrRequest $request,
        Request $directoryRequest
    ): void {
        Legacy::$http->getResponseAugmentor()->resetCurrent();

        if (($e = $request->getAttribute('error')) instanceof Throwable) {
            Legacy::$http->setDispatchException($e);
            $directoryRequest->setArea('front');
        } else {
            Legacy::$http->setDispatchRequest(clone $directoryRequest);
        }
    }


    /**
     * Load node
     */
    protected function loadNode(
        Request $directoryRequest
    ): INode {
        // Create context
        /** @var Context $context */
        $context = Context::factory(clone $directoryRequest);
        Legacy::setActiveContext($context);


        // Load node
        $node = NodeBase::factory($context);


        // Notify registry objects
        foreach (Legacy::getRegistryObjects() as $object) {
            if ($object instanceof IDispatchAware) {
                $object->onAppDispatch($node);
            }
        }


        // Ensure daemon activity
        if (!$node->shouldOptimize()) {
            DaemonManager::getInstance()->ensureActivity();
        }

        return $node;
    }


    /**
     * Attempt to find node with trailing slash
     */
    protected function handleNotFound(
        PsrRequest $psrRequest,
        Request $directoryRequest,
        NodeNotFoundException $e
    ): PsrResponse {
        // See if the url just needs a /
        $url = new Url((string)$psrRequest->getUri());
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
            $router = Legacy::$http->getRouter();

            /** @var Context $context */
            $context = Context::factory(clone $directoryRequest);
            $context->location = $context->request = $router->urlToRequest($testUrl);

            if ($context->apex->nodeExists($context->request)) {
                return Legacy::$http->redirect($context->request)->toPsrResponse();
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

<?php
/**
 * This file is part of the Decode r7 framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Genesis\Kernel;

use DecodeLabs\Genesis\Kernel;
use DecodeLabs\Glitch;
use DecodeLabs\Harvest;
use DecodeLabs\Harvest\Dispatcher;
use DecodeLabs\Harvest\Request;
use DecodeLabs\Harvest\Request\Factory\Environment as RequestFactory;
use DecodeLabs\R7\Genesis\KernelTrait;
use DecodeLabs\R7\Legacy;
use DecodeLabs\Sanctum\Definition as SanctumDefinition;
use Psr\Http\Message\ResponseInterface as Response;

class Http implements Kernel
{
    use KernelTrait;

    protected Dispatcher $dispatcher;
    protected Request $request;

    /**
     * Initialize platform systems
     */
    public function initialize(): void
    {
        // Dispatcher
        $this->dispatcher = new Dispatcher(
            $this->context->container
        );

        // Middleware
        $this->dispatcher->add(
            // Generators
            'LegacyKernel',

            // Outbound
            'ResponseAugmentor',
            'AccessControl',
            'AjaxResponseUrl',
            'ContentSecurityPolicy',
            'ClientCache',

            // Inbound
            'CheckIpRanges',
            'EnforceCredentials',
            'HttpMethod',
            'EnsureHttps',

            // Error
            'ErrorHandler'
        );

        // Request
        $this->request = (new RequestFactory())->createServerRequest();
        $_SERVER = $this->request->getServerParams();


        // Csp
        $this->context->container->bindShared(
            SanctumDefinition::class,
            function (Response $response) {
                $contentType = explode(';', $response->getHeaderLine('content-type'))[0];

                if (empty($contentType)) {
                    $contentType = 'text/html';
                }

                return Legacy::app()->getCsp($contentType);
            }
        );


        // Delete
        $this->doLegacyStuff();
    }

    protected function doLegacyStuff(): void
    {
        Legacy::$http->initializeRequest();

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
        $response = $this->dispatcher->handle($this->request);
        $transport = Harvest::createTransport();

        $transport->sendResponse(
            $this->request,
            $response
        );
    }
}

<?php
/**
 * This file is part of the Decode r7 framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Legacy\Plugins;

use Closure;
use DateTime;
use DecodeLabs\Atlas\File;
use DecodeLabs\Coercion;
use DecodeLabs\Deliverance\Channel;
use DecodeLabs\Exceptional;
use DecodeLabs\R7\Legacy\Helper;
use df\arch\IAjaxDataProvider as AjaxDataProvider;
use df\arch\Request;
use df\aura\view\IView as View;
use df\core\app\http\Router as HttpRouter;
use df\core\collection\Tree;
use df\core\time\Date;
use df\flex\csv\Builder as CsvBuilder;
use df\link\http\ICookie;
use df\link\http\IRequest as HttpRequest;
use df\link\http\request\Base as HttpRequestBase;
use df\link\http\request\HeaderCollection;
use df\link\http\response\Augmentor as HttpResponseAugmentor;
use df\link\http\response\File as FileResponse;
use df\link\http\response\Generator as GeneratorResponse;
use df\link\http\response\Redirect as RedirectResponse;
use df\link\http\response\Stream as StreamResponse;
use df\link\http\Url;
use Stringable;
use Throwable;

class Http
{
    protected ?HttpRequest $request = null;
    protected HttpRouter $router;
    protected HttpResponseAugmentor $responseAugmentor;
    protected ?Request $dispatchRequest = null;
    protected ?Throwable $dispatchException = null;

    protected Helper $helper;

    public function __construct(Helper $helper)
    {
        $this->helper = $helper;
    }


    /**
     * Initialize request from environment
     */
    public function initializeRequest(): HttpRequest
    {
        if (!$this->request) {
            $this->request = new HttpRequestBase(null, true);
        }

        return $this->request;
    }

    /**
     * Set HTTP request
     */
    public function setRequest(?HttpRequest $request): void
    {
        $this->request = $request;
    }

    /**
     * Get HTTP request
     */
    public function getRequest(): HttpRequest
    {
        if (!$this->request) {
            throw Exceptional::Setup('No HTTP request available');
        }

        return $this->request;
    }

    /**
     * Get HTTP router
     */
    public function getRouter(): HttpRouter
    {
        if (!isset($this->router)) {
            $this->router = new HttpRouter();
        }

        return $this->router;
    }


    /**
     * Get response augmentor
     */
    public function getResponseAugmentor(): HttpResponseAugmentor
    {
        if (!isset($this->responseAugmentor)) {
            $this->responseAugmentor = new HttpResponseAugmentor($this->getRouter());
        }

        return $this->responseAugmentor;
    }



    /**
     * Set dispatch request
     */
    public function setDispatchRequest(?Request $request): void
    {
        $this->dispatchRequest = $request;
    }

    /**
     * Get dispatch request
     */
    public function getDispatchRequest(): ?Request
    {
        return $this->dispatchRequest;
    }

    /**
     * Set dispatch exception
     */
    public function setDispatchException(?Throwable $exception): void
    {
        $this->dispatchException = $exception;
    }

    /**
     * Get dispatch exception
     */
    public function getDispatchException(): ?Throwable
    {
        return $this->dispatchException;
    }



    /**
     * Get DF self key
     */
    public function getDfSelfKey(): string
    {
        return md5($this->helper->getPassKey());
    }

    /**
     * Is DF self
     */
    public function isDfSelf(): bool
    {
        return $this->getHeader('x-df-self') === $this->getDfSelfKey();
    }


    /**
     * Get URL
     */
    public function getUrl(): Url
    {
        return $this->getRequest()->getUrl();
    }

    /**
     * Get host from URL
     */
    public function getHost(): string
    {
        return $this->getUrl()->getDomain();
    }


    /**
     * Get HTTP method
     */
    public function getMethod(): string
    {
        return $this->getRequest()->getMethod();
    }


    /**
     * Get header collection
     */
    public function getHeaders(): HeaderCollection
    {
        return $this->getRequest()->getHeaders();
    }

    /**
     * Get individual header
     */
    public function getHeader(string $key): mixed
    {
        return $this->getHeaders()->get($key);
    }

    /**
     * Get user agent
     */
    public function getUserAgent(): ?string
    {
        return Coercion::toStringOrNull($this->getHeader('User-Agent'));
    }

    /**
     * Get referrer
     */
    public function getReferrer(): ?string
    {
        return Coercion::toStringOrNull($this->getHeader('Referer'));
    }


    /**
     * Get referrer directory request
     */
    public function getReferrerDirectoryRequest(): ?Request
    {
        if (!$referrer = $this->getReferrer()) {
            return null;
        }

        return $this->localReferrerToRequest($referrer);
    }

    /**
     * Local site referrer to request
     */
    public function localReferrerToRequest(string $referrer): ?Request
    {
        try {
            return $this->getRouter()->urlToRequest(Url::factory($referrer));
        } catch (Throwable $e) {
            return null;
        }
    }


    /**
     * Get post data
     */
    public function getPostData(): Tree
    {
        return $this->getRequest()->getPostData() ?? new Tree();
    }


    /**
     * Get Cookie tree
     */
    public function getCookies(): Tree
    {
        return $this->getRequest()->getCookies();
    }


    /**
     * Set cookie in response augmentor
     */
    public function setCookie(
        string|ICookie $name,
        ?string $value = null,
        string|Date|DateTime|null $expiry = null,
        bool $httpOnly = null,
        bool $secure = null
    ): ICookie {
        $augmentor = $this->getResponseAugmentor();

        if ($name instanceof ICookie) {
            $cookie = $name;
        } else {
            $cookie = $augmentor->newCookie($name, $value, $expiry, $httpOnly, $secure);
        }

        $augmentor->setCookieForAnyRequest($cookie);
        return $cookie;
    }


    /**
     * Get cookie from request
     */
    public function getCookie(
        string $name,
        ?string $default = null
    ): ?string {
        return $this->getCookies()->get($name, $default);
    }


    /**
     * Does cookie exist?
     */
    public function hasCookie(string $name): bool
    {
        return $this->getCookies()->has($name);
    }

    /**
     * Remove cookie from response
     */
    public function removeCookie(
        string|ICookie $name
    ): ICookie {
        $augmentor = $this->getResponseAugmentor();

        if ($name instanceof ICookie) {
            $cookie = $name;
        } else {
            $cookie = $augmentor->newCookie($name, 'deleted');
        }

        $augmentor->removeCookieForAnyRequest($cookie);
        return $cookie;
    }


    /**
     * Is in DF ajax request
     */
    public function isAjaxRequest(): bool
    {
        $headers = $this->getRequest()->getHeaders();

        return
            strtolower($headers->get('x-requested-with') ?? '') === 'xmlhttprequest' ||
            $headers->has('x-ajax-request-type');
    }




    /**
     * Create a string response
     */
    public function stringResponse(
        string|Stringable|null $content,
        ?string $contentType = null
    ): StreamResponse {
        return new StreamResponse((string)$content, $contentType);
    }

    /**
     * Create Channel stream response
     */
    public function streamResponse(
        string|Stringable|Channel|null $content,
        ?string $contentType = null
    ): StreamResponse {
        return new StreamResponse($content, $contentType);
    }


    /**
     * Create a file response
     */
    public function fileResponse(
        string|File $path,
        bool $checkPath = true
    ): FileResponse {
        return new FileResponse($path, $checkPath);
    }


    /**
     * Create a json data response
     */
    public function jsonResponse(
        mixed $data,
        int $flags = 0
    ): StreamResponse {
        return $this->streamResponse(
            $this->helper->getContext()->data->toJson($data, $flags),
            'application/json'
        );
    }



    /**
     * Create redirect with active request
     */
    public function redirect(
        Closure|string|Stringable|Request|Url|null $request = null
    ): RedirectResponse {
        $context = $this->helper->getContext();

        if ($request instanceof Closure) {
            $request = $request(clone $context->request);
        }

        $url = $context->uri->__invoke($request);

        if ($url->isJustFragment()) {
            $fragment = $url->getFragment();
            $url = clone $this->getUrl();
            $url->setFragment($fragment);
        }

        return new RedirectResponse($url);
    }

    /**
     * Apply redirect with active request
     */
    public function redirectNow(
        Closure|string|Stringable|Request|Url|null $request = null
    ): Throwable {
        return $this->helper->getContext()->forceResponse(
            $this->redirect($request)
        );
    }


    /**
     * Create default redireect
     */
    public function defaultRedirect(
        ?string $default = null,
        bool $success = true,
        ?string $sectionReferrer = null,
        ?string $fallback = null
    ): RedirectResponse {
        $request = $this->helper->getContext()->request;

        if ($success) {
            if (!$redirect = $request->getRedirectTo()) {
                if ($default !== null) {
                    $redirect = $default;
                } else {
                    $redirect = $request->getRedirectFrom();
                }
            }

            if ($redirect) {
                return $this->redirect($redirect);
            }
        }

        if (!$success && ($redirect = $request->getRedirectFrom())) {
            return $this->redirect($redirect);
        }

        if ($default !== null) {
            return $this->redirect($default);
        }

        if ($fallback !== null) {
            return $this->redirect($fallback);
        }

        if ($sectionReferrer !== null) {
            if (substr($sectionReferrer, 0, 4) == 'http') {
                $sectionReferrer = $this->localReferrerToRequest($sectionReferrer);
            }

            return $this->redirect($sectionReferrer);
        }

        if ($referrer = $this->getReferrer()) {
            $referrer = $this->localReferrerToRequest($referrer);

            if ($referrer && !$referrer->matches($request)) {
                return $this->redirect($referrer);
            }
        }

        return $this->redirect($request->getParent());
    }


    /**
     * Apply redirect with active request
     */
    public function defaultRedirectNow(
        ?string $default = null,
        bool $success = true,
        ?string $sectionReferrer = null,
        ?string $fallback = null
    ): Throwable {
        return $this->helper->getContext()->forceResponse(
            $this->defaultRedirect(
                $default,
                $success,
                $sectionReferrer,
                $fallback
            )
        );
    }



    /**
     * Create a generator response
     */
    public function generator(
        string $contentType,
        callable $sender
    ): GeneratorResponse {
        return new GeneratorResponse($contentType, $sender);
    }

    /**
     * Create a CSV generator response
     */
    public function csvGenerator(
        string $fileName,
        Closure $generator
    ): GeneratorResponse {
        return $this->generator('text/csv', function ($stream) use ($generator) {
            (new CsvBuilder($generator))
                ->setDataReceiver($stream)
                ->sendData();
        })->setAttachmentFileName($fileName);
    }



    /**
     * Send ajax data response
     *
     * @param array<string, mixed> $extraData
     */
    public function ajaxResponse(
        mixed $content,
        array $extraData = []
    ): StreamResponse {
        $originalContent = $content;

        if ($content instanceof View) {
            $content = $this->getAjaxViewContent($content);
        }

        if ($originalContent instanceof AjaxDataProvider) {
            $extraData = array_merge($originalContent->getAjaxData(), $extraData);
        }

        $context = $this->helper->getContext();

        return $this->stringResponse(
            $context->data->toJson(array_merge(
                [
                    'node' => $context->request->getLiteralPathString(),
                    'content' => $content
                ],
                $extraData
            )),
            'application/json'
        );
    }


    /**
     * Send ajax reload package
     *
     * @param array<string, mixed> $extraData
     */
    public function ajaxReload(array $extraData = []): StreamResponse
    {
        $context = $this->helper->getContext();

        return $this->stringResponse(
            $context->data->toJson(array_merge(
                [
                    'node' => $context->request->getLiteralPathString(),
                    'reload' => true
                ],
                $extraData
            )),
            'application/json'
        );
    }


    /**
     * Prepare view for ajax response
     */
    public function getAjaxViewContent(View $view): string
    {
        return (string)$view->getContentProvider()->setRenderTarget($view);
    }
}

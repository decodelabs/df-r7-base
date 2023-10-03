<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\arch\node;

use DecodeLabs\Dictum;
use DecodeLabs\Exceptional;
use DecodeLabs\Genesis;
use DecodeLabs\R7\Legacy;
use df\arch;

use df\arch\node\form\State as FormState;
use df\aura;
use df\aura\view\IContentProvider as ViewContentProvider;

use df\aura\view\IView;
use df\core;
use df\flex;
use df\link;

abstract class Form extends Base implements IFormNode
{
    use TForm;

    public const SESSION_ID_KEY = 'fsid';
    public const MAX_SESSIONS = 15;
    public const SESSION_PRUNE_THRESHOLD = 5400; // 1.5 hrs
    public const SESSION_AUTO_RESUME = true;
    public const AUTO_INSTANCE_ID_IGNORE = ['rf', 'rt'];

    public const DEFAULT_EVENT = 'save';
    public const DEFAULT_REDIRECT = null;

    public const QUERY_RESET = false;

    private bool $_isNew = false;
    private bool $_isComplete = false;
    private ?string $_sessionNamespace = null;


    public function __construct(arch\IContext $context)
    {
        parent::__construct($context);

        if (Genesis::$kernel->getMode() !== 'Http') {
            throw Exceptional::Logic(
                'Form nodes can only be used in Http run mode'
            );
        }

        $this->event = new arch\node\form\EventDescriptor();
        $this->afterConstruct();
    }

    protected function afterConstruct(): void
    {
    }

    final protected function _beforeDispatch(): void
    {
        $this->init();
        $request = $this->context->request;
        $id = null;

        $this->_sessionNamespace = $this->_createSessionNamespace();
        $session = $this->context->getUserManager()->session->getBucket($this->_sessionNamespace);

        if ($request->hasQuery() && $request->getQuery()->has(static::SESSION_ID_KEY)) {
            $id = $request->getQuery()->get(static::SESSION_ID_KEY);
        }

        if (!empty($id)) {
            $this->_state = $session->get($id);
        } else {
            if (static::SESSION_AUTO_RESUME) {
                $this->_state = $session->getLastUpdated();
            } else {
                $id = $this->_createSessionId();

                $request->getQuery()->set(
                    static::SESSION_ID_KEY,
                    $id
                );
            }
        }

        if (!$this->_state) {
            if (empty($id)) {
                $id = $this->_createSessionId();
            }

            $this->_state = new arch\node\form\State($id);
            $keys = $session->getAllKeys();

            if (count($keys) > static::MAX_SESSIONS) {
                $this->context->comms->flash(
                    'form.session.prune',
                    $this->context->_('The maximum form session threshold has been reached'),
                    'debug'
                );


                $session->prune(static::SESSION_PRUNE_THRESHOLD);
                $session->remove(array_shift($keys));
            }
        }

        $this->_isNew = $this->_state->isNew();
        $this->values = $this->_state->values;

        if (static::QUERY_RESET && isset($this->request->query->reset)) {
            $this->_state->reset();

            throw Legacy::$http->redirectNow(function ($request) {
                unset($request->query->reset);
                return $request;
            });
        }

        $this->initWithSession();
        $this->loadDelegates();

        if ($this->_isNew) {
            $this->setDefaultValues();
        }

        foreach ($this->_delegates as $delegate) {
            $response = $delegate->beginInitialize();

            if (!empty($response)) {
                throw $this->forceResponse($response);
            }
        }

        foreach ($this->_delegates as $delegate) {
            $delegate->endInitialize();
        }


        if ($this->_state->isNew()) {
            if (($referrer = Legacy::$http->getReferrerDirectoryRequest())
            && $referrer->matches($this->request)) {
                $referrer = null;
            }

            $this->_state->referrer = $referrer;

            $method = Legacy::$http->getMethod();

            if (!$this->_state->isOperating) {
                $this->_state->isOperating = $method != 'get' && $method != 'head';
            }
        } else {
            $this->_state->isOperating = true;
        }

        $this->_state->isNew(false);
        $this->afterInit();
    }

    public function isNew(): bool
    {
        return $this->_isNew;
    }

    public function isComplete(): bool
    {
        return $this->_isComplete;
    }

    private function _createSessionId(): string
    {
        return flex\Generator::sessionId();
    }

    private function _createSessionNamespace(): string
    {
        $request = clone $this->context->request;
        $ext = $this->getSessionNamespaceExtension();

        if ($ext === 'ajax') {
            $ext = null;
        }

        $request->setType($ext);
        $output = 'form://' . implode('/', $request->getLiteralPathArray());

        if (null !== ($dataId = $this->getInstanceId())) {
            $output .= '#' . $dataId;
        }

        return $output;
    }

    protected function getSessionNamespaceExtension(): ?string
    {
        return $this->context->request->getPath()->getExtension();
    }

    protected function getInstanceId(): ?string
    {
        $output = [];
        $ignore = array_unique(array_merge(self::AUTO_INSTANCE_ID_IGNORE, static::AUTO_INSTANCE_ID_IGNORE));

        foreach ($this->request->query as $key => $node) {
            if (in_array($key, $ignore) || !$node->hasValue()) {
                continue;
            }

            $output[$key] = $node->getValue();
        }

        if (!empty($output)) {
            return implode('|', $output);
        }

        return null;
    }

    protected function initWithSession(): void
    {
    }

    public function getState(): FormState
    {
        if (!$this->_state) {
            throw Exceptional::{'NoState,NoContext'}(
                'State controller is not available until the form has been dispatched'
            );
        }

        return $this->_state;
    }

    public function dispatchToRenderInline(aura\view\IView $view): ViewContentProvider
    {
        $this->_beforeDispatch();
        $this->view = $view;

        $this->_isRenderingInline = true;
        $method = $this->getDispatchMethodName();
        $this->{$method}();
        $this->_isRenderingInline = false;

        return $this->content;
    }






    // State

    /**
     * @return $this
     */
    public function reset(): static
    {
        $this->_state->reset();

        foreach ($this->_delegates as $id => $delegate) {
            $this->unloadDelegate($id);
        }

        $this->afterReset();

        $this->initWithSession();
        $this->loadDelegates();
        $this->setDefaultValues();

        foreach ($this->_delegates as $id => $delegate) {
            $delegate->initialize();
        }

        $this->_state->isNew(false);
        $this->afterInit();

        return $this;
    }

    protected function afterReset(): void
    {
    }




    // HTML Request
    public function handleHtmlGetRequest(): mixed
    {
        if ($this->event->hasResponse()) {
            return $this->event->getResponse();
        }

        return $this->_renderHtmlGetRequest();
    }

    private function _renderHtmlGetRequest(): IView
    {
        $setContentProvider = false;

        if (!$this->view) {
            $this->view = aura\view\Base::factory('Html', $this->context);
            $setContentProvider = true;
        }

        $this->content = new aura\view\content\WidgetContentProvider($this->view->getContext());

        $classes = $this->request->getControllerParts();
        $classes[] = $this->request->getNode();

        $this->content->addClasses(array_map(function ($value) {
            return Dictum::slug($value);
        }, $classes));

        $this->content->addClass('form');

        if ($setContentProvider) {
            $this->view->setContentProvider($this->content);
        }

        $this->content->setRenderTarget($this->view);

        foreach ($this->_delegates as $delegate) {
            $delegate->setRenderContext($this->view, $this->content, $this->_isRenderingInline);
        }

        if ($decorator = arch\decorator\Form::factory($this)) {
            $decorator->renderUi();
        } elseif (method_exists($this, 'createUi')) {
            $this->createUi();
        } elseif (Genesis::$environment->isDevelopment()) {
            $this->content->add(
                'p',
                'This form handler has no ui generator - you need to implement function createUi() or override function handleGetRequest()'
            );
        }

        $this->view->getHeaders()
            ->setCacheAccess('no-cache')
            ->canStoreCache(false)
            ->shouldRevalidateCache(true);

        if ($this->content instanceof aura\view\ICollapsibleContentProvider) {
            $this->content->collapse();
        }

        return $this->view;
    }

    public function handleHtmlPostRequest(): mixed
    {
        if ($this->event->hasResponse()) {
            return $this->event->getResponse();
        }

        $this->_runPostRequest();
        $response = $this->event->getResponse();

        if (empty($response)) {
            $response = Legacy::$http->redirect()->isAlternativeContent(true);
        }

        return $response;
    }



    // JSON Request
    public function handleJsonGetRequest(): mixed
    {
        return Legacy::$http->jsonResponse($this->_getJsonResponseData());
    }

    public function handleJsonPostRequest()
    {
        $this->_runPostRequest();
        $data = $this->_getJsonResponseData();

        if ($this->event->hasRedirect()) {
            $data['redirect'] = $this->uri($this->event->getRedirect());
        }

        return Legacy::$http->jsonResponse($data);
    }

    private function _getJsonResponseData(): array
    {
        return array_merge(
            $this->getStateData(),
            $this->view ? $this->view->getAjaxData() : [],
            [
                'node' => $this->context->request->getLiteralPathString(),
                'events' => $this->getAvailableEvents(),
                'defaultEvent' => static::DEFAULT_EVENT,
                'isNew' => $this->_isNew,
                'isComplete' => $this->_isComplete,
                'redirect' => $this->event->getRedirect(),
                'forceRedirect' => $this->event->shouldForceRedirect(),
                'reload' => $this->event->shouldReload()
            ]
        );
    }


    // AJAX Request
    public function handleAjaxGetRequest(): mixed
    {
        return Legacy::$http->jsonResponse($this->_getAjaxResponseData());
    }

    public function handleAjaxPostRequest(): mixed
    {
        if (!$this->event->hasResponse()) {
            $this->_runPostRequest();
        }

        return Legacy::$http->jsonResponse($this->_getAjaxResponseData());
    }

    private function _getAjaxResponseData(): array
    {
        $response = $this->event->getResponse();
        $loadUi = $this->event->getEventName() !== null
            || $response === null;

        $content = null;
        $type = 'text/html';

        if ($response instanceof link\http\IResponse) {
            $content = $response->getContent();
            $type = $response->getContentType();
        } elseif (is_string($response)) {
            $content = $response;
        }

        if ($content === null && $loadUi) {
            $content = Legacy::$http->getAjaxViewContent($this->_renderHtmlGetRequest());
        }

        $redirect = $this->event->hasRedirect() ?
            $this->uri($this->event->getRedirect()) : null;

        $output = [
            'node' => $this->context->request->getLiteralPathString(),
            'content' => $content,
            'type' => $type,
            'redirect' => $redirect,
            'forceRedirect' => $this->event->shouldForceRedirect(),
            'reload' => $this->event->shouldReload(),
            'isNew' => $this->_isNew,
            'isComplete' => $this->_isComplete
        ];

        if ($this->view) {
            $output = array_merge($this->view->getAjaxData(), $output);
        }

        return $output;
    }



    private function _runPostRequest(core\collection\ITree $postData = null): void
    {
        if ($postData === null) {
            $postData = clone Legacy::$http->getPostData();
        }

        $event = null;

        if ($postData->has('formEvent')) {
            $event = $postData->get('formEvent');
            $postData->remove('formEvent');
        }

        $matches = [];

        if (preg_match('/^\<[a-z]+ .*data\-button\-event\=\"([^"]+)\"/i', (string)$event, $matches)) {
            $event = $matches[1];
        }

        if ($postData->__isset('_delegates')) {
            foreach ($postData->_delegates as $id => $delegateValues) {
                try {
                    $this->getDelegate($id)->values->clear()->import($delegateValues);
                } catch (DelegateException $e) {
                }
            }

            $postData->remove('_delegates');
        }

        $this->values->clear()->import($postData);

        if (empty($event)) {
            $event = $this->getDefaultEvent();

            if (empty($event)) {
                $event = self::DEFAULT_EVENT;
            }
        }


        $parts = explode('(', $event, 2);
        $event = (string)array_shift($parts);
        $args = substr((string)array_pop($parts), 0, -1);

        if (!empty($args)) {
            $args = flex\Delimited::parse($args);
        } else {
            $args = [];
        }

        $targetId = explode('.', trim($event, '.'));
        $event = (string)array_pop($targetId);
        $targetString = implode('.', $targetId);
        $target = $this;
        $isTargetComplete = false;

        if (!empty($targetId)) {
            while (!empty($targetId)) {
                $target->handleDelegateEvent(implode('.', $targetId), $event, $args);
                $currentId = (string)array_shift($targetId);

                try {
                    $target = $target->getDelegate($currentId);
                } catch (DelegateException $e) {
                    if ($target->handleMissingDelegate($currentId, $event, $args)) {
                        $isTargetComplete = $target->isComplete();
                        $target = null;
                        break;
                    } else {
                        throw $e;
                    }
                }
            }
        }


        if ($target) {
            $target->handleEvent($event, $args);
            $this->triggerPostEvent($target, $event, $args);
            $isTargetComplete = $target->isComplete();
        }

        if ($isTargetComplete) {
            $this->setComplete();

            foreach ($this->_delegates as $delegate) {
                $delegate->setComplete();
            }

            if ($this->_sessionNamespace) {
                $session = $this->context->getUserManager()->session->getBucket($this->_sessionNamespace);
                $session->remove($this->_state->sessionId);
            }
        }
    }


    public function setComplete(): void
    {
        $this->_isComplete = true;
    }

    public function getStateData(): array
    {
        $output = [
            'isValid' => $this->isValid(),
            'isNew' => $this->_isNew,
            'values' => $this->values->toArrayDelimitedSet(),
            'errors' => []
        ];

        foreach ($this->_delegates as $delegate) {
            $delegateState = $delegate->getStateData();

            if (!$delegateState['isValid']) {
                $output['isValid'] = false;
            }

            $output['values'] = array_merge($output['values'], $delegateState['values']);
            $output['errors'] = array_merge($output['errors'], $delegateState['errors']);
        }

        return $output;
    }

    // Names
    public function fieldName(string $name): string
    {
        return $name;
    }

    public function elementId(string $name): string
    {
        return Dictum::slug($name);
    }


    // Events
    protected function onCancelEvent(): mixed
    {
        $this->setComplete();
        return $this->_getCompleteRedirect(null, false);
    }

    protected function getDefaultRedirect(): ?string
    {
        return static::DEFAULT_REDIRECT;
    }

    protected function getDefaultEvent(): string
    {
        return static::DEFAULT_EVENT;
    }


    // Node dispatch
    public function getDispatchMethodName(): ?string
    {
        $method = ucfirst(strtolower(Legacy::$http->getMethod()));

        if ($method == 'Head') {
            $method = 'Get';
        }

        $func = 'handle' . $this->context->request->getType() . $method . 'Request';

        return method_exists($this, $func) ?
            $func : null;
    }

    protected function _handleNoDispatchMethod(): mixed
    {
        if ($this->context->request->getType() == 'Htm') {
            $request = clone $this->context->request->setType('Html');

            return Legacy::$http->redirect($request)
                ->isPermanent(true);
        }

        throw Exceptional::BadRequest([
            'message' => 'Form node ' . $this->context->location->getLiteralPath() . ' does not support ' .
                Legacy::$http->getMethod() . ' http method',
            'http' => 405
        ]);
    }

    protected function _afterDispatch(mixed $response): mixed
    {
        if (
            !$this->_isComplete &&
            $this->_sessionNamespace &&
            $this->_state->isOperating()
        ) {
            $session = $this->context->getUserManager()->session->getBucket($this->_sessionNamespace);
            $session->set($this->_state->sessionId, $this->_state);
        }

        return $response;
    }
}

<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\node;

use df;
use df\core;
use df\arch;
use df\aura;
use df\flex;
use df\link;

abstract class Form extends Base implements IFormNode {

    use TForm;

    const SESSION_ID_KEY = 'fsid';
    const MAX_SESSIONS = 15;
    const SESSION_PRUNE_THRESHOLD = 5400; // 1.5 hrs
    const SESSION_AUTO_RESUME = true;

    const DEFAULT_EVENT = 'save';
    const DEFAULT_REDIRECT = null;

    private $_isNew = false;
    private $_isComplete = false;
    private $_sessionNamespace;


    public function __construct(arch\IContext $context) {
        parent::__construct($context);

        if($this->context->getRunMode() !== 'Http') {
            throw new RuntimeException(
                'Form nodes can only be used in Http run mode'
            );
        }

        $this->event = new arch\node\form\EventDescriptor();
        $this->afterConstruct();
    }

    protected function afterConstruct() {}

    final protected function _beforeDispatch() {
        $response = $this->init();
        $request = $this->context->request;
        $id = null;

        if(!empty($response)) {
            $this->event->parseOutput($response);
            return $response;
        }

        $this->_sessionNamespace = $this->_createSessionNamespace();
        $session = $this->context->getUserManager()->session->getBucket($this->_sessionNamespace);

        if($request->hasQuery() && $request->getQuery()->has(static::SESSION_ID_KEY)) {
            $id = $request->getQuery()->get(static::SESSION_ID_KEY);
        }

        if(!empty($id)) {
            $this->_state = $session->get($id);
        } else {
            if(static::SESSION_AUTO_RESUME) {
                $this->_state = $session->getLastUpdated();
            } else {
                $id = $this->_createSessionId();

                $request->getQuery()->set(
                    static::SESSION_ID_KEY, $id
                );
            }
        }

        if(!$this->_state) {
            if(empty($id)) {
                $id = $this->_createSessionId();
            }

            $this->_state = new arch\node\form\State($id);
            $keys = $session->getAllKeys();

            if(count($keys) > static::MAX_SESSIONS) {
                $this->context->comms->flash(
                    'form.session.prune',
                    $this->context->_('The maximum form session threshold has been reached'),
                    'debug'
                );


                $session->prune(static::SESSION_PRUNE_THRESHOLD);
                $session->remove(array_shift($keys));
            }
        }

        $this->values = $this->_state->values;
        $response = $this->initWithSession();

        if(!empty($response)) {
            $this->event->parseOutput($response);
        } else {
            if($this->_state->isNew()) {
                $this->_isNew = true;
            }

            $this->loadDelegates();

            if($this->_isNew) {
                $this->setDefaultValues();
            }

            foreach($this->_delegates as $delegate) {
                $delegate->beginInitialize();
            }

            foreach($this->_delegates as $delegate) {
                $delegate->endInitialize();
            }
        }


        if($this->_state->isNew()) {
            if(($referrer = $this->http->getReferrerDirectoryRequest())
            && $referrer->matches($this->request)) {
                $referrer = null;
            }

            $this->_state->referrer = $referrer;

            $method = $this->http->getMethod();
            $this->_state->isOperating = $method != 'get' && $method != 'head';
        } else {
            $this->_state->isOperating = true;
        }

        $this->_state->isNew(false);
        $this->afterInit();
    }

    public function isNew() {
        return $this->_isNew;
    }

    public function isComplete() {
        return $this->_isComplete;
    }

    private function _createSessionId() {
        return flex\Generator::sessionId();
    }

    private function _createSessionNamespace() {
        $output = 'form://'.implode('/', $this->context->request->getLiteralPathArray());

        if(substr($output, -5) == '.ajax') {
            $output = substr($output, 0, -5);
        }

        if(null !== ($dataId = $this->getInstanceId())) {
            $output .= '#'.$dataId;
        }

        return $output;
    }

    protected function getInstanceId() {
        return null;
    }

    protected function initWithSession() {}

    public function getState() {
        if(!$this->_state) {
            throw new RuntimeException(
                'State controller is not available until the form has been dispatched'
            );
        }

        return $this->_state;
    }

    public function dispatchToRenderInline(aura\view\IView $view) {
        $this->_beforeDispatch();
        $this->view = $view;

        $this->_isRenderingInline = true;
        $method = $this->getDispatchMethodName();
        $this->{$method}();
        $this->_isRenderingInline = false;

        return $this->content;
    }






// State
    public function reset() {
        $this->_state->reset();

        foreach($this->_delegates as $id => $delegate) {
            $this->unloadDelegate($id);
        }

        $this->afterReset();

        $this->initWithSession();
        $this->loadDelegates();
        $this->setDefaultValues();

        foreach($this->_delegates as $id => $delegate) {
            $delegate->initialize();
        }

        $this->_state->isNew(false);
        $this->afterInit();

        return $this;
    }

    protected function afterReset() {}




// HTML Request
    public function handleHtmlGetRequest() {
        if($this->event->hasResponse()) {
            return $this->event->getResponse();
        }

        return $this->_renderHtmlGetRequest();
    }

    private function _renderHtmlGetRequest() {
        $setContentProvider = false;

        if(!$this->view) {
            $this->view = aura\view\Base::factory('Html', $this->context);
            $setContentProvider = true;
        }

        $this->content = new aura\view\content\WidgetContentProvider($this->view->getContext());

        if($setContentProvider) {
            $this->view->setContentProvider($this->content);
        }

        $this->content->setRenderTarget($this->view);

        foreach($this->_delegates as $delegate) {
            $delegate->setRenderContext($this->view, $this->content, $this->_isRenderingInline);
        }

        if($decorator = arch\decorator\Form::factory($this)) {
            $decorator->renderUi();
        } else if(method_exists($this, 'createUi')) {
            $this->createUi();
        } else if($this->context->application->isDevelopment()) {
            $this->content->push($this->html(
                '<p>This form handler has no ui generator - you need to implement function createUi() or override function handleGetRequest()</p>'
            ));
        }

        $this->view->getHeaders()
            ->setCacheAccess('no-cache')
            ->canStoreCache(false)
            ->shouldRevalidateCache(true);

        if($this->content instanceof aura\view\ICollapsibleContentProvider) {
            $this->content->collapse();
        }

        return $this->view;
    }

    public function handleHtmlPostRequest() {
        if($this->event->hasResponse()) {
            return $this->event->getResponse();
        }

        $this->_runPostRequest();
        $response = $this->event->getResponse();

        if(empty($response)) {
            $response = $this->http->redirect()->isAlternativeContent(true);
        }

        return $response;
    }



// JSON Request
    public function handleJsonGetRequest() {
        return $this->http->jsonResponse($this->_getJsonResponseData());
    }

    public function handleJsonPostRequest() {
        $this->_runPostRequest();
        $data = $this->_getJsonResponseData();

        if($this->event->hasRedirect()) {
            $data['redirect'] = $this->uri($result->getRedirect());
        }

        return $this->http->jsonResponse($data);
    }

    private function _getJsonResponseData() {
        return array_merge($this->getStateData(), [
            'node' => $this->context->request->getLiteralPathString(),
            'events' => $this->getAvailableEvents(),
            'defaultEvent' => static::DEFAULT_EVENT,
            'isNew' => $this->_isNew,
            'isComplete' => $this->_isComplete,
            'redirect' => $this->event->getRedirect(),
            'forceRedirect' => $this->event->shouldForceRedirect(),
            'reload' => $this->event->shouldReload()
        ]);
    }


// AJAX Request
    public function handleAjaxGetRequest() {
        return $this->http->jsonResponse($this->_getAjaxResponseData());
    }

    public function handleAjaxPostRequest() {
        if(!$this->event->hasResponse()) {
            $this->_runPostRequest();
        }

        return $this->http->jsonResponse($this->_getAjaxResponseData());
    }

    private function _getAjaxResponseData() {
        $response = $this->event->getResponse();
        $loadUi = $this->event->getEventName() !== null
            || $response === null;

        $content = null;
        $type = 'text/html';

        if($response instanceof link\http\IResponse) {
            $content = $response->getContent();
            $type = $response->getContentType();
        } else if(is_string($response)) {
            $content = $response;
        }

        if($content === null && $loadUi) {
            $content = $this->http->getAjaxViewContent($this->_renderHtmlGetRequest());
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

        return $output;
    }



    private function _runPostRequest(core\collection\ITree $postData=null) {
        if($postData === null) {
            $httpRequest = $this->context->application->getHttpRequest();
            $postData = clone $httpRequest->getPostData();
        }

        $event = null;

        if($postData->has('formEvent')) {
            $event = $postData->get('formEvent');
            $postData->remove('formEvent');
        }

        if(preg_match('/^\<[a-z]+ .*data\-button\-event\=\"([^"]+)\"/i', $event, $matches)) {
            $event = $matches[1];
        }

        if($postData->__isset('_delegates')) {
            foreach($postData->_delegates as $id => $delegateValues) {
                try {
                    $this->getDelegate($id)->values->clear()->import($delegateValues);
                } catch(DelegateException $e) {}
            }

            $postData->remove('_delegates');
        }

        $this->values->clear()->import($postData);

        if(empty($event)) {
            $event = $this->getDefaultEvent();

            if(empty($event)) {
                $event = self::DEFAULT_EVENT;
            }
        }


        $parts = explode('(', $event, 2);
        $event = array_shift($parts);
        $args = substr(array_pop($parts), 0, -1);

        if(!empty($args)) {
            $args = flex\Delimited::parse($args);
        } else {
            $args = [];
        }

        $targetId = explode('.', trim($event, '.'));
        $event = array_pop($targetId);
        $targetString = implode('.', $targetId);
        $target = $this;

        if(!empty($targetId)) {
            while(!empty($targetId)) {
                $target->handleDelegateEvent(implode('.', $targetId), $event, $args);
                $target = $target->getDelegate(array_shift($targetId));
            }
        }

        $target->handleEvent($event, $args);
        $this->triggerPostEvent($target, $event, $args);

        if($target->isComplete()) {
            $this->setComplete();

            foreach($this->_delegates as $delegate) {
                $delegate->setComplete();
            }

            if($this->_sessionNamespace) {
                $session = $this->context->getUserManager()->session->getBucket($this->_sessionNamespace);
                $session->remove($this->_state->sessionId);
            }
        }

    }


    public function setComplete() {
        $this->_isComplete = true;
        return $this;
    }

    public function getStateData() {
        $output = [
            'isValid' => $this->isValid(),
            'isNew' => $this->_isNew,
            'values' => $this->values->toArrayDelimitedSet(),
            'errors' => []
        ];

        foreach($this->_delegates as $delegate) {
            $delegateState = $delegate->getStateData();

            if(!$delegateState['isValid']) {
                $output['isValid'] = false;
            }

            $output['values'] = array_merge($output['values'], $delegateState['values']);
            $output['errors'] = array_merge($output['errors'], $delegateState['errors']);
        }

        return $output;
    }

// Names
    public function fieldName($name) {
        return $name;
    }

    public function elementId($name) {
        return flex\Text::formatSlug($name);
    }


// Events
    protected function onCancelEvent() {
        $this->setComplete();
        return $this->_getCompleteRedirect(null, false);
    }

    protected function getDefaultRedirect() {
        return static::DEFAULT_REDIRECT;
    }

    protected function getDefaultEvent() {
        return static::DEFAULT_EVENT;
    }


// Node dispatch
    public function getDispatchMethodName() {
        $method = ucfirst(strtolower($this->context->application->getHttpRequest()->getMethod()));

        if($method == 'Head') {
            $method = 'Get';
        }

        $func = 'handle'.$this->context->request->getType().$method.'Request';

        if(!method_exists($this, $func)) {
            throw new RuntimeException(
                'Form node '.$this->context->request.' does not support '.
                $this->context->application->getHttpRequest()->getMethod().' http method',
                405
            );
        }

        return $func;
    }

    protected function _afterDispatch($response) {
        if(!$this->_isComplete
        && $this->_sessionNamespace
        && $this->_state->isOperating()) {
            $session = $this->context->getUserManager()->session->getBucket($this->_sessionNamespace);
            $session->set($this->_state->sessionId, $this->_state);
        }

        return $response;
    }
}

<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\form;

use df;
use df\core;
use df\arch;
use df\aura;
use df\link;

abstract class Action extends arch\Action implements IAction {
    
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
    private $_initResponse;

    
    public function __construct(arch\IContext $context) {
        parent::__construct($context);
        
        if($this->context->getRunMode() !== 'Http') {
            throw new RuntimeException(
                'Form actions can only be used in Http run mode'
            );
        }

        $this->_onConstruct();
    }

    protected function _onConstruct() {}
    
    final protected function _beforeDispatch() {
        $response = $this->_init();
        $request = $this->context->request;
        $id = null;
        
        if(!empty($response)) {
            return $response;
        }
        
        $this->_sessionNamespace = $this->_createSessionNamespace();
        $session = $this->context->getUserManager()->getSessionNamespace($this->_sessionNamespace);
        
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
            
            $this->_state = new StateController($id);
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
        $response = $this->_onSessionReady();

        if(!empty($response)) {
            $this->_initResponse = $response;
        } else {
            if($this->_state->isNew()) {
                $this->_isNew = true;
            }
            
            $this->_setupDelegates();

            if($this->_isNew) {
                $this->_setDefaultValues();
            }
            
            foreach($this->_delegates as $delegate) {
                $delegate->initialize();
            }
        }

        if($this->_state->isNew()) {
            $this->_state->isOperating = $this->http->getMethod() != 'GET';
        } else {
            $this->_state->isOperating = true;
        }
        
        $this->_state->isNew(false);
        $this->_onInitComplete();
    }

    public function isNew() {
        return $this->_isNew;
    }

    public function isComplete() {
        return $this->_isComplete;
    }

    protected function _createSessionId() {
        return core\string\Generator::sessionId();
    }
    
    protected function _createSessionNamespace() {
        $output = 'form://'.implode('/', $this->context->request->getLiteralPathArray());

        if(substr($output, -5) == '.ajax') {
            $output = substr($output, 0, -5);
        }
        
        if(null !== ($dataId = $this->_getDataId())) {
            $output .= '#'.$dataId;
        }
        
        return $output;
    }
    
    protected function _getDataId() {
        return null;
    }
    
    protected function _onSessionReady() {}

    public function getStateController() {
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
        $method = $this->getActionMethodName();
        call_user_func_array([$this, $method], []);
        $this->_isRenderingInline = false;

        return $this->content;
    }



// HTML Request
    public function onHtmlGetRequest() {
        if($this->_initResponse) {
            return $this->_initResponse;
        }

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
        
        if(method_exists($this, '_createUi')) {
            $this->_createUi();
        } else if($this->context->application->isDevelopment()) {
            $this->content->push($this->html(
                '<p>This form handler has no ui generator - you need to implement function _createUi() or override function onGetRequest()</p>'
            ));
        }
        
        $this->view->getHeaders()
            ->setCacheAccess('no-cache')
            ->canStoreCache(false)
            ->shouldRevalidateCache(true);
        
        return $this->view;
    }

    public function onHtmlPostRequest() {
        if($this->_initResponse) {
            return $this->_initResponse;
        }

        $response = $this->_runPostRequest();

        if(empty($response)) {
            $response = $this->http->redirect()->isAlternativeContent(true);
        }
        
        return $response;
    }



// JSON Request
    public function onJsonGetRequest() {
        return $this->_newJsonResponse($this->_getJsonResponseData());
    }

    public function onJsonPostRequest() {
        $response = $this->_runPostRequest();
        $data = $this->_getJsonResponseData();

        if($response instanceof link\http\IRedirectResponse) {
            $data['redirect'] = (string)$response->getUrl();
        }

        return $this->_newJsonResponse($data);
    }

    private function _getJsonResponseData() {
        if($this->_initResponse) {
            $redirect = null;

            if($this->_initResponse instanceof link\http\IRedirectResponse) {
                $redirect = clone $this->_initResponse->getUrl();
                $redirect->path->setExtension('json');
                $redirect = (string)$redirect;
            }

            return [
                'values' => [],
                'errors' => [],
                'events' => [],
                'defaultEvent' => static::DEFAULT_EVENT,
                'isNew' => $this->_isNew,
                'isComplete' => $this->_isComplete,
                'redirect' => $redirect
            ];
        }

        return array_merge($this->getStateData(), [
            'action' => $this->context->request->getLiteralPathString(),
            'events' => $this->getAvailableEvents(),
            'defaultEvent' => static::DEFAULT_EVENT,
            'isNew' => $this->_isNew,
            'isComplete' => $this->_isComplete,
            'redirect' => null
        ]);
    }

    private function _newJsonResponse($json) {
        return $this->http->stringResponse($this->data->jsonEncode($json), 'application/json');
    }


// AJAX Request
    public function onAjaxGetRequest() {
        if($this->_initResponse) {
            $response = $this->_initResponse;
        } else {
            $response = null;
        }

        return $this->_newJsonResponse($this->_getAjaxResponseData($response, $response === null));
    }

    public function onAjaxPostRequest() {
        if($this->_initResponse) {
            $response = $this->_initResponse;
        } else {
            $response = $this->_runPostRequest();
        }

        return $this->_newJsonResponse($this->_getAjaxResponseData($response, true));
    }

    private function _getAjaxResponseData($response, $loadUi=false) {
        $content = $redirect = null;
        $type = 'text/html';

        if($response instanceof link\http\IRedirectResponse) {
            $redirect = clone $response->getUrl();
            $redirect->path->setExtension('ajax');
            $redirect = (string)$redirect;
        } else if($response instanceof link\http\IResponse) {
            $content = $response->getContent();
            $type = $response->getContentType();
        } else if(is_string($response)) {
            $content = $response;
        }

        if($content === null && $loadUi) {
            $content = $this->http->getAjaxViewContent($this->onHtmlGetRequest());
        }

        return [
            'action' => $this->context->request->getLiteralPathString(),
            'content' => $content,
            'type' => $type,
            'redirect' => $redirect,
            'isNew' => $this->_isNew,
            'isComplete' => $this->_isComplete
        ];
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
                } catch(DelegateException $e) {
                    if($this->context->application->isDevelopment()) {
                        throw $e;
                    }
                }
            }
            
            $postData->remove('_delegates');
        }

        $this->values->clear()->import($postData);
        
        if(empty($event)) {
            $event = $this->_getDefaultEvent();
            
            if(empty($event)) {
                $event = self::DEFAULT_EVENT;
            }
        }
        
        
        $parts = explode('(', $event, 2);
        $event = array_shift($parts);
        $args = substr(array_pop($parts), 0, -1);
        
        if(!empty($args)) {
            $args = core\string\Util::parseDelimited($args);
        } else {
            $args = [];
        }
        
        $targetId = explode('.', trim($event, '.'));
        $event = array_pop($targetId);
        $target = $this;
        
        if(!empty($targetId)) {
            while(!empty($targetId)) {
                $target->handleDelegateEvent(implode('.', $targetId), $event, $args);
                $target = $target->getDelegate(array_shift($targetId));
            }
        }
        
        $output = $target->handleEvent($event, $args);

        if($target->isComplete()) {
            $this->_finalizeCompletion();
        }

        return $output;
    }

    public function complete($defaultRedirect=null, $success=true) {
        if($defaultRedirect === null) {
            $defaultRedirect = $this->_getDefaultRedirect();
        }
        
        $this->_isComplete = true;
        
        if($this->request->getType() == 'Html') {
            return $this->http->defaultRedirect($defaultRedirect, $success);
        } else if($defaultRedirect) {
            return $this->http->redirect($defaultRedirect);
        }
    }

    protected function _finalizeCompletion($success=true) {
        $this->_isComplete = true;
        
        foreach($this->_delegates as $delegate) {
            $delegate->setComplete($success);
        }

        if($this->_sessionNamespace) {
            $session = $this->context->getUserManager()->getSessionNamespace($this->_sessionNamespace);
            $session->remove($this->_state->sessionId);
        }
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
        return core\string\Manipulator::formatSlug($name);   
    }
    
    
// Events
    protected function _onCancelEvent() {
        $redirect = $this->_getDefaultRedirect();

        //if(!$redirect && in_array($this->request->getType(), ['Json', 'Ajax'])) {
            //$redirect = $this->request;
        //}

        return $this->complete($redirect, false);
    }

    protected function _getDefaultRedirect() {
        return static::DEFAULT_REDIRECT;
    }

    protected function _getDefaultEvent() {
        return static::DEFAULT_EVENT;
    }


// Action dispatch
    public function getActionMethodName() {
        $method = ucfirst(strtolower($this->context->application->getHttpRequest()->getMethod()));
        $func = 'on'.$this->context->request->getType().$method.'Request';
        
        if(!method_exists($this, $func)) {
            throw new RuntimeException(
                'Form action '.$this->context->request.' does not support '.
                $this->context->application->getHttpRequest()->getMethod().' http method',
                405
            );
        }
        
        return $func;
    }
    
    protected function _afterDispatch($response) {
        if(!$this->_isComplete 
        && $this->_sessionNamespace
        && $this->_state->isOperating) {
            $session = $this->context->getUserManager()->getSessionNamespace($this->_sessionNamespace);
            $session->set($this->_state->sessionId, $this->_state);
        }
        
        return $response;
    }
}

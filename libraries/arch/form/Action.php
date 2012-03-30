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
use df\halo;

abstract class Action extends arch\Action implements IAction {
    
    use TBase;
    
    const SESSION_ID_KEY = 'fsid';
    const MAX_SESSIONS = 15;
    const SESSION_PRUNE_THRESHOLD = 5400; // 1.5 hrs
    const SESSION_AUTO_RESUME = true;
    
    const DEFAULT_EVENT = 'default';
    const DEFAULT_REDIRECT = null;
    
    private $_isNew = false;
    private $_isComplete = false;
    private $_sessionNamespace;
    
    public function __construct(arch\IContext $context, arch\IController $controller=null) {
        parent::__construct($context, $controller);
        
        if($this->_context->getRunMode() !== 'Http') {
            throw new RuntimeException(
                'Form actions can only be used in Http run mode'
            );
        }
    }
    
    final protected function _beforeDispatch() {
        $response = $this->_init();
        $request = $this->_context->getRequest();
        $id = null;
        
        if(!empty($response)) {
            return $response;
        }
        
        $this->_sessionNamespace = $this->_createSessionNamespace();
        $session = $this->_context->getUserManager()->getSessionNamespace($this->_sessionNamespace);
        
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
                
                $this->_context->getRequest()->getQuery()->set(
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
                // TODO: send notification
                $session->prune(static::SESSION_PRUNE_THRESHOLD);
                $session->remove(array_shift($keys));
            }
        }
        
        $this->values = $this->_state->getValues();
        
        $this->_onSessionCreate();
        $this->_setupDelegates();
        
        if($this->_state->isNew()) {
            $this->_setDefaultValues();
        }
        
        foreach($this->_delegates as $delegate) {
            $delegate->initialize();
        }
        
        $this->_state->isNew(false);
    }
    
    protected function _createSessionId() {
        return core\string\Generator::sessionId();
    }
    
    protected function _createSessionNamespace() {
        $output = 'form://'.implode('/', $this->_context->getRequest()->getLiteralPathArray());
        
        if(null !== ($dataId = $this->_getDataId())) {
            $output .= '#'.$dataId;
        }
        
        return $output;
    }
    
    protected function _getDataId() {
        return null;
    }
    
    protected function _onSessionCreate() {}
    
    public function getStateController() {
        if(!$this->_state) {
            throw new RuntimeException(
                'State controller is not available until the form has been dispatched'
            );
        }
        
        return $this->_state;
    }
    
    public function onGetRequest() {
        $this->view = aura\view\Base::factory('Html', $this->_context);
        $this->content = new aura\view\content\WidgetContentProvider($this->view->getContext());
        $this->html = $this->view->getHelper('html');
        $this->view->setContentProvider($this->content);
        $this->content->setRenderTarget($this->view);
        
        foreach($this->_delegates as $delegate) {
            $delegate->setRenderContext($this->view, $this->content);
        }
        
        if(method_exists($this, '_createUi')) {
            $this->_createUi();
        } else if($this->_context->getApplication()->isDevelopment()) {
            $this->content->push($this->html->string(
                '<p>This form handler has no ui generator - you need to implement function _createUi() or override function onGetRequest()</p>'
            ));
        }
        
        $this->view->getHeaders()
            ->setCacheAccess('no-cache')
            ->canStoreCache(false)
            ->shouldRevalidateCache(true);
        
        return $this->view;
    }
    
    public function onPostRequest() {
        $httpRequest = $this->_context->getApplication()->getHttpRequest();
        $postData = clone $httpRequest->getPostData();
        
        $event = null;
        
        if($postData->has('formEvent')) {
            $event = $postData->get('formEvent');
            $postData->remove('formEvent');
        }
        
        if($postData->__isset('_delegates')) {
            foreach($postData->_delegates as $id => $delegateValues) {
                try {
                    $this->getDelegate($id)->values->clear()->import($delegateValues);
                } catch(DelegateException $e) {
                    if($this->_context->getApplication()->isDevelopment()) {
                        core\dump($e);
                    }
                }
            }
            
            $postData->remove('_delegates');
        }

        $this->values->clear()->import($postData);
        
        if(empty($event)) {
            $event = static::DEFAULT_EVENT;
            
            if(empty($event)) {
                $event = 'default';
            }
        }
        
        
        $parts = explode('(', $event, 2);
        $event = array_shift($parts);
        $args = substr(array_pop($parts), 0, -1);
        
        if(!empty($args)) {
            $args = core\string\Util::parseDelimited($args);
        } else {
            $args = array();
        }
        
        $targetId = explode('.', trim($event, '.'));
        $event = array_pop($targetId);
        $target = $this;
        
        if(!empty($targetId)) {
            $target = $this->getDelegate($targetId);
        }
        
        $response = $target->handleEvent($event, $args);
        
        if(empty($response)) {
            $response = $this->http->redirect()->isAlternativeContent(true);
        }
        
        return $response;
    }

    public function complete($defaultRedirect=null, $success=true) {
        $this->_isComplete = true;
        
        if($this->_sessionNamespace) {
            $session = $this->_context->getUserManager()->getSessionNamespace($this->_sessionNamespace);
            $session->remove($this->_state->getSessionId());
        }
        
        return $this->http->defaultRedirect($defaultRedirect, $success);
    }
    
    
    
// Events
    protected function _onCancelEvent() {
        return $this->complete(static::DEFAULT_REDIRECT, false);
    }
    
    
// Action dispatch
    protected function _getActionMethod() {
        $method = ucfirst(strtolower($this->_context->getApplication()->getHttpRequest()->getMethod()));
        $func = 'on'.$method.'Request';
        
        if(!method_exists($this, $func)) {
            throw new RuntimeException(
                'Form action '.$this->_context->getRequest().' does not support '.
                $this->_context->getApplication()->getHttpRequest()->getMethod().' http method',
                405
            );
        }
        
        return $func;
    }
    
    protected function _afterDispatch($response) {
        // TODO: check state has changed
        
        if(!$this->_isComplete && $this->_sessionNamespace) {
            $session = $this->_context->getUserManager()->getSessionNamespace($this->_sessionNamespace);
            $session->set($this->_state->getSessionId(), $this->_state);
        }
        
        return $response;
    }
}

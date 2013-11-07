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

    
    public function __construct(arch\IContext $context, arch\IController $controller=null) {
        parent::__construct($context, $controller);
        
        if($this->_context->getRunMode() !== 'Http') {
            throw new RuntimeException(
                'Form actions can only be used in Http run mode'
            );
        }

        $this->_onConstruct();
    }

    protected function _onConstruct() {}
    
    final protected function _beforeDispatch() {
        $response = $this->_init();
        $request = $this->_context->request;
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
                $this->_context->comms->flash(
                    'form.session.prune',
                    $this->_context->_('The maximum form session threshold has been reached'),
                    'debug'
                );


                $session->prune(static::SESSION_PRUNE_THRESHOLD);
                $session->remove(array_shift($keys));
            }
        }
        
        $this->values = $this->_state->getValues();
        
        $response = $this->_onSessionCreate();

        if(!empty($response)) {
            return $response;
        }

        $this->_setupDelegates();

        if($this->_state->isNew()) {
            $this->_isNew = true;
            $this->_setDefaultValues();
        }
        
        foreach($this->_delegates as $delegate) {
            $delegate->initialize();
        }
        
        $this->_state->isNew(false);
        $this->_onInitComplete();
    }

    public function isNew() {
        return $this->_isNew;
    }

    protected function _createSessionId() {
        return core\string\Generator::sessionId();
    }
    
    protected function _createSessionNamespace() {
        $output = 'form://'.implode('/', $this->_context->request->getLiteralPathArray());
        
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

    public function dispatchToRenderInline(aura\view\IView $view) {
        $this->_beforeDispatch();
        $this->view = $view;
        
        $this->_isRenderingInline = true;
        $method = self::getActionMethodName($this, $this->context);
        call_user_method($method, $this);
        $this->_isRenderingInline = false;

        return $this->content;
    }



// HTML Request
    public function onHtmlGetRequest() {
        $setContentProvider = false;

        if(!$this->view) {
            $this->view = aura\view\Base::factory('Html', $this->_context);
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

    public function onHtmlPostRequest() {
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

        if($response instanceof halo\protocol\http\IRedirectResponse) {
            $data['redirect'] = (string)$response->getUrl();
        }

        return $this->_newJsonResponse($data);
    }

    private function _getJsonResponseData() {
        return array_merge($this->getStateData(), [
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
        return $this->_newJsonResponse([
            'content' => $this->_getAjaxResponseContent(),
            'type' => 'text/html',
            'redirect' => null,
            'isNew' => $this->_isNew,
            'isComplete' => $this->_isComplete
        ]);
    }

    public function onAjaxPostRequest() {
        $response = $this->_runPostRequest();
        $content = $redirect = null;
        $type = 'text/html';


        if($response instanceof halo\protocol\http\IRedirectResponse) {
            $redirect = (string)$response->getUrl();
        } else if($response instanceof halo\protocol\http\response\IResponse) {
            $content = $response->getContent();
            $type = $response->getContentType();
        } else if(is_string($response)) {
            $content = $response;
        }

        if($content === null) {
            $content = $this->_getAjaxResponseContent();
        }

        return $this->_newJsonResponse([
            'content' => $content,
            'type' => $type,
            'redirect' => $redirect,
            'isNew' => $this->_isNew,
            'isComplete' => $this->_isComplete
        ]);
    }

    private function _getAjaxResponseContent() {
        $view = $this->onHtmlGetRequest();
        $output = $this->html->flashList();

        if($output) {
            $output .= "\n";
        }

        $output .= (string)$view->getContentProvider();
        return $output;
    }


    
    
    private function _runPostRequest(core\collection\ITree $postData=null) {
        if($postData === null) {
            $httpRequest = $this->_context->getApplication()->getHttpRequest();
            $postData = clone $httpRequest->getPostData();
        }

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
                        throw $e;
                    }
                }
            }
            
            $postData->remove('_delegates');
        }

        $this->values->clear()->import($postData);
        
        if(empty($event)) {
            $event = static::DEFAULT_EVENT;
            
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
            $args = array();
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
        
        return $target->handleEvent($event, $args);
    }

    public function complete($defaultRedirect=null, $success=true) {
        if($defaultRedirect === null) {
            $defaultRedirect = $this->_getDefaultRedirect();
        }
        
        $this->_isComplete = true;

        foreach($this->_delegates as $delegate) {
            $delegate->complete($success);
        }

        if($this->_sessionNamespace) {
            $session = $this->_context->getUserManager()->getSessionNamespace($this->_sessionNamespace);
            $session->remove($this->_state->getSessionId());
        }
        
        if($this->request->getType() == 'Html') {
            return $this->http->defaultRedirect($defaultRedirect, $success);
        } else if($defaultRedirect) {
            return $this->http->redirect($defaultRedirect);
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
        return $this->complete($this->_getDefaultRedirect(), false);
    }

    protected function _getDefaultRedirect() {
        return static::DEFAULT_REDIRECT;
    }


// Action dispatch
    public static function getActionMethodName($actionClass, arch\IContext $context) {
        $method = ucfirst(strtolower($context->getApplication()->getHttpRequest()->getMethod()));
        $func = 'on'.$context->request->getType().$method.'Request';
        
        if(!method_exists($actionClass, $func)) {
            throw new RuntimeException(
                'Form action '.$context->request.' does not support '.
                $context->getApplication()->getHttpRequest()->getMethod().' http method',
                405
            );
        }
        
        return $func;
    }
    
    protected function _afterDispatch($response) {
        if(!$this->_isComplete && $this->_sessionNamespace) {
            $session = $this->_context->getUserManager()->getSessionNamespace($this->_sessionNamespace);
            $session->set($this->_state->getSessionId(), $this->_state);
        }
        
        return $response;
    }
}

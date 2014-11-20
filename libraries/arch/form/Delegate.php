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

class Delegate implements IDelegate {
    
    use core\TContextAware;
    use TForm;

    const DEFAULT_REDIRECT = null;
    
    protected $_delegateId;
    private $_isNew = false;
    private $_isComplete = false;

    public function __construct(arch\IContext $context, IStateController $state, $id) {
        $this->context = $context;
        $this->_state = $state;
        $this->_delegateId = $id;

        $this->values = $state->getValues();
        $this->_onConstruct();
    }

    protected function _onConstruct() {}
    
    public function getDelegateId() {
        return $this->_delegateId;
    }

    public function getDelegateKey() {
        $parts = explode('.', $this->_delegateId);
        return array_pop($parts);
    }

    final public function initialize() {
        $this->_init();
        $this->_setupDelegates();

        if($this->_state->isNew()) {
            $this->_isNew = true;
            $this->_setDefaultValues();
        }
        
        foreach($this->_delegates as $delegate) {
            $delegate->initialize();
        }
        
        $this->_onInitComplete();
        return $this;
    }

    public function isNew() {
        return $this->_isNew;
    }
    
    public function setRenderContext(aura\view\IView $view, aura\view\IContentProvider $content, $isRenderingInline=false) {
        $this->view = $view;
        $this->content = $content;
        $this->_isRenderingInline = $isRenderingInline;
        
        foreach($this->_delegates as $delegate) {
            $delegate->setRenderContext($view, $content);
        }
        
        return $this;
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
    
    public function setComplete($success=true) {
        $this->_onComplete($success);

        foreach($this->_delegates as $delegate) {
            $delegate->setComplete($success);
        }
        
        $this->_state->reset();
        return $this;
    }

    public function isComplete() {
        return $this->_isComplete;
    }

    protected function _onComplete($success) {}

    protected function _getDefaultRedirect() {
        return static::DEFAULT_REDIRECT;
    }


    protected function _onCancelEvent() {
        $redirect = $this->_getDefaultRedirect();
        return $this->complete($redirect, false);
    }


// Names
    public function fieldName($name) {
        $parts = explode('[', $name, 2);
        $parts[0] .= ']';
        
        return '_delegates['.$this->_delegateId.']['.implode('[', $parts);
    }

    public function elementId($name) {
        return core\string\Manipulator::formatSlug($this->getDelegateId().'-'.$name);   
    }



    public function getStateData() {
        $output = [
            'isValid' => $this->isValid(),
            'isNew' => $this->_isNew,
            'values' => $this->values->toArrayDelimitedSet('_delegates['.$this->_delegateId.']'),
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
}
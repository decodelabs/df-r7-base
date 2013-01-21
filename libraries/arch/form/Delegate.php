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
    
    use TBase;
    use arch\TContextProxy;
    
    protected $_delegateId;
    
    public function __construct(arch\IContext $context, IStateController $state, $id) {
        $this->_context = $context;
        $this->_state = $state;
        $this->_delegateId = $id;
        
        $this->values = $state->getValues();
        $this->_onConstruct();
    }

    protected function _onConstruct() {}
    
    final public function initialize() {
        $this->_init();
        $this->_setupDelegates();

        if($this->_state->isNew()) {
            $this->_setDefaultValues();
        }
        
        foreach($this->_delegates as $delegate) {
            $delegate->initialize();
        }
        
        return $this;
    }
    
    public function setRenderContext(aura\view\IView $view, aura\view\IContentProvider $content) {
        $this->view = $view;
        $this->content = $content;
        $this->html = $view->getHelper('html');
        
        foreach($this->_delegates as $delegate) {
            $delegate->setRenderContext($view, $content);
        }
        
        return $this;
    }
    
    
// Names
    public function fieldName($name) {
        $parts = explode('[', $name, 2);
        $parts[0] .= ']';
        
        return '_delegates['.$this->_delegateId.']['.implode('[', $parts);
    }
}
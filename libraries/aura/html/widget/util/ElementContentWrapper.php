<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget\util;

use df;
use df\core;
use df\aura;

class ElementContentWrapper implements aura\html\widget\IElementContentWrapper {
    
    protected $_tagContent;
    protected $_widget;
    
    public function __construct(aura\html\widget\IWidget $widget, aura\html\IElementContent $tagContent) {
        $this->_widget = $widget;
        $this->_tagContent = $tagContent;
    }
    
    public function getView() {
        return $this->_width->getView();
    }

    public function __call($method, $args) {
        $output = call_user_func_array([$this->_tagContent, $method], $args);
        
        if($output === $this->_tagContent) {
            return $this;
        }
        
        return $output;
    }
    
    public function count() {
        return $this->_tagContent->count();
    }
    
    public function offsetSet($offset, $value) {
        $this->_tagContent->offsetSet($offset, $value);
        return $this;
    }
    
    public function offsetGet($offset) {
        return $this->_tagContent->offsetGet($offset);
    }
    
    public function offsetExists($offset) {
        return $this->_tagContent->offsetExists($offset);
    }
    
    public function offsetUnset($offset) {
        $this->_tagContent->offsetUnset($offset);
        return $this;
    }
    
    public function end() {
        return $this->_widget;
    }
    
    public function setRenderTarget(aura\view\IRenderTarget $target=null) {
        $this->_widget->setRenderTarget($target);
        return $this;
    }
    
    public function getRenderTarget() {
        return $this->_widget->getRenderTarget();
    }
    
    public function renderTo(aura\view\IRenderTarget $target) {
        return $this->_widget->renderTo($target);
    }

    public function render() {
        return $this->_widget->render();
    }
    
    public function __toString() {
        return $this->_widget->__toString();
    }
    
    public function toString() {
        return $this->_widget->toString();
    }
}

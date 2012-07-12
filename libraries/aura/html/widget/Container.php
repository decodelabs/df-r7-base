<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use df;
use df\core;
use df\aura;
use df\arch;

class Container extends Base implements IContainerWidget, IWidgetShortcutProvider, core\IDumpable {
    
    protected $_children;
    protected $_context;
    
    public function __construct(arch\IContext $context, $input=null) {
        $this->_context = $context;
        
        if($input !== null && !is_array($input) && !$input instanceof aura\html\IElementContent) {
            $input = func_get_args();
        }
        
        if(!$input instanceof aura\html\IElementContent) {
            $input = new aura\html\ElementContent($input);
        }
        
        $this->_children = $input;
    }
    
    protected function _render() {
        if($this->_children->isEmpty()) {
            return '';
        }
        
        return $this->getTag()->renderWith($this->_children, true);
    }
    
    public function import($input) {
        $this->_children->import($input);
        return $this;
    }
    
    public function toArray() {
        return $this->_children->toArray();
    }
    
    public function isEmpty() {
        return $this->_children->isEmpty();
    }
    
    public function clear() {
        $this->_children->clear();
        return $this;
    }
    
    public function set($index, $value) {
        $this->_children->set($index, $value);
        return $this;
    }
    
    public function put($index, $value) {
        $this->_children->put($index, $value);
        return $this;
    }
    
    public function get($index, $default=null) {
        return $this->_children->get($index, $default);
    }
    
    public function has($index) {
        return $this->_children->has($index);
    }
    
    public function remove($index) {
        $this->_children->remove($index);
        return $this;
    }
    
    public function getNext() {
        return $this->_children->getNext();
    }
    
    public function getPrev() {
        return $this->_children->getPrev();
    }
    
    public function getFirst() {
        return $this->_children->getFirst();
    }
    
    public function getLast() {
        return $this->_children->getLast();
    }
    
    public function getCurrent() {
        return $this->_children->getCurrent();
    }
    
    public function seekFirst() {
        return $this->_children->seekFirst();
    }
    
    public function seekNext() {
        return $this->_children->seekNext();
    }
    
    public function seekPrev() {
        return $this->_children->seekPrev();
    }
    
    public function seekLast() {
        return $this->_children->seekLast();
    }
    
    public function hasSeekEnded() {
        return $this->_children->hasSeekEnded();
    }
    
    public function getSeekPosition() {
        return $this->_children->getSeekPosition();
    }
    
    public function extract() {
        return $this->_children->extract();
    }
    
    public function extractList($count) {
        return $this->_children->extractList($count);
    }
    
    public function insert($value) {
        $this->_children->insert($value);
        return $this;
    }
    
    public function pop() {
        return $this->_children->pop();
    }
    
    public function push($value) {
        call_user_func_array(array($this->_children, 'push'), func_get_args());
        return $this;
    }
    
    public function shift() {
        return $this->_children->shift();
    }
    
    public function unshift($value) {
        call_user_func_array(array($this->_children, 'unshift'), func_get_args());
        return $this;
    }
    
    
    public function count() {
        return $this->_children->count();
    }
    
    public function getIterator() {
        return $this->_children->getIterator();
    }
    
    public function offsetSet($index, $value) {
        $this->_children->offsetSet($index, $value);
        return $this;
    }
    
    public function offsetGet($index) {
        return $this->_children->offsetGet($index);
    }
    
    public function offsetExists($index) {
        return $this->_children->offsetExists($index);
    }
    
    public function offsetUnset($index) {
        $this->_children->offsetUnset($index);
        return $this;
    }
    
    
    
// Widget shortcuts
    public function __call($method, array $args) {
        $add = false;

        if(substr($method, 0, 3) == 'add') {
            $add = true;
            $method = substr($method, 3);
        }
        
        $widget = Base::factory($this->_context, $method, $args)->setRenderTarget($this->_renderTarget);
        
        if($add) {
            $this->push($widget);
        }
        
        return $widget;
    }
    
    
// Dump
    public function getDumpProperties() {
        return [
            'children' => $this->_children,
            'tag' => $this->getTag(),
            'renderTarget' => $this->_getRenderTargetDisplayName()
        ];
    }
}

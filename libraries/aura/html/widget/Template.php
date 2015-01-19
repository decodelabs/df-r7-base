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

class Template extends Base implements aura\view\IContentProvider, \ArrayAccess, core\IDumpable {
    
    protected $_path;
    protected $_slots = [];
    
    public function __construct(arch\IContext $context, $path, array $slots=null) {
        $this->setPath($path);

        if($slots !== null) {
            $this->setSlots($slots);
        }
    }

    public function toResponse() {
        return $this->_loadTemplate();
    }
    
    protected function _loadTemplate() {
        $view = $this->getRenderTarget()->getView();
        return $view->apex->template($this->_path, $this->_slots);
    }

    protected function _render() {
        $tag = $this->getTag();
        $template = $this->_loadTemplate();

        $output = new aura\html\ElementString(
            $template->renderTo($this->getRenderTarget())
        );

        if($tag->countAttributes() <= 1 && $tag->countClasses() == 1) {
            return $output;
        }
        
        return $tag->renderWith($output);
    }
    
    
// Path
    public function setPath($path) {
        $this->_path = $path;
        return $this;
    }
    
    public function getPath() {
        return $this->_path;
    }
    
    
// Slots
    public function setSlot($name, $value) {
        $this->_slots[$name] = $value;
        return $this;
    }
    
    public function getSlot($name, $default=null) {
        if(array_key_exists($name, $this->_slots)) {
            return $this->_slots[$name];
        }
        
        return $default;
    }
    
    public function hasSlot($name) {
        return array_key_exists($name, $this->_slots);
    }
    
    public function removeSlot($name) {
        unset($this->_slots[$name]);
        return $this;
    }
    
    public function setSlots(array $args) {
        $this->_slots = [];
        return $this->addSlots($args);
    }
    
    public function addSlots(array $args) {
        $this->_slots = array_merge($this->_slots, $args);
        return $this;
    }
    
    public function getSlots(array $add=[]) {
        $output = $this->_slots;
        
        foreach($add as $key => $var) {
            $output[$key] = $var;
        }
        
        return $output;
    }
    
    public function offsetSet($name, $value) {
        return $this->setSlot($name, $value);
    }
    
    public function offsetGet($name) {
        return $this->getSlot($name);
    }
    
    public function offsetExists($name) {
        return $this->hasSlot($name);
    }
    
    public function offsetUnset($name) {
        return $this->removeSlot($name);
    }
    
    
// Dump
    public function getDumpProperties() {
        return [
            'path' => $this->_path,
            'slots' => count($this->_slots),
            'tag' => $this->getTag(),
            'renderTarget' => $this->_getRenderTargetDisplayName()
        ];
    }
}

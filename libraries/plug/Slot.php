<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\plug;

use df;
use df\core;
use df\plug;
use df\arch;
use df\aura as auraLib;
    
class Slot implements auraLib\view\IHelper, \ArrayAccess {
    
    use auraLib\view\THelper;

    protected $_slots = [];

    public function startCapture() {
        ob_start();
        return $this;
    }

    public function endCapture($id) {
        return $this->set($id, ob_get_clean());
    }

    public function set($id, $value) {
        $this->_slots[$id] = auraLib\view\content\SlotRenderer::factoryArgs(array_slice(func_get_args(), 1));
        return $this;
    }

    public function setArgs($id, array $args) {
        $this->_slots[$id] = auraLib\view\content\SlotRenderer::factoryArgs($args);
        return $this;
    }

    public function get($id, $default=null) {
        if(!isset($this->_slots[$id])) {
            if($default !== null) {
                call_user_func_array([$this, 'set'], func_get_args());
            } else {
                return null;
            }
        }

        return $this->_slots[$id];
    }

    public function getValue($id, $default=null) {
        if($slot = $this->get($id, $default)) {
            return $slot->getValue();
        }

        return $default;
    }

    public function has($id) {
        return isset($this->_slots[$id]);
    }

    public function render($id, $default=null) {
        if(!$renderer = $this->get($id, $default)) {
            return '';
        }

        return $renderer->renderTo($this->_view);
    }


// Array access
    public function offsetSet($key, $value) {
        return $this->set($key, $value);
    }

    public function offsetGet($key) {
        return $this->getValue($key);
    }

    public function offsetExists($key) {
        return isset($this->_slots[$key]);
    }

    public function offsetUnset($key) {
        unset($this->_slots[$key]);
        return $this;
    }
}
<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\plug\view;

use df;
use df\core;
use df\plug;
use df\arch;
use df\aura;
    
class Slot implements aura\view\IHelper {
    
    use aura\view\THelper;

    public function set($id, $value) {
        $this->_slots[$id] = aura\view\content\SlotRenderer::factoryArgs(array_slice(func_get_args(), 1));
        return $this;
    }

    public function setArgs($id, array $args) {
        $this->_slots[$id] = aura\view\content\SlotRenderer::factoryArgs($args);
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

    public function render($id, $default=null) {
        if(!$renderer = $this->get($id, $default)) {
            return '';
        }

        return $renderer->renderTo($this->_view);
    }
}
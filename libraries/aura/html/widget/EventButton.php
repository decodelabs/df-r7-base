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

class EventButton extends Button {
    
    const BUTTON_TYPE = 'submit';
    
    public function __construct(arch\IContext $context, $event, $body=null) {
        parent::__construct($context, 'formEvent', $body, $event);
    }
    
    protected function _render() {
        if($this->_body->isEmpty()) {
            $this->_body->push(core\string\Manipulator::formatName($this->getEvent()));
        }
        
        return parent::_render();
    }
    
    public function setEvent($event) {
        return $this->setValue($event);
    }
    
    public function getEvent() {
        return $this->getValue();
    }

    public function setValue($event) {
        if($event == 'cancel'
        || $event == 'reset') {
            $this->shouldValidate(false);
        }


        return parent::setValue($event);
    }
}

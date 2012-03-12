<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use df;
use df\core;
use df\aura;

class Label extends Base implements ILabelWidget, core\IDumpable {
    
    use TWidget_BodyContentAware;
    
    const PRIMARY_TAG = 'label';
    
    protected $_inputId;
    
    public function __construct($body, $inputId=null) {
        $this->setInputId($inputId);
        $this->setBody($body);
    }
    
    
    protected function _render() {
        $tag = $this->getTag();
        
        if($this->_body->isEmpty()) {
            //$body = new aura\html\ElementString('&nbsp;');
            $tag->addClass('state-empty');
        }
        
        $body = $this->_body->toString();
        
        if($this->_inputId !== null) {
            $tag->setAttribute('for', $this->_inputId);
        }
        
        return $tag->renderWith($body);
    }
    
    public function setInputId($inputId) {
        if($inputId instanceof IWidget) {
            $inputId = $inputId->getId();
        }
        
        $this->_inputId = $inputId;
        return $this;
    }
    
    public function getInputId() {
        return $this->_inputId;
    }
    
    
// Dump
    public function getDumpProperties() {
        return [
            'for' => $this->_inputId,
            'body' => $this->_body,
            'tag' => $this->getTag(),
            'renderTarget' => $this->_getRenderTargetDisplayName()
        ];
    }
}
    
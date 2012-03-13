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

class Button extends Base implements IButtonWidget, core\IDumpable {
    
    use TWidget_FormData;
    use TWidget_Input;
    use TWidget_BodyContentAware;
    
    const PRIMARY_TAG = 'button';
    const ARRAY_INPUT = false;
    const BUTTON_TYPE = 'button';
    const HAS_VALUE = true;
    
    protected $_shouldValidate = true;
    
    public function __construct($name, $body=null, $value=null) {
        $this->setName($name);
        $this->setValue($value);
        $this->setBody($body);
    }
    
    protected function _render() {
        $tag = $this->getTag();
        $tag->setAttribute('type', static::BUTTON_TYPE);
        
        $this->_applyFormDataAttributes($tag, static::HAS_VALUE);
        $this->_applyInputAttributes($tag);
        
        if(!$this->_shouldValidate) {
            $tag->setAttribute('formnovalidate', 'formnovalidate');
        }
        
        return $tag->renderWith(
            $this->_body//.'<span class="ievalue">'.$this->esc($this->getValueString()).'</span>'
        );
    }
    
    
// Validate
    public function shouldValidate($flag=null) {
        if($flag !== null) {
            $this->_shouldValidate = (bool)$flag;
            return $this;
        }    
        
        return $this->_shouldValidate;
    }
    
    
// Dump
    public function getDumpProperties() {
        return [
            'name' => $this->_name,
            'value' => $this->_value,
            'body' => $this->_body,
            'tag' => $this->getTag(),
            'renderTarget' => $this->_getRenderTargetDisplayName()
        ];
    }
}

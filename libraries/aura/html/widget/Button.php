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
    use TWidget_FocusableInput;
    use TWidget_BodyContentAware;
    use TWidget_DispositionAware;
    
    const PRIMARY_TAG = 'button';
    const ARRAY_INPUT = false;
    const BUTTON_TYPE = 'button';
    const HAS_VALUE = true;
    
    protected $_shouldValidate = true;
    protected $_icon;
    
    public function __construct(arch\IContext $context, $name, $body=null, $value=null) {
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

        if($this->_disposition !== null) {
            $tag->addClass('disposition-'.$this->getDispositionString());
        }

        $icon = null;

        if($this->_icon) {
            $icon = $this->_renderTarget->getView()->html->icon($this->_icon);
        }
        
        return $tag->renderWith(
            [$icon, $this->_body/*, '<span class="ievalue">'.$this->esc($this->getValueString()).'</span>'*/]
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


// Icon
    public function setIcon($icon) {
        $this->_icon = $icon;
        return $this;
    }

    public function getIcon() {
        return $this->_icon;
    }
    
    
// Dump
    public function getDumpProperties() {
        return [
            'name' => $this->_name,
            'value' => $this->_value,
            'body' => $this->_body,
            'icon' => $this->_icon,
            'tag' => $this->getTag(),
            'disposition' => $this->getDispositionString(),
            'renderTarget' => $this->_getRenderTargetDisplayName()
        ];
    }
}

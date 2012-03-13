<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use df;
use df\core;
use df\aura;

class RadioButton extends Base implements ICheckInputWidget, core\IDumpable {
    
    use TWidget_BodyContentAware;
    use TWidget_FormData;
    use TWidget_Input;
    use TWidget_FocusableInput;
    use TWidget_CheckInput;
    
    const PRIMARY_TAG = 'input';
    const ARRAY_INPUT = false;
    const INPUT_TYPE = 'radio';
    
    public function __construct($name, $isChecked=false, $body=null, $value='1') {
        $this->setName($name);
        $this->setValue($value);
        $this->isChecked($isChecked);
        $this->setBody($body);
    }
    
    protected function _render() {
        $tag = $this->getTag();
        
        $tag->setAttribute('type', static::INPUT_TYPE);
        
        $this->_applyFormDataAttributes($tag);
        $this->_applyInputAttributes($tag);
        $this->_applyFocusableInputAttributes($tag);
        $this->_applyCheckInputAttributes($tag);
        
        $output = $tag;
        
        if(!$this->_body->isEmpty()) {
            $output = new aura\html\Element('label', $this->_body);
            $output->unshift($tag, ' ');
        }
        
        return $output;
    }
}

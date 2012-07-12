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

class NumberTextbox extends Base implements IRangeEntryWidget, core\IDumpable {
    
    use TWidget_FormData;
    use TWidget_Input;
    use TWidget_VisualInput;
    use TWidget_FocusableInput;
    use TWidget_DataListEntry;
    use TWidget_RangeEntry;
    
    const PRIMARY_TAG = 'input';
    const ARRAY_INPUT = false;
    const INPUT_TYPE = 'number';
    
    public function __construct(arch\IContext $context, $name, $value=null) {
        $this->setName($name);
        $this->setValue($value);
    }
    
    protected function _render() {
        $tag = $this->getTag();
        $tag->setAttribute('type', static::INPUT_TYPE);
        
        $this->_applyFormDataAttributes($tag);
        $this->_applyInputAttributes($tag);
        $this->_applyVisualInputAttributes($tag);
        $this->_applyFocusableInputAttributes($tag);
        $this->_applyDataListEntryAttributes($tag);
        $this->_applyRangeEntryAttributes($tag);
        
        return $tag;
    }
    
    
// Dump
    public function getDumpProperties() {
        return [
            'name' => $this->_name,
            'value' => $this->_value,
            'min' => $this->_min,
            'max' => $this->_max,
            'step' => $this->_step,
            'tag' => $this->getTag(),
            'renderTarget' => $this->_getRenderTargetDisplayName()
        ];
    }
}

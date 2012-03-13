<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use df;
use df\core;
use df\aura;

class SelectList extends Base implements IUngroupedSelectionInputWidget, IFocusableInputWidget, core\IDumpable {
    
    use TWidget_FormData;
    use TWidget_Input;
    use TWidget_VisualInput;
    use TWidget_FocusableInput;
    use TWidget_UngroupedSelectionInput;
    
    const PRIMARY_TAG = 'select';
    const ARRAY_INPUT = false;
    
    public function __construct($name, $value=null, $options=null) {
        $this->setName($name);
        $this->setValue($value);
        
        if($options !== null) {
            $this->addOptions($options);
        }
    }
    
    protected function _render() {
        $tag = $this->getTag();
        
        $this->_applyFormDataAttributes($tag, false);
        $this->_applyInputAttributes($tag);
        $this->_applyVisualInputAttributes($tag);
        $this->_applyFocusableInputAttributes($tag);
        
        $optionList = new aura\html\ElementContent();
        $selectionFound = false;
        
        foreach($this->_options as $value => $label) {
            $option = new aura\html\Element('option', null, array('value' => $value));
            
            if(!$selectionFound && $this->_checkSelected($value, $selectionFound)) {
                $option->setAttribute('selected', 'selected');
            }
            
            if($optionRenderer = $this->_optionRenderer) {
                $optionRenderer($option, $value, $label);
            } else {
                $option->push($label);
            }
            
            $optionList->push($option->render());
        }
        
        return $tag->renderWith($optionList, true);
    }
    
    protected function _checkSelected($value, &$selectionFound) {
        return $selectionFound = $value == $this->getValueString();
    }
    
// Dump
    public function getDumpProperties() {
        return [
            'name' => $this->_name,
            'value' => $this->_value,
            'options' => $this->_options,
            'tag' => $this->getTag(),
            'renderTarget' => $this->_getRenderTargetDisplayName()
        ];
    }
}

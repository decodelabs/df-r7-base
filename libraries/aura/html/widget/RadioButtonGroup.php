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

class RadioButtonGroup extends Base implements IUngroupedSelectionInputWidget, core\IDumpable {
    
    use TWidget_FormData;
    use TWidget_Input;
    use TWidget_UngroupedSelectionInput;
    
    const PRIMARY_TAG = 'div';
    const INPUT_TYPE = 'radio';
    const ARRAY_INPUT = false;
    
    protected $_inputIdCounter = 0;
    
    public function __construct(arch\IContext $context, $name, $value=null, $options=null) {
        $this->setName($name);
        $this->setValue($value);
        
        if($options !== null) {
            $this->addOptions($options);
        }
    }
    
    protected function _render() {
        $tag = $this->getTag();
        $optionList = new aura\html\ElementContent();
        $selectionFound = false;
        
        $id = $tag->getId();
        
        foreach($this->_options as $value => $label) {
            $labelTag = new aura\html\Element('label');
            $inputTag = new aura\html\Tag('input', array(
                'type' => static::INPUT_TYPE
            ));
            
            $this->_applyFormDataAttributes($inputTag);
            $this->_applyInputAttributes($inputTag);
            
            $inputTag->setAttribute('value', $value);
            $inputId = null;
            
            if($id !== null) {
                $inputId = $id.'-'.++$this->_inputIdCounter;
                $labelTag->setInputId($inputId);
                $inputTag->setId($inputId);
            }
            
            if(!$selectionFound && $this->_checkSelected($value, $selectionFound)) {
                $inputTag->setAttribute('checked', 'checked');
            }
            
            if($optionRenderer = $this->_optionRenderer) {
                $optionRenderer($labelTag, $value, $label);
            } else {
                $labelTag->push($label);
            }
            
            $labelTag->unshift($inputTag->render(), ' ');
            $optionList->push($labelTag->render());
        }
        
        return $tag->renderWith($optionList, true);
    }
    
    protected function _checkSelected($value, &$selectionFound) {
        $currValue = $this->getValue()->getValue();
        
        if($currValue === null) {
            return false;
        }
        
        return $selectionFound = $value == $currValue;
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

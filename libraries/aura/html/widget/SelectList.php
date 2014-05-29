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

class SelectList extends Base implements IUngroupedSelectionInputWidget, IFocusableInputWidget, core\IDumpable {
    
    use TWidget_FormData;
    use TWidget_Input;
    use TWidget_VisualInput;
    use TWidget_FocusableInput;
    use TWidget_UngroupedSelectionInput;
    
    const PRIMARY_TAG = 'select';
    const ARRAY_INPUT = false;

    protected $_markSelected = true;
    protected $_noSelectionLabel = '--';

    public function __construct(arch\IContext $context, $name, $value=null, $options=null, $labelsAsValues=false) {
        $this->setName($name);
        $this->setValue($value);
        
        if($options !== null) {
            $this->addOptions($options, $labelsAsValues);
        }
    }

    public function shouldMarkSelected($flag=null) {
        if($flag !== null) {
            $this->_markSelected = (bool)$flag;
            return $this;
        }

        return $this->_markSelected;
    }

    public function setNoSelectionLabel($label) {
        $this->_noSelectionLabel = $label;
        return $this;
    }

    public function getNoSelectionLabel() {
        return $this->_noSelectionLabel;
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
            $isSelected = !$selectionFound && $this->_checkSelected($value, $selectionFound);
            $option = new aura\html\Element('option', null, ['value' => $value]);
            
            if($isSelected) {
                $option->setAttribute('selected', 'selected');
            }
            
            if($optionRenderer = $this->_optionRenderer) {
                $optionRenderer($option, $value, $label);
            } else {
                $option->push($label);
            }
            
            if($isSelected && $this->_markSelected) {
                $option->unshift('» ');
            }
            
            $optionList->push($option->render());
        }

        if(!$selectionFound && $this->_noSelectionLabel !== null) {
            $optionList->unshift(new aura\html\Element('option', $this->_noSelectionLabel, ['value' => null, 'disabled' => true, 'selected' => true]));
        } else if(!$this->isRequired()) {
            $optionList->unshift(new aura\html\Element('option', '', ['value' => null]));
        }
        
        return $tag->renderWith($optionList, true);
    }
    
    protected function _checkSelected($value, &$selectionFound) {
        return $selectionFound = (string)$value === $this->getValueString();
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

<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use df;
use df\core;
use df\aura;

class CheckboxGroup extends RadioButtonGroup implements IMultipleSelectionInputWidget {
    
    const INPUT_TYPE = 'checkbox';
    const ARRAY_INPUT = true;
    const WIDGET_CLASS = 'widget-checkbox';

    protected $_allRequired = false;

    public function isAllRequired($flag=null) {
        if($flag !== null) {
            $this->_allRequired = (bool)$flag;

            if($this->_allRequired) {
                $this->_isRequired = true;
            }

            return $this;
        }

        return $this->_allRequired;
    }

    protected function _render() {
        $required = $this->_isRequired;
        $this->_isRequired = $this->_allRequired;

        $output = parent::_render();

        $this->_isRequired = $required;
        return $output;
    }
    
    protected function _checkSelected($value, &$selectionFound) {
        return $this->_value->contains($value);
    }
    
    public function setValue($value) {
        if(!$value instanceof core\collection\IInputTree) {
            if($value instanceof core\collection\ICollection) {
                $value = $value->toArray();
            }
            
            if($value instanceof core\IValueContainer) {
                $value = $value->getValue();
            }
            
            if(!is_array($value)) {
                $value = [$value];
            }
            
            $newValue = [];
            
            foreach($value as $val) {
                $val = (string)$val;
                
                if(!strlen($val)) {
                    continue;
                }
                
                $newValue[] = $val;
            }
            
            $newValue = array_unique($newValue);
            $value = new core\collection\InputTree($newValue);
        }
        
        $this->_value = $value;
        return $this;
    }
    
    public function getValueString() {
        return implode(', ', $this->getValue()->toArray());
    }
}

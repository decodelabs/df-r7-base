<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;
    
class Set extends Base implements core\validate\IEnumField {

    use core\validate\TSanitizingField;

    protected $_options = array();

    public function setOptions(array $options) {
        $this->_options = $options;
        return $this;
    }

    public function getOptions() {
        return $this->_options;
    }

    public function validate(core\collection\IInputTree $node) {
        if((!$count = count($node)) && $this->_isRequired) {
            $node->addError('required', $this->_handler->_(
                'This field requires at least one selection'
            ));
        }
        
        $value = $node->toArray();
        $value = (array)$this->_sanitizeValue($value);

        foreach($value as $key => $keyValue) {
            if(trim($keyValue) === '') {
                $node->{$key}->addError('required', $this->_handler->_(
                    'This field cannot be empty'
                ));

                continue;
            }

            if(!in_array($keyValue, $this->_options)) {
                $node->{$key}->addError('invalid', $this->_handler->_(
                    'This is not a valid option'
                ));
            }
        }

        $value = $this->_applyCustomValidator($node, $value);
        
        return $value;
    }
    
    public function applyValueTo(&$record, $value) {
        if(!is_array($record) && !$record instanceof \ArrayAccess) {
            throw new RuntimeException(
                'Target record does not implement ArrayAccess'
            );
        }
        
        if(!is_array($value)) {
            $value = array($value);
        }
        
        $record[$this->getRecordName()] = $value;
        return $this;
    }
}
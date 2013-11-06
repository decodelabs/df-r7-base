<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;

class IdList extends Base implements core\validate\IIdListField {
    
    use core\validate\TSanitizingField;

    public function validate(core\collection\IInputTree $node) {
        if((!$count = count($node)) && $this->_isRequired) {
            $node->addError('required', $this->_handler->_(
                'This field requires at least one selection'
            ));
        }
        
        $value = $node->toArray();
        $value = (array)$this->_sanitizeValue($value);
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

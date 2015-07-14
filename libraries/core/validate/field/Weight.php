<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;
use df\opal;

class Weight extends Base implements core\validate\IWeightField {

    use core\validate\TStorageAwareField;
    use core\validate\TRecordManipulatorField;
    use opal\query\TFilterConsumer;

    public function validate(core\collection\IInputTree $node) {
        $value = $node->getValue();
        $value = $this->_sanitizeValue($value);

        if(!$value) {
            $value = $this->_generateValue();
        }

        if(!$length = $this->_checkRequired($node, $value)) {
            return null;
        }
        
        if(false === filter_var($value, FILTER_VALIDATE_INT)) {
            $this->_applyMessage($node, 'invalid', $this->validator->_(
                'This is not a valid number'
            ));
        } else {
            $value = (int)$value;
        }
        
        return $this->_finalize($node, $value);
    }

    protected function _generateValue() {
        if(!$this->_storageAdapter) {
            return null;
        }

        $fieldName = $this->getRecordName();

        $query = opal\query\Initiator::factory()
            ->beginSelect(['MAX('.$fieldName.') as max'])
            ->from($this->_storageAdapter);

        if($this->_recordId !== null) {
            $query->where('@primary', '!=', $this->_recordId);
        }

        $this->applyFilters($query);

        return $query->toValue('max') + 1;
    }
}

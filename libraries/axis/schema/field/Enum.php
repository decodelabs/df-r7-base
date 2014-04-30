<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\schema\field;

use df;
use df\core;
use df\axis;
use df\opal;
    
class Enum extends Base implements 
    opal\schema\IOptionProviderField,
    opal\schema\ICharacterSetAwareField {

    use opal\schema\TField_OptionProvider;
    use opal\schema\TField_CharacterSetAware;

    protected function _init(array $options=[]) {
        $this->setOptions($options);
    }

    public function sanitizeValue($value, opal\record\IRecord $forRecord=null) {
        if(empty($value)) {
            $value = null;
        }

        if(!in_array($value, $this->_options)) {
            $value = null;
        }

        return $value;
    }

// Primitive
    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        return new opal\schema\Primitive_Enum($this, $this->_options);
    }

// Ext. serialize
    protected function _importStorageArray(array $data) {
        $this->_setBaseStorageArray($data);
        $this->_setOptionStorageArray($data);
    }

    public function toStorageArray() {
        return array_merge(
            $this->_getBaseStorageArray(),
            $this->_getOptionStorageArray()
        );
    }
}
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

    protected function _init($options=null) {
        if(is_array($options)) {
            $this->setOptions($options);
        } else {
            $this->setType($options);
        }
    }

    public function sanitizeValue($value, opal\record\IRecord $forRecord=null) {
        if($value instanceof core\lang\IEnum) {
            $value = $value->getOption();
        }

        if(!strlen($value)) {
            $value = null;
        }

        if(!in_array($value, $this->getOptions())) {
            $value = null;
        }

        return $value;
    }

    public function getSearchFieldType() {
        return 'string';
    }

    public function getOrderableValue($value) {
        $key = array_search($value, $this->_options);

        if($key !== false) {
            return $key;
        } else {
            return $value;
        }
    }

// Primitive
    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        return new opal\schema\Primitive_Enum($this, $this->getOptions());
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
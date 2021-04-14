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
use df\flex;

use DecodeLabs\Dictum;

class Text extends Base implements
    axis\schema\ILengthRestrictedField,
    opal\schema\ILargeByteSizeRestrictedField,
    opal\schema\ICharacterSetAwareField
{
    use axis\schema\TLengthRestrictedField;
    use opal\schema\TField_LargeByteSizeRestricted;
    use opal\schema\TField_CharacterSetAware;

    protected $_case = flex\ICase::NONE;

    protected function _init($length=null, $case=flex\ICase::NONE)
    {
        if (is_string($length)) {
            $this->setExponentSize($length);
        } else {
            $this->setLength($length);
        }

        $this->setCase($case);
    }

    public function setCase($case)
    {
        $case = flex\TextCase::normalizeCaseFlag($case);

        if ($case != $this->_case) {
            $this->_hasChanged = true;
        }

        $this->_case = $case;
        return $this;
    }

    public function getCase()
    {
        return $this->_case;
    }


    public function compareValues($value1, $value2)
    {
        return Dictum::compare($value1, $value2);
    }

    public function sanitizeValue($value, opal\record\IRecord $forRecord=null)
    {
        if ($value !== null) {
            $value = (string)$value;

            if ($this->_case != flex\ICase::NONE) {
                $value = flex\TextCase::apply($value, $this->_case, $this->_characterSet);
            }
        }

        return $value;
    }

    public function getSearchFieldType()
    {
        return 'string';
    }

    // Primitive
    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema)
    {
        if ($this->_exponentSize !== null) {
            $output = new opal\schema\Primitive_Text($this, $this->_exponentSize);
        } elseif ($this->_isConstantLength) {
            $output = new opal\schema\Primitive_Char($this, $this->_length);
        } else {
            $output = new opal\schema\Primitive_Varchar($this, $this->_length);
        }

        if ($this->_characterSet !== null) {
            $output->setCharacterSet($this->_characterSet);
        }

        return $output;
    }


    // Ext. serialize
    protected function _importStorageArray(array $data)
    {
        $this->_setBaseStorageArray($data);
        $this->_setLengthRestrictedStorageArray($data);
        $this->_setLargeByteSizeRestrictedStorageArray($data);
        $this->_setCharacterSetStorageArray($data);

        if (isset($data['cas'])) {
            $this->_case = $data['cas'];
        } else {
            $this->_case = flex\ICase::NONE;
        }
    }

    public function toStorageArray()
    {
        $output = array_merge(
            $this->_getBaseStorageArray(),
            $this->_getLengthRestrictedStorageArray(),
            $this->_getLargeByteSizeRestrictedStorageArray(),
            $this->_getCharacterSetStorageArray()
        );

        if ($this->_case !== flex\ICase::NONE) {
            $output['cas'] = $this->_case;
        }

        return $output;
    }
}

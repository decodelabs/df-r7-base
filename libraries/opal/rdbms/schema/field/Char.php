<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\rdbms\schema\field;

use df\opal;

class Char extends Base implements
    opal\schema\ILengthRestrictedField,
    opal\schema\IBinaryCollationField,
    opal\schema\ICharacterSetAwareField
{
    use opal\schema\TField_CharacterSetAware;
    use opal\schema\TField_BinaryCollationProvider;
    use opal\schema\TField_LengthRestricted;

    protected function _init($length = null)
    {
        $this->setLength($length);
    }

    protected function _getDefaultLength()
    {
        switch ($this->_type) {
            case 'char':
                return 1;

            case 'varchar':
                return 255;
        }
    }


    // String
    public function toString(): string
    {
        $output = $this->_name . ' ' . strtoupper($this->_type);

        if ($this->_length !== null) {
            $output .= '(' . $this->_length . ')';
        }

        if ($this->_binaryCollation) {
            $output .= ' BINARY';
        }

        if ($this->_isNullable) {
            $output .= ' NULL';
        }

        if ($this->_defaultValue !== null) {
            $output .= ' DEFAULT \'' . $this->_defaultValue . '\'';
        }

        if ($this->_characterSet !== null) {
            $output .= ' CHARSET ' . $this->_characterSet;
        }

        if ($this->_collation) {
            $output .= ' COLLATION ' . $this->_collation;
        }

        $output .= ' [' . $this->_sqlVariant . ']';

        return $output;
    }

    // Ext. serialize
    public function toStorageArray()
    {
        return array_merge(
            $this->_getBaseStorageArray(),
            $this->_getCharacterSetStorageArray(),
            $this->_getBinaryCollationStorageArray(),
            $this->_getLengthRestrictedStorageArray()
        );
    }
}

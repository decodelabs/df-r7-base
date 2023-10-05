<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\rdbms\schema\field;

use df\opal;

class Binary extends Base implements opal\schema\ILengthRestrictedField
{
    use opal\schema\TField_LengthRestricted;

    protected function _init($length = null)
    {
        $this->setLength($length);
    }

    protected function _getDefaultLength()
    {
        switch ($this->_type) {
            case 'binary':
                return 1;

            case 'varbinary':
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

        if ($this->_isNullable) {
            $output .= ' NULL';
        }

        if ($this->_defaultValue !== null) {
            $output .= ' DEFAULT \'' . $this->_defaultValue . '\'';
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
            $this->_getLengthRestrictedStorageArray()
        );
    }
}

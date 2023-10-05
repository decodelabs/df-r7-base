<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\rdbms\schema\field;

use df\opal;

class Integer extends Base implements
    opal\schema\ILengthRestrictedField,
    opal\schema\INumericField,
    opal\schema\IAutoIncrementableField
{
    public const DEFAULT_VALUE = 0;

    use opal\schema\TField_LengthRestricted;
    use opal\schema\TField_Numeric;
    use opal\schema\TField_AutoIncrementable;

    protected function _init($length = null)
    {
        $this->setLength($length);
    }

    protected function _getDefaultLength()
    {
        switch ($this->_type) {
            case 'bool':
            case 'boolean':
                $length = 1;
                break;

                /*
                case 'tinyint':
                    $length = 4;
                    break;

                case 'smallint':
                    $length = 6;
                    break;

                case 'mediumint':
                    $length = 9;
                    break;

                case 'int':
                case 'integer':
                    $length = 11;
                    break;

                case 'bigint':
                    $length = 20;
                    break;
                 *
                 */
        }
    }


    // String
    public function toString(): string
    {
        $output = $this->_name . ' ' . strtoupper($this->_type);

        if ($this->_length !== null) {
            $output .= '(' . $this->_length . ')';
        }

        if ($this->_isUnsigned) {
            $output .= ' UNSIGNED';
        }

        if ($this->_zerofill) {
            $output .= ' ZEROFILL';
        }

        if ($this->_isNullable) {
            $output .= ' NULL';
        }

        if ($this->_autoIncrement) {
            $output .= ' AUTO_INCREMENT';
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
            $this->_getLengthRestrictedStorageArray(),
            $this->_getNumericStorageArray(),
            $this->_getAutoIncrementStorageArray()
        );
    }
}

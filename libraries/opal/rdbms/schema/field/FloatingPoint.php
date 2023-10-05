<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\opal\rdbms\schema\field;

use df\opal;

class FloatingPoint extends Base implements opal\schema\IFloatingPointNumericField
{
    use opal\schema\TField_FloatingPointNumeric;

    public const DEFAULT_VALUE = 0;

    protected function _init($precision = null, $scale = null)
    {
        $this->setPrecision($precision);
        $this->setScale($scale);
    }

    // String
    public function toString(): string
    {
        $output = $this->_name . ' ' . strtoupper($this->_type);

        if ($this->_precision !== null) {
            $output .= '(' . $this->_precision;

            if ($this->_scale !== null) {
                $output .= ',' . $this->_scale;
            }

            $output .= ')';
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
            $this->_getFloatingPointNumericStorageArray()
        );
    }
}

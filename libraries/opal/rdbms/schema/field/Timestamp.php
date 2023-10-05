<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\rdbms\schema\field;

use df\opal;

class Timestamp extends Base implements opal\schema\IAutoTimestampField
{
    use opal\schema\TField_AutoTimestamp;

    // String
    public function toString(): string
    {
        $output = $this->_name . ' ' . strtoupper($this->_type);

        if ($this->_isNullable) {
            $output .= ' NULL';
        }

        if ($this->_defaultValue !== null) {
            $output .= ' DEFAULT \'' . $this->_defaultValue . '\'';
        }

        if ($this->_shouldTimestampOnUpdate) {
            $output .= ' on update TIMESTAMP';
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
            $this->_getAutoTimestampStorageArray()
        );
    }
}

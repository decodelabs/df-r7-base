<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\rdbms\schema\field;

use df\flex;
use df\opal;

class Set extends Base implements
    opal\schema\IOptionProviderField,
    opal\schema\ICharacterSetAwareField
{
    use opal\schema\TField_CharacterSetAware;
    use opal\schema\TField_OptionProvider;

    protected function _init(array $options)
    {
        $this->setOptions($options);
    }


    // String
    public function toString(): string
    {
        $output = $this->_name . ' ' . strtoupper($this->_type) . '(' . flex\Delimited::implode($this->_options) . ')';

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
            $this->_getOptionStorageArray()
        );
    }
}

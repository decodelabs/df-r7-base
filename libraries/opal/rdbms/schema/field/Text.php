<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\rdbms\schema\field;

use df\core;
use df\opal;

class Text extends Base implements 
    opal\schema\IBinaryCollationField, 
    opal\schema\ICharacterSetAwareField {
    
    use opal\schema\TField_CharacterSetAware;
    use opal\schema\TField_BinaryCollationProvider;
    
// String
    public function toString() {
        $output = $this->_name.' '.strtoupper($this->_type);
        
        if($this->_binaryCollation) {
            $output .= ' BINARY';
        }
        
        if($this->_isNullable) {
            $output .= ' NULL';
        }
        
        if($this->_defaultValue !== null) {
            $output .= ' DEFAULT \''.$this->_defaultValue.'\'';
        }
        
        if($this->_characterSet !== null) {
            $output .= ' CHARSET '.$this->_characterSet;
        }
        
        if($this->_collation) {
            $output .= ' COLLATION '.$this->_collation;
        }
        
        $output .= ' ['.$this->_sqlVariant.']';
        
        return $output;
    }    
}

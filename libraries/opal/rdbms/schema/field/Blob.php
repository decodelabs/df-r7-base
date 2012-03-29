<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\rdbms\schema\field;

use df\core;
use df\opal;

class Blob extends Base {
    
// String
    public function toString() {
        $output = $this->_name.' '.strtoupper($this->_type);
        
        if($this->_isNullable) {
            $output .= ' NULL';
        }
        
        if($this->_defaultValue !== null) {
            $output .= ' DEFAULT \''.$this->_defaultValue.'\'';
        }
        
        if($this->_collation) {
            $output .= ' COLLATION '.$this->_collation;
        }
        
        $output .= ' ['.$this->_sqlVariant.']';
        
        return $output;
    }    
}

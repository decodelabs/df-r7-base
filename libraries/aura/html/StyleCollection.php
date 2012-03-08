<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html;

use df;
use df\core;
use df\aura;

class StyleCollection extends core\collection\Map implements IStyleCollection, core\IDumpable {
    
    use core\TStringProvider;
    
    public function import($input) {
        if(is_string($input)) {
            $parts = explode(';', $input);
            $input = array();
            
            foreach($parts as $part) {
                $exp = explode(':', $part);
                
                if(count($exp) == 2) {
                    $input[trim(array_shift($exp))] = trim(array_shift($exp));
                }
            }
        }
        
        return parent::import($input);
    }
    
    public function toString() {
        $output = array();
        
        foreach($this->_collection as $key => $value) {
            $output[] = $key.': '.$value.';';
        }
        
        return implode(' ', $output);
    }
    
    public function getDumpProperties() {
        return $this->toString();
    }
}
<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html;

use df;
use df\core;
use df\aura;

class StyleCollection implements IStyleCollection, core\IDumpable {
    
    use core\TStringProvider;
    use core\collection\TArrayCollection_Map;
    use core\collection\TArrayCollection_Constructor;
    
    public function import($input) {
        if($input instanceof core\IArrayProvider) {
            $input = $input->toArray();
        }
        
        if(!is_array($input)) {
            $input = [$input];
        }

        $this->_importSet($input);
        return $this;
    }

    protected function _importSet(array $set) {
        foreach($set as $key => $val) {
            if(is_numeric($key) && is_string($val)) {
                $temp = explode(';', $val);
                $val = [];

                foreach($temp as $part) {
                    $part = trim($part);

                    if(empty($part)) {
                        continue;
                    }

                    $exp = explode(':', $part);
                    
                    if(count($exp) == 2) {
                        $this->set(trim(array_shift($exp)), trim(array_shift($exp)));
                    }
                }
            } else if(is_array($val)) {
                $this->_importSet($val);
            } else if(is_string($val)) {
                $this->set(trim($key), trim($val));
            }
        }
    }
    
    public function toString() {
        $output = [];
        
        foreach($this->_collection as $key => $value) {
            $output[] = $key.': '.$value.';';
        }
        
        return implode(' ', $output);
    }
    
    public function getDumpProperties() {
        return $this->toString();
    }
}
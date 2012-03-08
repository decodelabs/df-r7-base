<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html;

use df;
use df\core;
use df\aura;


class StyleBlock implements IStyleBlock, core\collection\IMappedCollection, core\IDumpable {
    
    use core\TStringProvider;
    use core\collection\TArrayCollection;
    use core\collection\TAssociativeValueMapArrayCollection;
    
    public function import($input) {
        if(is_string($input)) {
            $parts = explode('{', $input);
            $input = array();
            $count = count($parts);
            
            while(count($parts)) {
                $selector = trim(array_shift($parts));
                $body = explode('}', array_shift($parts), 2);
                $nextSelector = trim(array_pop($body));
                $body = trim(array_shift($body));
                
                if(!empty($nextSelector)) {
                    array_unshift($parts, $nextSelector);
                }
                
                $input[$selector] = new StyleCollection($body);
            }
        }
        
        if($input instanceof core\IArrayProvider) {
            $input = $input->toArray();
        }
        
        if(is_array($input)) {
            foreach($input as $key => $value) {
                $this->set($key, $value);
            }
        }
        
        return $this;
    }
    
    public function set($key, $value) {
        if(!$value instanceof IStyleCollection) {
            $value = new StyleCollection($value);
        }
        
        $this->_collection[(string)$key] = $value;
        return $this;
    }
    
    public function get($key, $default=null) {
        $key = (string)$key;
        
        if(array_key_exists($key, $this->_collection)) {
            $output = $this->_collection[$key];
        } else {
            $output = $default;
        }
        
        if($output !== null && !$output instanceof IStyleCollection) {
            $output = new StyleCollection($default);
        }
        
        return $output;
    }
    
    public function toString() {
        if(empty($this->_collection)) {
            return '';
        }
        
        $output = array();
        
        foreach($this->_collection as $selector => $styles) {
            $output[] = $selector.' { '.$styles.' }';
        }
        
        return '<style type="text/css">'."\n    ".implode("\n".'    ', $output)."\n".'</style>';
    }
    
    
// Dump
    public function getDumpProperties() {
        return $this->_collection;
    }
}
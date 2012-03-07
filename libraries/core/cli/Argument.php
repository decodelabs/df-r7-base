<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\cli;

use df;
use df\core;

class Argument implements IArgument, core\IDumpable {
    
    use core\TStringProvider;
    
    protected $_option;
    protected $_value;
    
    public function __construct($string) {
        if(substr($string, 0, 1) != '-') {
            $this->_value = $string;
        } else {
            $parts = explode('=', $string, 2);
            $this->_option = array_shift($parts);
            $this->_value = array_shift($parts);
        }
    }
    
    public function setOption($option) {
        if(!strlen($option)) {
            $option = null;
        }
        
        $this->_option = $option;
        return $this;
    }
    
    public function getOption() {
        return $this->_option;
    }
    
    public function isOption() {
        return $this->_option !== null;
    }
    
    public function isLongOption() {
        return substr($this->_option, 0, 2) == '--';
    }
    
    public function isShortOption() {
        return substr($this->_option, 0, 1) == '-' && !$this->isLongOption();
    }
    
    public function isOptionCluster() {
        return preg_match('/^-[a-zA-Z0-9]{2,}/', $this->_option);
    }
    
    public function getClusterOptions() {
        $output = array();
        
        if($this->isOptionCluster()) {
            for($i = 2; $i < strlen($this->_option); $i++) {
                $output[] = $this->_option{$i};
            }
        }
        
        return $output;
    }
    
    public function setValue($value) {
        if(!strlen($value)) {
            $value = null;
        }
        
        $this->_value = $value;
        return $this;
    }
    
    public function getValue() {
        return $this->_value;
    }
    
    public function hasValue() {
        return $this->_value !== null;
    }
    
    public function toString() {
        $output = '';
        $hasValue = $this->hasValue();
        
        if($this->_option !== null) {
            $output = $this->_option;
            
            if($hasValue) {
                $output .= '=';
            }
        }
        
        if($hasValue) {
            $output .= $this->_value;
        }
        
        return $output;
    }
    
    
// Dump
    public function getDumpProperties() {
        return $this->toString();
    }
}

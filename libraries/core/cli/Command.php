<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\cli;

use df;
use df\core;

class Command implements ICommand {
    
    use core\TStringProvider;
    use core\collection\TArrayCollection;
    use core\collection\TArrayCollection_ProcessedIndexedValueMap;
    use core\collection\TArrayCollection_ProcessedShiftable;
    
    protected $_executable;
    
    public static function fromArgv() {
        if(!isset($_SERVER['argv'])) {
            throw new RuntimeException(
                'No argv information is available'
            );
        }
        
        // TODO: build a proper PHP binary finder
        if(isset($_SERVER['_'])) {
            $executable = $_SERVER['_'];
        } else {
            $executable = 'php';
        }
        
        $output = new self($executable);
        
        foreach($_SERVER['argv'] as $arg) {
            $output->addArgument(new Argument($arg));
        }
        
        return $output;
    }
    
    public static function fromString($string) {
        // TODO: parse properly to account for quoted strings
        $parts = explode(' ', $string);
        $output = new self(array_shift($parts));
        
        foreach($parts as $part) {
            $output->addArgument(new Argument($part));
        }
        
        return $output;
    }
    
    public function __construct($executable=null) {
        $this->setExecutable($executable);
    }
    
    public function import($input) {
        core\stub($input);
    }
    

    
// Executable
    public function setExecutable($executable) {
        $this->_executable = $executable;
        return $this;
    }
    
    public function getExecutable() {
        return $this->_executable;
    }
    
    
// Arguments
    public function addArgument($argument) {
        return $this->push($argument);
    }
    
    public function getArguments() {
        return $this->toArray();
    }
    
    public function insert($argument) {
        return $this->push($argument);
    }
    
    protected function _onInsert() {}
    
    protected function _expandInput($input) {
        if($input instanceof core\ICollection) {
            $input = $input->toArray();
        }
        
        if(!is_array($input)) {
            if(is_string($input)) {
                $input = explode(' ', $input);
            } else {
                $input = array($input);
            }
        }
        
        foreach($input as $i => $value) {
            if(!$value instanceof IArgument) {
                $input[$i] = new Argument($value);
            }
        }
        
        return $input;
    }
    
    
// String
    public function toString() {
        $output = $this->_executable;
        
        foreach($this->_collection as $argument) {
            $output .= ' '.$argument;
        }
        
        return $output;
    }
}

<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\uri;

use df;
use df\core;

class Path implements IPath, \IteratorAggregate, \Serializable, core\IDumpable {
    
    use core\TStringProvider;
    use core\collection\TArrayCollection;
    use core\collection\TIndexedProcessedValueMapArrayCollection;
    use core\collection\TProcessedShiftableArrayCollection;
    use core\collection\TSeekableArrayCollection;
        
    protected $_separator = '/';
    protected $_autoCanonicalize = true;
    protected $_isAbsolute = false;
    protected $_addTrailingSlash = false;
    
    public static function factory() {
        if(func_num_args()) {
            $path = func_get_arg(0);
        } else {
            $path = null;
        }
        
        if($path instanceof IPath) {
            return $path;
        }
        
        $class = get_called_class();
        $ref = new \ReflectionClass($class);
        return $ref->newInstanceArgs(func_get_args());
    }
    
    public function __construct($input=null, $autoCanonicalize=true, $separator=null) {
        $this->canAutoCanonicalize($autoCanonicalize);
        
        if($separator !== null) {
            $this->setSeparator($separator);
        }
        
        if($input !== null) {
            $this->import($input);
        }
    }
    
// Serialize
    public function serialize() {
        return $this->toString();
    }
    
    public function unserialize($data) {
        return $this->import($data);
    }
    
// Parameters
    public function setSeparator($separator) {
        if($separator !== null) {
            $this->_separator = (string)$separator;
        }
        
        return $this;
    }
    
    public function getSeparator() {
        return $this->_separator;
    }
    
    public function isAbsolute($flag=null) {
        if($flag !== null) {
            $this->_isAbsolute = (bool)$flag;
            return $this;    
        }
        
        return $this->_isAbsolute;
    }
    
    public function shouldAddTrailingSlash($flag=null) {
        if($flag !== null) {
            $this->_addTrailingSlash = (bool)$flag;
            return $this;    
        }
        
        return $this->_addTrailingSlash;
    }
    
    public function canAutoCanonicalize($flag=null) {
        if($flag !== null) {
            $this->_autoCanonicalize = (bool)$flag;
            return $this;
        }
        
        return $this->_autoCanonicalize;
    }
    
    public function canonicalize() {
        if(in_array('.', $this->_collection) 
        || in_array('..', $this->_collection)
        || in_array('', $this->_collection)) {
            $queue = $this->_collection;
            $this->_collection = array();
            
            foreach($queue as $key => $part) {
                if($part == '..' && !$this->isEmpty() && $this->getLast() != '..') {
                    array_pop($this->_collection);
                    continue;
                }
                
                if($part != '.' && strlen($part)) {
                    $this->_collection[] = $part;
                }
            }
        }
        
        return $this;
    }
    
    
// Collection
    public function import($input) {
        if($input === null) {
            return $this;
        }
        
        if($input instanceof IPath) {
            $this->_collection = $input->_collection;
            $this->_isAbsolute = $input->_isAbsolute;
            $this->_autoCanonicalize = $input->_autoCanonicalize;
            $this->_addTrailingSlash = $input->_addTrailingSlash;
            $this->_separator = $input->_separator;
            
            return $this;
        }
        
        
        $this->clear();
        
        
        if($input instanceof core\collection\ICollection) {
            $input = $input->toArray();
        } else if(!is_array($input)) {
            $input = explode(
                $this->_separator, 
                str_replace(array('\\', '/'), $this->_separator, (string)$input)
            );
        }
        
        if(!($count = count($input))) {
            return $this;
        }
        
        // Strip trailing slash
        if($count > 1 && !strlen(trim($input[$count - 1]))) {
            array_pop($input);
            $this->_addTrailingSlash = true;
        }
        
        // Strip leading slash
        if(!strlen($input[0])) {
            array_shift($input);
            $this->_isAbsolute = true;
        }
        
        // Fill values
        foreach($input as $value) {
            $this->_collection[] = trim($value);
        }
        
        
        // Canonicalize
        if($this->_autoCanonicalize) {
            $this->canonicalize();
        }
        
        return $this;
    }

    public function clear() {
        $this->_isAbsolute = false;
        $this->_addTrailingSlash = false;
        $this->_collection = array();
        
        return $this;
    }
    
    public function insert($value) {
        foreach(func_get_args() as $arg) {
            foreach($this->_expandInput($arg) as $value) {
                array_push($this->_collection, $value);
            }
        }
        
        $this->_onInsert();
        return $this;
    }
    
    protected function _onInsert() {
        if(!strlen($this->getLast())) {
            array_pop($this->_collection);
            $this->_addTrailingSlash = true;
        }
        
        if($this->_autoCanonicalize) {
            $this->canonicalize();
        }
    }
    
    protected function _expandInput($input) {
        if($input instanceof core\ICollection) {
            $input = $input->toArray();
        }
        
        if(is_array($input)) {
            return $input;
        }
        
        $input = (string)$input;
        
        if(!strlen($input)) {
            return array();
        }
        
        return explode($this->_separator, ltrim($input, $this->_separator));
    }
    
    
    
// Accessors
    public function getDirname() {
        return dirname($this->toString());
    }
    
    public function setBasename($basename) {
        $t = $this->_autoCanonicalize;
        $this->_autoCanonicalize = false;
        
        $this->set(-1, $basename);
        $this->_autoCanonicalize = $t;
        
        return $this;
    }
    
    public function getBasename() {
        return $this->getLast();
    }
    
    public function setFilename($filename) {
        if($this->_addTrailingSlash) {
            $this->_collection[] = $filename;
            $this->_addTrailingSlash = false;
            return $this;
        }
        
        if(strlen($extension = $this->getExtension())
        || substr($this->getLast(), -1) == '.') {
            $filename .= '.'.$extension;
        }
        
        return $this->setBasename($filename);
    }
    
    public function getFilename() {
        if($this->_addTrailingSlash) {
            return null;
        }
        
        $basename = $this->getBasename();
        
        if(false === ($pos = strrpos($basename, '.'))) {
            return $basename;
        }
        
        return substr($basename, 0, $pos);
    }
    
    public function hasExtension($extensions=false) {
        if($this->_addTrailingSlash) {
            return false;
        }
        
        if(($basename = $this->getBasename()) == '..') {
            return false;
        }
        
        if($extensions === false) {
            return false !== strrpos($basename, '.');
        }
        
        if(!is_array($extensions)) {
            $extensions = func_get_args();
        }
        
        if(is_string($extension = $this->getExtension())) {
            $extension = strtolower($extension);
        }
        
        array_walk($extensions, function(&$value) {
             if(is_string($value)) {
                 $value = strtolower($value);
             }
        });
        
        return in_array($extension, $extensions, true);
    }
    
    public function setExtension($extension) {
        $filename = $this->getFilename();
        
        if($extension !== null) {
            $filename .= '.'.$extension;
        }
        
        if(strlen($filename)) {
            if($this->_addTrailingSlash) {
                $this->_collection[] = $filename;
                $this->_addTrailingSlash = false;
                return $this;
            } else {
                return $this->setBasename($filename);
            }
        }
        
        return $this;
    }
    
    public function getExtension() {
        if($this->_addTrailingSlash) {
            return null;
        }
        
        $basename = $this->getBasename();
        
        if(false === ($pos = strrpos($basename, '.'))) {
            return null;
        }
        
        if(false === ($output = substr($basename, $pos + 1))) {
            return null;
        }
        
        return $output;
    }
    
    
// Strings
    public function toString() {
        return $this->_pathToString(false);
    }
    
    public function toUrlEncodedString() {
        return $this->_pathToString(true);
    }
    
    protected function _pathToString($encode=false) {
        $output = '';
        
        if($this->_isAbsolute) {
            $output .= $this->_separator;
        }
        
        foreach($this->_collection as $key => $value) {
            if($key > 0) {
                $output .= $this->_separator;
            }
            
            if($encode) {
                $value = rawurlencode($value);
            }
            
            $output .= $value;
        }
        
        if(!strlen($output) && !$this->_isAbsolute) {
            $output = '.';
        }
        
        if($this->_addTrailingSlash
        && $output != $this->_separator
        && !$this->hasExtension()) {
            $output .= $this->_separator;
        }
        
        return $output;
    }
    
// Dump
    public function getDumpProperties() {
        return $this->toString();
    }
}

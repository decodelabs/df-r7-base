<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug;

use df;
use df\core;

class StackCall implements IStackCall {
    
    const TYPE_STATIC = 1;
    const TYPE_OBJECT = 2;
    const TYPE_NS_FUNCTION = 3;
    const TYPE_GLOBAL_FUNCTION = 4;
    
    protected $_function;
    protected $_className;
    protected $_namespace;
    protected $_type = 4;
    protected $_args;
    
    protected $_callingFile;
    protected $_callingLine;
    protected $_originFile;
    protected $_originLine;
    
    public function __construct(array $callData) {
        if(isset($callData['fromFile']) && $callData['fromFile'] !== $callData['file']) {
            $this->_callingFile = $callData['fromFile'];
        }
        
        if(isset($callData['fromLine'])) {
            $this->_callingLine = $callData['fromLine'];
        }
        
        $this->_originFile = $callData['file'];
        $this->_originLine = $callData['line'];
        
        if(isset($callData['function'])) {
            $this->_function = $callData['function'];
        }
        
        if(isset($callData['class'])) {
            $this->_className = $callData['class'];
            $parts = explode('\\', $this->_className);
            $this->_className = array_pop($parts);
        } else {
            $parts = explode('\\', $this->_function);
            $this->_function = array_pop($parts);
        }
        
        $this->_namespace = implode('\\', $parts);
        
        if(isset($callData['type'])) {
            switch($callData['type']) {
                case '::': 
                    $this->_type = self::TYPE_STATIC;
                    break;
                    
                case '->':
                    $this->_type = self::TYPE_OBJECT;
                    break;
                    
                default:
                    throw new \Exception('Unknown call type: '.$callData['type']);
            }
        } else if($this->_namespace !== null) {
            $this->_type = 3;
        }
        
        if(isset($callData['args'])) {
            $this->_args = $callData['args'];
        }
    }


// Args
    public function getArgs() {
        return $this->_args;
    }
    
    public function hasArgs() {
        return !empty($this->_args);
    }
    
    public function countArgs() {
        return count($this->_args);
    }
    

// Type
    public function getType() {
        return $this->_type;
    }
    
    public function isStatic() {
        return $this->_type === self::TYPE_STATIC;
    }
    
    public function isObject() {
        return $this->_type === self::TYPE_OBJECT;
    }
    
    public function isNamespaceFunction() {
        return $this->_type === self::TYPE_NS_FUNCTION;
    }
    
    public function isGlobalFunction() {
        return $this->_type === self::TYPE_GLOBAL_FUNCTION;
    }
    

// Namespace
    public function getNamespace() {
        return $this->_namespace;
    }
    
    public function hasNamespace() {
        return $this->_namespace !== null;
    }
    
    
// Class
    public function getClass() {
        if($this->_className === null) {
            return null;
        }
        
        $output = '';
        
        if($this->_namespace !== null) {
            $output = $this->_namespace.'\\';
        }
        
        $output .= $this->_className;
        return $output;
    }
    
    public function hasClass() {
        return $this->_className !== null;
    }
    
    public function getClassName() {
        return $this->_className;
    }
    
    
// Function
    public function getFunctionName() {
        return $this->_function;
    }
    
    public function getCallSignature() {
        $output = '';
        
        if($this->_namespace !== null) {
            $output = $this->_namespace.'\\';
        }
        
        if($this->_className !== null) {
            $output .= $this->_className;
        }
        
        switch($this->_type) {
            case self::TYPE_STATIC:
                $output .= '::';
                break;
                
            case self::TYPE_OBJECT:
                $output .= '->';
                break;
        }
        
        $output .= $this->_function.'(';
        
        if(!empty($this->_args)) {
            $output .= count($this->_args);
        }
        
        $output .= ')';
        return $output;
    }

    
// Location
    public function getCallingFile() {
        if($this->_callingFile !== null) {
            return $this->_callingFile;
        }
        
        return $this->_originFile;
    }
    
    public function getCallingLine() {
        if($this->_callingLine !== null) {
            return $this->_callingLine;
        }
        
        return $this->_originLine;
    }
    
    public function getOriginFile() {
        return $this->_originFile;
    }
    
    public function getOriginLine() {
        return $this->_originLine;
    }
}

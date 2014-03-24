<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug;

use df;
use df\core;

class StackCall implements IStackCall, core\IDumpable {
    
    protected $_function;
    protected $_className;
    protected $_namespace;
    protected $_type = 4;
    protected $_args;
    
    protected $_callingFile;
    protected $_callingLine;
    protected $_originFile;
    protected $_originLine;
    
    public static function factory($rewind=0) {
        $data = debug_backtrace();
        
        while($rewind > 0) {
            $rewind--;
            array_shift($data);
        }
        
        $last = array_shift($data);
        $output = array_shift($data);
        $output['fromFile'] = @$output['file'];
        $output['fromLine'] = @$output['line']; 
        $output['file'] = @$last['file'];
        $output['line'] = @$last['line'];
        
        return new self($output);
    }
    
    public function __construct(array $callData) {
        if(isset($callData['fromFile']) && $callData['fromFile'] !== @$callData['file']) {
            $this->_callingFile = $callData['fromFile'];
        }
        
        if(isset($callData['fromLine'])) {
            $this->_callingLine = $callData['fromLine'];
        }
        
        $this->_originFile = @$callData['file'];
        $this->_originLine = @$callData['line'];
        
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
                    $this->_type = IStackCall::STATIC_METHOD;
                    break;
                    
                case '->':
                    $this->_type = IStackCall::OBJECT_METHOD;
                    break;
                    
                default:
                    throw new \Exception('Unknown call type: '.$callData['type']);
            }
        } else if($this->_namespace !== null) {
            $this->_type = 3;
        }
        
        if(isset($callData['args'])) {
            $this->_args = (array)$callData['args'];
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
    
    public function getArgString() {
        $output = array();

        if(!is_array($this->_args)) {
            $this->_args = [$this->_args];
        }
        
        foreach($this->_args as $arg) {
            if(is_string($arg)) {
                if(strlen($arg) > 16) {
                    $arg = substr($arg, 0, 16).'...';
                }
                
                $arg = '\''.$arg.'\'';
            } else if(is_array($arg)) {
                $arg = 'Array('.count($arg).')';
            } else if(is_object($arg)) {
                $arg = get_class($arg).' Object';
            } else if(is_bool($arg)) {
                if($arg) {
                    $arg = 'true';
                } else {
                    $arg = 'false';
                }
            } else if(is_null($arg)) {
                $arg = 'null';
            }

            $output[] = $arg;
        }
        
        return '('.implode(', ', $output).')';
    }
    

// Type
    public function getType() {
        return $this->_type;
    }
    
    public function getTypeString() {
        switch($this->_type) {
            case IStackCall::STATIC_METHOD:
                return '::';
                
            case IStackCall::OBJECT_METHOD:
                return '->';
        }
    }

    public function isStatic() {
        return $this->_type === IStackCall::STATIC_METHOD;
    }
    
    public function isObject() {
        return $this->_type === IStackCall::OBJECT_METHOD;
    }
    
    public function isNamespaceFunction() {
        return $this->_type === IStackCall::NAMESPACE_FUNCTION;
    }
    
    public function isGlobalFunction() {
        return $this->_type === IStackCall::GLOBAL_FUNCTION;
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
    
    public function getSignature($argString=false) {
        $output = '';
        
        if($this->_namespace !== null) {
            $output = $this->_namespace.'\\';
        }
        
        if($this->_className !== null) {
            $output .= $this->_className;
        }
        
        if($this->_type) {
            $output .= $this->getTypeString();
        }
        
        $output .= $this->_function;
        
        if($argString) {
            $output .= $this->getArgString();
        } else if($argString !== null) {
            $output .= '(';
            
            if(!empty($this->_args)) {
                $output .= count($this->_args);
            }
            
            $output .= ')';
        }
        
        return $output;
    }
    
    public function toArray() {
        return [
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'function' => $this->_function,
            'class' => $this->_className,
            'type' => $this->getTypeString(),
            'args' => $this->_args
        ];
    }

    public function toJsonArray() {
        return [
            'file' => core\io\Util::stripLocationFromFilePath($this->getFile()),
            'line' => $this->getLine(),
            'signature' => $this->getSignature()
            /*
            'function' => $this->_function,
            'class' => $this->_className,
            'type' => $this->getTypeString(),
            'args' => $this->getArgString()
            */
        ];
    }

    public function toJson() {
        return json_encode($this->toJsonArray());
    }
    

    
// Location
    public function getFile() {
        return $this->_originFile;
    }
    
    public function getLine() {
        return $this->_originLine;
    }

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


// Dump
    public function getDumpProperties() {
        return [
            new core\debug\dumper\Property(null, $this->getSignature(true)),
            new core\debug\dumper\Property(null, $this->getFile().' : '.$this->getLine())
        ];
    }
}

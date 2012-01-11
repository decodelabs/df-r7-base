<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug;

use df;
use df\core;

class StackTrace implements IStackTrace {
    
    use TLocationProvider;
    
    protected $_calls = array();
    
    public static function factory($rewind=0) {
        return self::createFromTrace(debug_backtrace(), $rewind);
    }
    
    public static function createFromTrace(array $data, $rewind=0) {
        $output = array();
        
        while($rewind > 0) {
            $rewind--;
            array_shift($data);
        }
        
        $last = array_shift($data);
        $last['fromFile'] = $last['file'];
        $last['fromLine'] = $last['line'];
        
        foreach($data as $callData) {
            $callData['fromFile'] = $callData['file'];
            $callData['fromLine'] = $callData['line'];
            $callData['file'] = $last['fromFile'];
            $callData['line'] = $last['fromLine'];
            
            $output[] = new StackCall($callData);
            $last = $callData;
        }
        
        return new self($output);
    }
    
    public function __construct(array $calls=null) {
        if(!empty($calls)) {
            foreach($calls as $call) {
                if($call instanceof IStackCall) {
                    $this->_calls[] = $call;
                }
            }
            
            if(isset($this->_calls[0])) {
                $this->_file = $this->_calls[0]->getFile();
                $this->_line = $this->_calls[0]->getLine();
            } else {
                $data = debug_backtrace();
                $this->_file = $data[1]['file'];
                $this->_line = $data[1]['line'];
            }
        }
    }
    
    public function toArray() {
        return $this->_calls;
    }
    
    
// Debug node
    public function getNodeTitle() {
        return 'Stack Trace';
    }

    public function getNodeType() {
        return 'stackTrace';
    }
    
    public function isCritical() {
        return false;
    }
}

<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug;

use df;
use df\core;

class StackTrace implements IStackTrace {
    
    protected $_calls = array();
    
    public static function factory($rewind=0) {
        $data = debug_backtrace();
        
        $last = array_shift($data);
        $last['fromFile'] = $last['file'];
        $last['fromLine'] = $last['line'];
        
        $output = new self();
        
        foreach($data as $callData) {
            $callData['fromFile'] = $callData['file'];
            $callData['fromLine'] = $callData['line'];
            $callData['file'] = $last['fromFile'];
            $callData['line'] = $last['fromLine'];
            
            $output->_calls[] = new StackCall($callData);
            $last = $callData;
        }
        
        return $output;
    }
    
    public function __construct(array $calls=null) {
        if(!empty($calls)) {
            foreach($calls as $call) {
                if($call instanceof IStackCall) {
                    $this->_calls[] = $call;
                }
            }
        }
    }
}

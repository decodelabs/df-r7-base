<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\process;

use df;
use df\core;
use df\halo;

class Result implements halo\process\IResult {
    
    protected $_hasLaunched = true;
    protected $_hasCompleted = false;
    protected $_timer;
    protected $_output;
    protected $_error;
    
    public function __construct() {
        $this->_timer = new core\time\Timer();
    }
    
    
    
    public function registerFailure() {
        $this->_timer->stop();
        $this->_hasLaunched = false;
        
        return $this;
    }
    
    public function hasLaunched() {
        return $this->_hasLaunched;
    }
    
    
    
    public function registerCompletion() {
        $this->_timer->stop();
        $this->_hasCompleted = true;
        
        return $this;
    }
    
    public function hasCompleted() {
        return $this->_hasCompleted;
    }
    
    
    
    public function getTimer() {
        return $this->_timer;
    }
    
    
    public function setOutput($output) {
        $this->_output = $output;
        return $this;
    }
    
    public function appendOutput($output) {
        $this->_output .= $output;
        return $this;
    }
    
    public function hasOutput() {
        return isset($this->_output{0});
    }
    
    public function getOutput() {
        return $this->_output;
    }
    
    
    public function setError($error) {
        $this->_error = $error;
        return $this;
    }
    
    public function appendError($error) {
        $this->_error .= $error;
        return $this;
    }
    
    public function hasError() {
        return isset($this->_error{0});
    }
    
    public function getError() {
        return $this->_error;
    }
}
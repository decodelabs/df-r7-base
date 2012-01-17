<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\acid;

use df;
use df\core;
use df\acid;

class Result implements IResult {
    
    protected $_batches = array();
    protected $_successes = 0;
    protected $_failures = array();
    
    
// Batch
    public function addBatch(IBatch $batch) {
        $this->_batches[get_class($batch)] = true;
        return $this;
    }
    
    public function hasBatch($batch) {
        if($batch instanceof IBatch) {
            $class = get_class($batch);
        } else {
            $class = (string)$batch;
        }
        
        return isset($this->_batches[$class]);
    }
    
    public function hasBatchFailed($batch) {
        if($batch instanceof IBatch) {
            $class = get_class($batch);
        } else {
            $class = (string)$batch;
        }
        
        if(isset($this->_batches[$class])) {
            return !$this->_batches[$class];
        } else {
            return false;
        }
    }
    
    public function getBatches() {
        return array_keys($this->_batches);
    }
    
    
// Test
    public function registerSuccess(IBatch $batch, $testName) {
        $this->_successes++;
        return $this;
    }
    
    public function registerFailure(IBatch $batch, $testName, \Exception $e) {
        $class = get_class($batch);
        $this->_batches[$class] = false;
        
        $name = $class.'::'.$testName;
        $this->_failures[$name] = $e;
        
        return $this;
    }
}

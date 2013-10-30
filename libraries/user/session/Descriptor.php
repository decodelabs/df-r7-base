<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\user\session;

use df;
use df\core;
use df\user;

class Descriptor implements user\session\IDescriptor {
    
    public $internalId;
    public $externalId;
    public $transitionId;
    public $userId;
    public $startTime;
    public $accessTime;
    public $transitionTime;
    public $justStarted = false;
    
    public static function fromArray(array $values) {
        $output = new self(null, null);
        
        foreach($values as $key => $value) {
            switch($key) {
                case 'internalId':
                    $output->setInternalId($value);
                    break;
                    
                case 'externalId':
                    $output->setExternalId($value);
                    break;
                    
                case 'transitionId':
                    $output->setTransitionId($value);
                    break;
                    
                case 'userId':
                    $output->setUserId($value);
                    break;
                    
                case 'startTime':
                    $output->setStartTime($value);
                    break;
                    
                case 'accessTime':
                    $output->setAccessTime($value);
                    break;
                    
                case 'transitionTime':
                    $output->setTransitionTime($value);
                    break;
            }
        }
        
        return $output;
    }
    
    public function __construct($internalId, $externalId) {
        $this->setInternalId($internalId);
        $this->setExternalId($externalId);
    }
    
    public function isNew() {
        return $this->startTime == $this->accessTime
            && $this->transitionTime === null;
    }
    
    public function hasJustStarted($flag=null) {
        if($flag !== null) {
            $this->justStarted = (bool)$flag;
            return $this;
        }
        
        return $this->justStarted;
    }
    
    public function setInternalId($id) {
        $this->internalId = $id;
        return $this;
    }

    public function getInternalId() {
        return $this->internalId;
    }

    public function setExternalId($id) {
        $this->externalId = $id;
        return $this;
    }

    public function getExternalId() {
        return $this->externalId;
    }

    public function setTransitionId($id) {
        $this->transitionId = $id;
        return $this;
    }

    public function getTransitionId() {
        return $this->transitionId;
    }
    
    public function applyTransition($newExternalId) {
        $this->transitionId = $this->externalId;
        $this->transitionTime = $this->accessTime = time();
        return $this->setExternalId($newExternalId);
    }
    
    public function hasJustTransitioned($transitionLifeTime=10) {
        return time() - $this->transitionTime < $transitionLifeTime; 
    }
    
    public function needsTouching($transitionLifeTime=10) {
        if($this->justStarted) {
            return false;
        }
        
        $time = time();
        $output = true;
        
        if($time - $this->transitionTime < $transitionLifeTime) {
            $output = false;
        }
        
        if($time - $this->accessTime < $transitionLifeTime) {
            $output = false;
        }
        
        return $output;
    }
    
    public function touchInfo($transitionLifeTime=10) {
        $output = array(
            'accessTime' => $this->accessTime = time()
        );
        
        if($this->accessTime - $this->transitionTime >= $transitionLifeTime) {
            $output['transitionId'] = $this->transitionId = null;
        }
        
        return $output;
    }
    
    public function setUserId($id) {
        $this->userId = $id;
        return $this;
    }
    
    public function getUserId() {
        return $this->userId;
    }
    
    
    
    
    public function setStartTime($time) {
        if($time !== null) {
            $time = (int)$time;
        }
        
        $this->startTime = $time;
        return $this;
    }

    public function getStartTime() {
        return $this->startTime;
    }

    public function setAccessTime($time) {
        if($time !== null) {
            $time = (int)$time;
        }
        
        $this->accessTime = $time;
        return $this;
    }

    public function getAccessTime() {
        return $this->accessTime;
    }
    
    public function isAccessOlderThan($seconds) {
        return time() - $this->accessTime > $seconds;
    }
    
    public function setTransitionTime($time) {
        if($time !== null) {
            $time = (int)$time;
        }
        
        $this->transitionTime = $time;
        return $this;
    }
    
    public function getTransitionTime() {
        return $this->transitionTime;
    }
    
    
    public function toArray() {
        return $this->toDataRowArray();
    }
    
    public function toDataRowArray() {
        return array(
            'internalId' => $this->internalId,
            'externalId' => $this->externalId,
            'transitionId' => $this->transitionId,
            'userId' => $this->userId,
            'startTime' => $this->startTime,
            'accessTime' => $this->accessTime,
            'transitionTime' => $this->transitionTime
        );
    }
}

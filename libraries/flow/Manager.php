<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flow;

use df;
use df\core;
use df\flow;
use df\user;
    
class Manager implements IManager {

    use core\TManager;

    const REGISTRY_PREFIX = 'manager://flow';
    const SESSION_NAMESPACE = 'flow';
    const FLASH_SESSION_KEY = 'flashQueue';

    protected $_flashLimit = 15;
    protected $_flashQueue;
    protected $_isFlashQueueProcessed = false;

    protected function __construct(core\IApplication $application) {
        $this->_application = $application;
    }

    public function onApplicationShutdown() {
        $this->_saveFlashQueue();
    }



## FLASH

// Limit
    public function setFlashLimit($limit) {
        $this->_flashLimit = (int)$limit;

        if($this->_flashLimit <= 0) {
            $this->_flashLimit = 1;
        }

        return $this;
    }

    public function getFlashLimit() {
        return $this->_flashLimit;
    }


    public function newFlashMessage($id, $message=null, $type=null) {
        return FlashMessage::factory($id, $message, $type);
    }


// Queue
    public function processFlashQueue() {
        if(!$this->_isFlashQueueProcessed) {
            $this->_loadFlashQueue();

            foreach($this->_flashQueue->instant as $id => $message) {
                if($message->isDisplayed()) {
                    if($message->canDisplayAgain()) {
                        $message->resetDisplayState();
                    } else {
                        unset($this->_flashQueue->instant[$id]);
                    }
                }
            }

            $limit = $this->_flashLimit - count($this->_flashQueue->instant);

            for($i = 0; $i < $limit; $i++) {
                if(!$message = array_shift($this->_flashQueue->queued)) {
                    break;
                }

                $this->_flashQueue->instant[$message->getId()] = $message;
            }

            $this->_isFlashQueueProcessed = true;
        }

        return $this;
    }

    protected function _loadFlashQueue() {
        if($this->_flashQueue === null) {
            $session = user\Manager::getInstance($this->_application)->getSessionNamespace(self::SESSION_NAMESPACE);
            $this->_flashQueue = $session->get(self::FLASH_SESSION_KEY);

            if(!$this->_flashQueue instanceof FlashQueue) {
                $this->_flashQueue = new FlashQueue();
            }
        }
    }

    protected function _saveFlashQueue() {
        if(!$this->_flashQueue instanceof FlashQueue) {
            return false;
        }

        $session = user\Manager::getInstance($this->_application)->getSessionNamespace(self::SESSION_NAMESPACE);
        $session->set(self::FLASH_SESSION_KEY, $this->_flashQueue);
        
        return true;
    }


// Shortcuts
    public function flash($id, $message=null, $type=null) {
        $message = $this->newFlashMessage($id, $message, $type);
        $this->queueFlash($message);
        return $message;
    }

    public function flashNow($id, $message=null, $type=null) {
        $message = $this->newFlashMessage($id, $message, $type);
        $this->addInstantFlash($message);
        return $message;
    }

    public function flashAlways($id, $message=null, $type=null) {
        $message = $this->newFlashMessage($id, $message, $type);
        $this->addConstantFlash($message);
        return $message;
    }


// Constant
    public function addConstantFlash(IFlashMessage $message) {
        $this->_loadFlashQueue();
        $this->_flashQueue->constant[$message->getId()] = $message;
        return $this;
    }
    
    public function getConstantFlash($id) {
        $this->_loadFlashQueue();

        if(isset($this->_flashQueue->constant[$id])) {
            return $this->_flashQueue->constant[$id];
        }
        
        return null;
    }
    
    public function getConstantFlashes() {
        $this->_loadFlashQueue();
        return $this->_flashQueue->constant;
    }
    
    public function removeConstantFlash($id) {
        $this->_loadFlashQueue();
        unset($this->_flashQueue->constant[$id]);
        return $this;
    }
    
    public function clearConstantFlashes() {
        $this->_loadFlashQueue();
        $this->_flashQueue->constant = array();
        return $this;
    }
    
// Queued
    public function queueFlash(IFlashMessage $message, $instantIfSpace=false) {
        $this->_loadFlashQueue();
        $id = $message->getId();

        unset($this->_flashQueue->instant[$id], $this->_flashQueue->queued[$id]);
        
        if($instantIfSpace && count($this->_flashQueue->instant) < $this->_flashLimit) {
            $this->_flashQueue->instant[$id] = $message;
        } else {
            $this->_flashQueue->queued[$id] = $message;
        }
        
        return $this;
    }
    
    public function addInstantFlash(IFlashMessage $message) {
        return $this->queueFlash($message, true);
    }
    
    public function getInstantFlashes() {
        $this->_loadFlashQueue();
        return $this->_flashQueue->instant;
    }
    
    public function removeQueuedFlash($id) {
        $this->_loadFlashQueue();

        unset(
            $this->_flashQueue->constant[$id],
            $this->_flashQueue->instant[$id]
        );
        
        return $this;
    }
}
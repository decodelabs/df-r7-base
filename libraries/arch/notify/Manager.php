<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\notify;

use df;
use df\core;
use df\arch;
use df\user;
    
class Manager implements IManager {

    use core\TManager;

    const REGISTRY_PREFIX = 'manager://notify';
    const SESSION_NAMESPACE = 'notify';
    const SESSION_KEY = 'queue';

    protected $_limit = 15;
    protected $_queue;
    protected $_isSaved = false;
    protected $_isFlushed = false;

    protected function __construct(core\IApplication $application) {
    	$this->_application = $application;

    	$session = user\Manager::getInstance($application)->getSessionNamespace(self::SESSION_NAMESPACE);
    	$this->_queue = $session->get(self::SESSION_KEY);

    	if(!$this->_queue instanceof IQueue) {
    		$this->_queue = new Queue();
    	}
    }

    public function __destruct() {
    	if(!$this->_isSaved) {
    		$this->_saveQueue();
    	}
    }

    public function onApplicationShutdown() {
    	if(!$this->_isSaved) {
    		$this->_saveQueue();
    	}
    }

    protected function _saveQueue() {
    	if(!$this->_queue instanceof IQueue) {
    		return false;
    	}

    	try {
    		$session = user\Manager::getInstance($this->_application)->getSessionNamespace(self::SESSION_NAMESPACE);
    	} catch(\Exception $e) {
    		//return false;
    	}

    	$session->set(self::SESSION_KEY, $this->_queue);
		$this->_isSaved = true;
		return true;
    }

    public function setMessageLimit($limit) {
    	$this->_limit = (int)$limit;

    	if($this->_limit <= 0) {
    		$this->_limi = 1;
    	}

    	return $this;
    }

	public function getMessageLimit() {
		return $this->_limit;
	}


	public function newMessage($id, $message=null, $type=null) {
		return Message::factory($id, $message, $type);
	}


// Flush
	public function flushQueue() {
		foreach($this->_queue->instant as $id => $message) {
			if($message->isDisplayed()) {
				if($message->canDisplayAgain()) {
					$message->resetDisplayState();
				} else {
					unset($this->_queue->instant[$id]);
				}
			}
		}

		$limit = $this->_limit - count($this->_queue->instant);

		for($i = 0; $i < $limit; $i++) {
			if(!$message = array_shift($this->_queue->queued)) {
				break;
			}

			$this->_queue->instant[$message->getId()] = $message;
		}

		$this->_isFlushed = true;

		return $this;
	}

	public function isFlushed() {
		return $this->_isFlushed;
	}


// Constant
    public function setConstantMessage(IMessage $message) {
        $this->_queue->constant[$message->getId()] = $message;
        return $this;
    }
    
    public function getConstantMessage($id) {
        if(isset($this->_queue->constant[$id])) {
            return $this->_queue->constant[$id];
        }
        
        return null;
    }
    
    public function getConstantMessages() {
        return $this->_queue->constant;
    }
    
    public function removeConstantMessage($id) {
        unset($this->_queue->constant[$id]);
        return $this;
    }
    
    public function clearConstantMessages() {
        $this->_queue->constant = array();
        return $this;
    }
    
// Queued
    public function queueMessage(IMessage $message, $instantIfSpace=false) {
        $id = $message->getId();
        unset($this->_queue->instant[$id], $this->_queue->queued[$id]);
        
        if($instantIfSpace && count($this->_queue->instant) < $this->_limit) {
            $this->_queue->instant[$id] = $message;
        } else {
            $this->_queue->queued[$id] = $message;
        }
        
        return $this;
    }
    
    public function setInstantMessage(IMessage $message) {
        return $this->queueMessage($message, true);
    }
    
    public function getInstantMessages() {
        return $this->_queue->instant;
    }
    
    public function removeQueuedMessage($id) {
        unset(
            $this->_queue->constant[$id],
            $this->_queue->instant[$id]
        );
        
        return $this;
    }
}
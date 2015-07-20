<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\lang;

use df;
use df\core;

class Promise implements IPromise {

    protected $_state = null;
    protected $_progressCurrent = 0;
    protected $_progressTotal;
    protected $_result;

    protected $_eventHandlers = [];

    protected $_canceller;
    protected $_cancelRequests = 0;

    protected $_parent;
    protected $_children = [];

    public static function call($action, $canceller=null) {
        return (new static())
            ->onFulfill($action)
            ->setCanceller($canceller)
            ->begin();
    }

    public static function defer($action, $canceller=null) {
        return (new static())
            ->onFulfill($action)
            ->setCanceller($canceller);
    }

    protected function __construct() {}

    public function setCanceller($canceller) {
        $this->_canceller = Callback::factory($canceller);
        return $this;
    }

    public function getCanceller() {
        return $this->_canceller;
    }

    public function getState() {
        return $this->_state;
    }

    public function setParent(IPromise $promise=null) {
        $this->_parent = $promise;
        return $this;
    }

    public function getParent() {
        return $this->_parent;
    }

    public function getRoot() {
        $target = $this;

        while(true) {
            $next = $target->getParent();

            if(!$next || $next === $target) {
                return $target;
            } else {
                $target = $next;
            }
        }

        return $target;
    }



// Routing
    public function then($onFulfill, $onReject=null) {
        $output = new static();
        $output->onFulfill($onFulfill)->onReject($onReject);
        $output->setParent($this);

        if($this->_state === IPromise::CANCELLED) {
            $output->_state = IPromise::CANCELLED;
        }
        
        if(!$this->_state) {
            $this->_children[] = $output;
        } else {
            $this->_invokeChild($output);
        }

        return $output;
    }

    public function also($onFulfill, $onReject=null) {
        if($this->_parent) {
            return $this->_parent->then($onFulfill, $onReject);
        } else {
            $output = (new static())
                ->onFulfill($onFulfill)
                ->onReject($onReject);

            if($this->hasBegun()) {
                $output->begin();
            }

            if($this->_state === IPromise::CANCELLED) {
                $output->_state = IPromise::CANCELLED;
            }
            
            return $output;
        }
    }

    public function otherwise($onReject) {
        return $this->then(null, $onReject);
    }

    public function always($onComplete) {
        $onComplete = Callback::factory($onComplete);

        return $this->then(function($value) use($onComplete) {
            $onComplete($value, true, $this);
            return $value;
        }, function($reason) use($onComplete) {
            $onComplete($reason, false, $this);
            return $reason;
        });
    }



// Event handlers
    public function on($name, $callback) {
        $name = lcfirst($name);
        $this->_eventHandlers[$name] = Callback::factory($callback);
        return $this;
    }
 
    public function hasEventHandler($name) {
        $name = lcfirst($name);
        return isset($this->_eventHandlers[$name]);
    }

    public function getEventHandler($name) {
        $name = lcfirst($name);

        if($this->hasEventHandler($name)) {
            return $this->_eventHandlers[$name];
        }
    }

    public function removeEventHandler($name) {
        $name = lcfirst($name);
        unset($this->_eventHandlers[$name]);
        return $this;
    }

    public function __call($method, array $args) {
        if(substr($method, 0, 2) != 'on') {
            throw new BadMethodCallException('Method '.$method.' does not exist');
        }

        return $this->on(substr($method, 2), array_shift($args));
    }


    public function onFulfill($onFulfill) {
        return $this->on('fulfill', $onFulfill);
    }

    public function getFulfillCallback() {
        return $this->getEventHandler('fulfill');
    }

    public function onReject($onReject) {
        return $this->on('reject', $onReject);
    }

    public function getRejectCallback() {
        return $this->getEventHandler('reject');
    }

    public function onProgress($progress) {
        $this->on('progress', $progress);

        if($this->_state !== null
        && $this->_state !== IPromise::CANCELLED
        && $this->_progressCurrent) {
            $this->emitThis(
                'progress', 
                $this->_progressCurrent, 
                $this->_progressTotal
            );
        }

        return $this;
    }

    public function getProgressCallback() {
        return $this->getEventHandler('progress');
    }


// Event broadcasting
    public function emit($name, $value=null) {
        if($this->_parent) {
            $this->_parent->emit($name, $value);
            return $this;
        }

        return $this->emitThis($name, $value);
    }

    public function emitThis($name, $value=null) {
        $name = lcfirst($name);
        $args = array_slice(func_get_args(), 1);
        $args = array_pad($args, 5, null);

        if($this->hasEventHandler($name)) {
            $this->_eventHandlers[$name]->invokeArgs($args);
        }

        return $this;
    }

    public function setProgress($progress, $total=null) {
        if($this->_parent) {
            $this->_parent->setProgress($progress, $total);
            return $this;
        }

        return $this->setProgressThis($progress, $total);
    }

    public function setProgressThis($progress, $total=null) {
        $this->_progressCurrent = (float)$progress;

        if($total !== null) {
            $this->_progressTotal = (float)$total;
        } else if($this->_progressCurrent > $this->_progressTotal
               && $this->_progressTotal) {
            $this->_progressTotal = $this->_progressCurrent;
        }

        return $this->emitThis(
            'progress', 
            $this->_progressCurrent, 
            $this->_progressTotal
        );    
    }


// Progress
    public function begin() {
        if($this->_parent) {
            $this->_parent->begin();
            return $this;
        }

        if($this->_state === IPromise::CANCELLED) {
            return $this;
        }

        if($this->_state !== null) {
            throw new LogicException(
                'Promise action has already begun'
            );
        }

        $this->_state = IPromise::PENDING;
        $this->fulfill($this);
        return $this;
    }

    public function hasBegun() {
        return $this->_state !== null;
    }

    public function fulfill($value=null) {
        if($this->_parent) {
            $this->_parent->fulfill($value);
            return $this;
        }

        return $this->fulfillThis($value);
    }

    public function fulfillThis($value=null) {
        if($this->_state || $this->_result) {
            return $this;
        }

        return $this->_invokeChildren(
            $this->getEventHandler('fulfill'), 
            $value,
            IPromise::FULFILLED
        );
    }

    public function reject($reason=null) {
        if($this->_parent) {
            $this->_parent->reject($reason);
            return $this;
        }

        return $this->rejectThis($reason);
    }

    public function rejectThis($reason=null) {
        if($this->_state || $this->_result) {
            return $this;
        }

        return $this->_invokeChildren(
            $this->getEventHandler('reject'), 
            $reason,
            IPromise::REJECTED
        );
    }

    protected function _invokeChildren(ICallback $callback=null, $value, $state) {
        if($callback) {
            try {
                $value = $callback->invoke($value, $this);
                $this->_state = $state;
            } catch(\Exception $e) {
                $value = $e;
                $this->_state = IPromise::REJECTED;
            }
        }

        if($this->_state === IPromise::REJECTED 
        && !$value instanceof \Exception) {
            $value = new RejectedPromiseException($value);
        }

        if($value === $this) {
            $value = null;
        }

        $this->_result = $value;

        while(!empty($this->_children)) {
            $this->_invokeChild(array_shift($this->_children));
        }

        return $this;
    }

    protected function _invokeChild(IPromise $child) {
        if($this->_result instanceof IPromise) {
            $this->_result->then([$child, 'fulfill'], [$child, 'promise']);

            if(!$this->_result->hasBegun()) {
                $this->_result->begin();
            }
        } else {
            if($this->_state === IPromise::FULFILLED) {
                $child->fulfillThis($this->_result);
            } else if($this->_state === IPromise::REJECTED) {
                $child->rejectThis($this->_result);
            }
        }
    }



// Cancel
    public function cancel() {
        if($this->_parent) {
            $this->_parent->cancel();
            return $this;
        }

        return $this->cancelThis();
    }

    public function cancelThis() {
        $this->_cancelRequests++;

        if($this->_cancelRequests >= count($this->_children)) {
            $this->_state = IPromise::CANCELLED;

            if($this->_canceller) {
                $this->_canceller->invoke($this);
            }

            $this->_cancelChildren();
            $this->emit('cancel');
        }

        return $this;
    }

    public function forceCancel() {
         if($this->_parent) {
            $this->_parent->cancel();
            return $this;
        }

        return $this->forceCancelThis();
    }

    public function forceCancelThis() {
        $this->_cancelRequests = count($this->_children);
        $this->_state = IPromise::CANCELLED;

        if($this->_canceller) {
            $this->_canceller->invoke($this);
        }

        $this->_cancelChildren();
        $this->emit('cancel');

        return $this;
    }

    protected function _cancelChildren() {
        foreach($this->_children as $child) {
            $child->_state = IPromise::CANCELLED;
            $child->_cancelChildren();
        }
    }


// Sync
    public function sync() {
        if(!$this->hasBegun()) {
            $this->begin();
        }

        while(!$this->_state) {
            usleep(100000);
        }

        if($this->_state === IPromise::FULFILLED) {
            return $this->_result;
        } else if($this->_state === IPromise::REJECTED) {
            throw $this->_result;
        } else {
            return null;
        }
    }
}
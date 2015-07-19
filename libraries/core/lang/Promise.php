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
    protected $_result;

    protected $_onFulfill;
    protected $_onReject;
    protected $_onProgress;

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




    public function onFulfill($onFulfill) {
        $this->_onFulfill = Callback::factory($onFulfill);
        return $this;
    }

    public function getFulfillCallback() {
        return $this->_onFulfill;
    }

    public function onReject($onReject) {
        $this->_onReject = Callback::factory($onReject);
        return $this;
    }

    public function getRejectCallback() {
        return $this->_onReject;
    }

    public function onProgress($progress) {
        $this->_onProgress = Callback::factory($progress);
        return $this;
    }

    public function getProgressCallback() {
        return $this->_onProgress;
    }



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

        $this->_state = IPromise::FULFILLED;
        return $this->_invokeChildren($this->_onFulfill, $value);
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

        $this->_state = IPromise::REJECTED;
        return $this->_invokeChildren($this->_onReject, $reason);
    }

    protected function _invokeChildren(ICallback $callback=null, $value) {
        if($callback) {
            try {
                $value = $callback->invoke($value, $this);
            } catch(\Exception $e) {
                $value = $e;
                $this->_state = IPromise::REJECTED;
            }
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

    public function notify($progress=null) {
        if($this->_parent) {
            $this->_parent->notify($progress);
            return $this;
        }

        return $this->notifyThis($progress);
    }

    public function notifyThis($progress=null) {
        if($this->_onProgress) {
            $this->_onProgress->invoke($progress, $this);
        }

        return $this;
    }

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

        return $this;
    }

    protected function _cancelChildren() {
        foreach($this->_children as $child) {
            $child->_state = IPromise::CANCELLED;
            $child->_cancelChildren();
        }
    }
}
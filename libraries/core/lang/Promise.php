<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\lang;

use df;
use df\core;

class Promise implements IPromise, core\IDumpable {

    protected $_action;
    protected $_errorHandlers = [];
    protected $_eventHandlers = [];

    protected $_canceller;
    protected $_cancelRequests = 0;

    protected $_progressCurrent = 0;
    protected $_progressTotal;

    protected $_result;
    protected $_error;

    protected $_hasBegun = false;
    protected $_hasDelivered = false;
    protected $_isCancelled = false;

    protected $_parent;
    protected $_dependants = [];


// Factories
    public static function call($action, $canceller=null) {
        return static::defer($action, $canceller)->begin();
    }

    public static function defer($action, $canceller=null) {
        return new static($action, $canceller);
    }

    public static function fulfilled($value) {
        return (new static())->deliver($value);
    }

    public static function rejected($value) {
        return (new static())->deliverError($value);
    }


// Aggregate factories
    public static function all($promises) {
        return static::deferAll($promises)->begin();
    }

    public static function deferAll($promises) {
        $results = [];

        return static::deferEach($promises, function($value, $key, $aggregate) use(&$results) {
            $results[$key] = $value;
        }, function(\Exception $error, $key, IPromise $aggregate) {
            $aggregate->deliverError($error);
        })->then(function() use(&$results) {
            return $results;
        });
    }

    public static function some($count, $promises) {
        return static::deferSome($count, $promises)->begin();
    }

    public static function deferSome($count, $promises) {
        $results = [];
        $rejections = [];

        return static::deferEach($promises, function($value, $key, IPromise $aggregate) use(&$results, $count) {
            if(!$aggregate->isPending()) {
                return;
            }

            $results[$key] = $value;

            if(count($results) >= $count) {
                $aggregate->deliver(null);
            }
        }, function(\Exception $error, $key) use(&$rejections) {
            $rejections[$key] = $error;
        })->then(function() use(&$results, &$rejections, $count) {
            if(count($results) < $count) {
                throw new RuntimeException(
                    'Not enough promises delivered a result'
                );
            }

            return $results;
        });
    }

    public static function any($promises) {
        return static::deferAny($promises);
    }

    public static function deferAny($promises) {
        return static::deferSome(1, $promises)
            ->then(function($results) {
                return array_shift($results);
            });
    }

    public static function each($promises, $onFulfill=null, $onReject=null) {
        return static::deferEach($promises, $onFulfill, $onReject)->begin();
    }

    public static function deferEach($promises, $onFulfill=null, $onReject=null) {
        return static::defer(function(IPromise $aggregate) use($promises, $onFulfill, $onReject) {
            $promises = core\collection\Util::ensureIterable($promises);
            $onFulfill = Callback::factory($onFulfill);
            $onReject = Callback::factory($onReject);

            foreach($promises as $key => $promise) {
                if(!$promise instanceof IPromise) {
                    $promise = static::fulfilled($promise);
                }

                $promise->then(function($value, $promise) use($key, $aggregate, $onFulfill) {
                    return Callback::call($onFulfill, $value, $key, $aggregate);
                }, function(\Exception $error, $promise) use($key, $aggregate, $onReject) {
                    return Callback::call($onReject, $error, $key, $aggregate);
                })->sync();

                if($aggregate->hasDelivered()) {
                    return;
                }
            }

            return;
        });
    }



// Construct
    public function __construct($action=null, $canceller=null) {
        if($action !== null) {
            $this->setAction($action);
        }

        if($canceller !== null) {
            $this->setCanceller($canceller);
        }
    }

// Action
    public function setAction($action) {
        $this->_action = Callback::factory($action);
        return $this;
    }

    public function getAction() {
        return $this->_action;
    }

    public function hasAction() {
        return $this->_action !== null;
    }

    public function begin($value=null) {
        if($this->_parent) {
            $this->_parent->begin($value);
            return $this;
        }

        return $this->beginThis($value);
    }

    public function beginThis($value=null) {
        if($this->_hasBegun) {
            throw new LogicException(
                'Promise action has already begun'
            );
        }

        if($this->_isCancelled) {
            return $this;
        }

        if($this->_hasDelivered) {
            throw new LogicException(
                'Promise has already delivered'
            );
        }

        $this->_hasBegun = true;

        if($this->_action) {
            $action = $this->_action;
            $this->_action = null;

            try {
                $value = $action->invoke($value, $this);
            } catch(\Exception $e) {
                return $this->deliverError($e);
            }

            return $this->deliver($value);
        } else {
            return $this->deliver(null);
        }

    }

    public function beginThisError(\Exception $e) {
        if($this->_hasBegun) {
            throw new LogicException(
                'Promise action has already begun'
            );
        }

        if($this->_isCancelled) {
            return $this;
        }

        if($this->_hasDelivered) {
            throw new LogicException(
                'Promise has already delivered'
            );
        }

        $this->_hasBegun = true;
        return $this->deliverError($e);
    }

    public function hasBegun() {
        if($this->_parent) {
            return $this->_parent->hasBegun();
        }

        return $this->hasBegunThis();
    }

    public function hasBegunThis() {
        return $this->_hasBegun;
    }

    public function isPending() {
        return $this->hasBegun()
            && !$this->isCancelled()
            && !$this->hasDelivered();
    }


// Canceller
    public function setCanceller($canceller) {
        $this->_canceller = Callback::factory($canceller);
        return $this;
    }

    public function getCanceller() {
        return $this->_canceller;
    }

    public function hasCanceller() {
        return $this->_canceller !== null;
    }

    public function cancel() {
        if($this->_parent) {
            $this->_parent->cancel();
            return $this;
        }

        return $this->cancelThis();
    }

    public function cancelThis() {
        if($this->_isCancelled || $this->_hasDelivered) {
            return $this;
        }

        $this->_cancelRequests++;

        if($this->_cancelRequests >= count($this->_dependants)) {
            $this->_isCancelled = true;

            if($this->_canceller) {
                $this->_canceller->invoke($this);
            }

            $this->_cancelDependants();
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
        $this->_cancelRequests = count($this->_dependants);
        $this->_isCancelled = true;

        if($this->_canceller) {
            $this->_canceller->invoke($this);
        }

        $this->_cancelDependants();
        $this->emit('cancel');

        return $this;
    }

    public function isCancelled() {
        return $this->_isCancelled;
    }

    protected function _cancelDependants() {
        foreach($this->_dependants as $dependant) {
            $dependant->_isCancelled = true;
            $dependant->_cancelDependants();
        }
    }


// Error handlers
    public function onError(...$callbacks) {
        return $this->addErrorHandlers($callbacks);
    }

    public function addErrorHandlers(array $handlers) {
        foreach($handlers as $handler) {
            $this->addErrorHandler($handler);
        }

        return $this;
    }

    public function addErrorHandler($callback) {
        if(!$callback = Callback::factory($callback)) {
            return $this;
        }

        $parameters = $callback->getParameters();
        $type = null;

        if(!isset($parameters[0])) {
            $type = '';
        } else {
            try {
                $class = $parameters[0]->getClass();
            } catch(\Exception $e) {
                $class = null;
            }

            if($class) {
                $type = $class->getName();
            } else if(!$parameters[0]->isArray()) {
                $type = '';
            }
        }

        if($type === null) {
            throw new LogicException('Error handling must be able to accept an exception as its first argument');
        }

        $this->_errorHandlers[$type] = $callback;
        return $this;
    }

    public function getErrorHandlers() {
        return $this->_errorHandlers;
    }

    public function clearErrorHandlers() {
        $this->_errorHandlers = [];
        return $this;
    }


// Parent
    public function setParent(IPromise $promise=null) {
        $this->_parent = $promise;
        return $this;
    }

    public function getParent() {
        return $this->_parent;
    }

    public function hasParent() {
        return $this->_parent !== null;
    }

    public function getDependants() {
        return $this->_dependants;
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


// Events
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

    public function emit($name, array $values=null) {
        if($this->_parent) {
            $this->_parent->emit($name, $values);
            return $this;
        }

        return $this->emitThis($name, $values);
    }

    public function emitThis($name, array $values=null) {
        $name = lcfirst($name);

        if($this->hasEventHandler($name)) {
            $this->_eventHandlers[$name]->invoke($values, $this);
        }

        return $this;
    }


// Progress
    public function setProgress($progress, $total=null) {
        if($this->_parent) {
            $this->_parent->setProgress($progress, $total);
            return $this;
        }

        return $this->setProgressThis($progress, $total);
    }

    public function setProgressThis($current, $total=null) {
        if($current !== null) {
            $this->_progressCurrent = (float)$current;
        }

        if($total !== null) {
            $this->_progressTotal = (float)$total;
        }

        return $this->emitThis('progress', [
            'current' => $this->_progressCurrent,
            'total' => $this->_progressTotal
        ]);
    }

    public function onProgress($callback) {
        $this->on('progress', $callback);

        if($this->hasBegun()
        && !$this->isCancelled()) {
            $this->emitThis('progress', [
                'current' => $this->_progressCurrent,
                'total' => $this->_progressTotal
            ]);
        }

        return $this;
    }

    public function getProgressCallback() {
        return $this->getEventHandler('progress');
    }


// Chaining
    public function then($action, ...$errorHandlers) {
        $output = (new static($action))
            ->addErrorHandlers($errorHandlers)
            ->setParent($this);

        if($this->_isCancelled) {
            $output->_isCancelled = true;
        }

        if(!$this->_hasDelivered) {
            $this->_dependants[] = $output;
        } else {
            $this->_deliverDependant($output);
        }

        return $output;
    }

    public function also($action, ...$errorHandlers) {
        if($this->_parent) {
            return $this->_parent->then($action)
                ->addErrorHandlers($errorHandlers);
        } else {
            $output = (new static($action))
                ->addErrorHandlers($errorHandlers);

            if($this->_isCancelled) {
                $output->_isCancelled = true;
            }

            if($this->hasBegun()) {
                $output->begin();
            }

            return $output;
        }
    }

    public function otherwise(...$errorHandlers) {
        $output = (new static())
            ->addErrorHandlers($errorHandlers)
            ->setParent($this);

        if($this->_isCancelled) {
            $output->_isCancelled = true;
        }

        if(!$this->_hasDelivered) {
            $this->_dependants[] = $output;
        } else {
            $this->_deliverDependant($output);
        }

        return $output;
    }

    public function always($action) {
        if(!$action = Callback::factory($action)) {
            return $this;
        }

        return $this->then(function($value) use($action) {
            $action($value, true, $this);
            return $value;
        }, function(\Exception $reason) use($action) {
            $action($reason, false, $this);
            throw $reason;
        });
    }


// Completion
    public function deliver($value) {
        if($this->_hasDelivered) {
            throw new LogicException(
                'Promise has already delivered'
            );
        }

        $this->_hasBegun = true;
        $this->_result = $value;
        $this->_action = null;
        $this->_error = null;

        return $this->_deliverDependants();
    }

    public function deliverError(\Exception $error) {
        if($this->_hasDelivered) {
            throw new LogicException(
                'Promise has already delivered'
            );
        }

        $this->_hasBegun = true;
        $this->_result = null;
        $this->_action = null;
        $this->_error = $error;

        return $this->_deliverDependants();
    }

    protected function _deliverDependants() {
        $this->_hasDelivered = true;
        $maxErrorDepth = 10;
        $errorDepth = 0;

        while($this->_error) {
            if($errorDepth++ >= $maxErrorDepth) {
                break;
            }

            $error = $this->_error;
            $this->_error = null;

            foreach($this->_errorHandlers as $type => $callback) {
                if(!$error instanceof $type && $type != '') {
                    continue;
                }

                try {
                    $this->_result = $callback->invoke($error, $this);
                    break 2;
                } catch(\Exception $e) {
                    $this->_error = $e;

                    if($e === $error) {
                        break 2;
                    } else {
                        continue 2;
                    }
                }
            }

            $this->_error = $error;
            break;
        }

        if($this->_result === $this) {
            $this->_result = null;
        }

        while(!empty($this->_dependants)) {
            $this->_deliverDependant(array_shift($this->_dependants));
        }

        return $this;
    }

    protected function _deliverDependant(IPromise $dependant) {
        if($this->_result instanceof IPromise) {
            $this->_result->then([$dependant, 'beginThis'], [$dependant, 'beginThisError']);

            if(!$this->_result->hasBegun()) {
                $this->_result->begin();
            }
        } else {
            if($this->_error) {
                $dependant->beginThisError($this->_error);
            } else {
                $dependant->beginThis($this->_result);
            }
        }
    }

    public function hasDelivered() {
        return $this->_hasDelivered;
    }

    public function hasError() {
        return $this->_error !== null;
    }

    public function isFulfilled() {
        return $this->_hasDelivered && !$this->_error;
    }

    public function isRejected() {
        return $this->_hasDelivered && $this->_error;
    }


// Sync
    public function sync() {
        if(!$this->_runSync()) {
            return null;
        }

        if($this->_result instanceof IPromise) {
            $this->_result = $this->_result->sync();
        }

        return $this->_result;
    }

    protected function _runSync() {
        if($this->_isCancelled) {
            return false;
        }

        if(!$this->hasBegun()) {
            $this->begin();
        }

        if($this->_isCancelled) {
            return false;
        }

        if(!$this->_hasDelivered) {
            // TODO: loop while not delivered
            core\stub($this);
        }

        if($this->_error) {
            throw $this->_error;
        }

        if($this->_isCancelled) {
            return false;
        }

        return true;
    }


// Dump
    public function getDumpProperties() {
        if($this->_error) {
            return $this->_error;
        } else if($this->_result) {
            return $this->_result;
        }

        if($this->_isCancelled) {
            return '** CANCELLED **';
        }

        $output = [];
        $output[] = $this->_hasBegun ? 'Pending' : 'Idle';

        if($this->_progressCurrent) {
            if($this->_progressTotal) {
                $output[] = intval($this->_progressCurrent / $this->_progressTotal * 100).'%';
            } else  {
                $output[] = $this->_progressCurrent.' completed';
            }
        }

        return implode(', ', $output);
    }
}
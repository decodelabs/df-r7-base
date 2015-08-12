<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\lang;

use df;
use df\core;


// Exceptions
interface IException {}
class LogicException extends \LogicException implements IException {}
class UnexpectedValueException extends \UnexpectedValueException implements IException {}
class RuntimeException extends \RuntimeException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}
class BadMethodCallException extends \BadMethodCallException {}


// Interfaces
interface ICallback {
    
    const DIRECT = 1;
    const REFLECTION = 2;

    public function setExtraArgs(array $args);
    public function getExtraArgs();

    public function invoke();
    public function invokeArgs(array $args);

    public function getParameters();
}

function Callback($callback) {
    return Callback::factory($callback)->invokeArgs(array_slice(func_get_args(), 1));
}

function CallbackArgs($callback, array $args) {
    return Callback::factory($callback)->invokeArgs($args);
}

interface IEnumFactory {
    public function factory($value);
}

interface IEnum extends core\IStringProvider, core\IStringValueProvider {
    //public static function factory($value);
    public static function normalize($value);
    public static function getOptions();
    public static function getLabels();
    public function getIndex();
    public function getOption();
    public function getLabel();
    public static function label($option);
    public function is($value);
}

interface IStruct {
    public function import(array $data);
}


interface ITypeRef {
    public function newInstance();
    public function newInstanceArgs(array $args);
    public function checkType($extends);
    public function getClass();
    public function getClassPath();
}


// Chaining
interface IChainable {
    public function chain($callback);
    public function chainIf($test, $trueCallback, $falseCallback=null);
    public function chainEach(array $list, $callback);
}

trait TChainable {

    public function chain($callback) {
        Callback::factory($callback)->invoke($this);
        return $this;
    }

    public function chainIf($test, $trueCallback, $falseCallback=null) {
        if($test) {
            Callback::factory($trueCallback)->invoke($this);
        } else if($falseCallback) {
            Callback::factory($falseCallback)->invoke($this);
        }

        return $this;
    }

    public function chainEach(array $list, $callback) {
        $callback = Callback::factory($callback);

        foreach($list as $key => $value) {
            $callback->invoke($this, $value, $key);
        }

        return $this;
    }
}


// Promise
interface IPromise {

// Factories
    public static function call($action, $canceller=null);
    public static function defer($action, $canceller=null);
    public static function fulfilled($value);
    public static function rejected($value);

// Aggregate factories
    public static function all($promises);
    public static function deferAll($promises);
    public static function some($count, $promises);
    public static function deferSome($count, $promises);
    public static function any($promises);
    public static function deferAny($promises);
    public static function each($promises, $onFulfill=null, $onReject=null);
    public static function deferEach($promises, $onFulfill=null, $onReject=null);

// Action
    public function setAction($action);
    public function getAction();
    public function hasAction();
    public function begin($value=null);
    public function beginThis($value=null);
    public function beginThisError(\Exception $e);
    public function hasBegun();
    public function hasBegunThis();
    public function isPending();

// Canceller
    public function setCanceller($canceller);
    public function getCanceller();
    public function hasCanceller();
    public function cancel();
    public function cancelThis();
    public function forceCancel();
    public function forceCancelThis();
    public function isCancelled();

// Error handlers
    public function onError($callback);
    public function addErrorHandlers(array $handlers);
    public function addErrorHandler($callback);
    public function getErrorHandlers();
    public function clearErrorHandlers();

// Parent
    public function setParent(IPromise $promise=null);
    public function getParent();
    public function hasParent();
    public function getDependants();
    public function getRoot();

// Events
    public function on($event, $callback);
    public function hasEventHandler($name);
    public function getEventHandler($name);
    public function removeEventHandler($name);
    public function emit($event, array $values=null);
    public function emitThis($event, array $values=null);

// Progress
    public function setProgress($progress, $total=null);
    public function setProgressThis($progress, $total=null);
    public function onProgress($progress);
    public function getProgressCallback();

// Chaining
    public function then($action, $errorHandler=null);
    public function also($action, $errorHandler=null);
    public function otherwise($errorHandler);
    public function always($action);

// Completion
    public function deliver($value);
    public function deliverError(\Exception $error);
    public function hasDelivered();
    public function hasError();
    public function isFulfilled();
    public function isRejected();

// Sync
    public function sync();
}
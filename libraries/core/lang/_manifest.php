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

class RejectedPromiseException extends \Exception implements IException {}


// Interfaces
interface ICallback {
    
    const DIRECT = 1;
    const REFLECTION = 2;

    public function setExtraArgs(array $args);
    public function getExtraArgs();

    public function invoke();
    public function invokeArgs(array $args);
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
    const PENDING = 0;
    const FULFILLED = 1;
    const REJECTED = -1;
    const CANCELLED = -2;

    public function setCanceller($canceller);
    public function getCanceller();
    public function getState();

    public function setParent(IPromise $promise=null);
    public function getParent();
    public function getRoot();

    public function then($onFulfill, $onReject=null);
    public function also($onFulfill, $onReject=null);
    public function otherwise($onReject);
    public function always($onComplete);

    public function on($event, $callback);
    public function hasEventHandler($name);
    public function getEventHandler($name);
    public function removeEventHandler($name);

    public function onFulfill($onFulfill);
    public function getFulfillCallback();
    public function onReject($onReject);
    public function getRejectCallback();
    public function onProgress($progress);
    public function getProgressCallback();

    public function emit($event, $value=null);
    public function emitThis($event, $value=null);
    public function setProgress($progress, $total=null);
    public function setProgressThis($progress, $total=null);

    public function begin();
    public function hasBegun();
    public function fulfill($value=null);
    public function fulfillThis($value=null);
    public function isFulfilled();
    public function reject($reason=null);
    public function rejectThis($reason=null);
    public function isRejected();

    public function cancel();
    public function cancelThis();
    public function forceCancel();
    public function forceCancelThis();
    public function isCancelled();

    public function sync();
}
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
    public static function getOptions();
    public static function getLabels();
    public function getIndex();
    public function getOption();
    public function getLabel();
    public static function label($option);
    public function is($value);
}


interface ITypeRef {
    public function newInstance();
    public function newInstanceArgs(array $args);
    public function checkType($extends);
}


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
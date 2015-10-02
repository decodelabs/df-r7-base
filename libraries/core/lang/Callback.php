<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\lang;

use df;
use df\core;

class Callback implements ICallback, core\IDumpable {

    protected $_callback;
    protected $_reflectionInstance;
    protected $_mode;
    protected $_extraArgs = [];

    public static function getCallableId(Callable $callable) {
        $output = '';

        if(is_array($callable)) {
            @list($target, $name) = $callable;

            if(is_object($target)) {
                $target = get_class($target);
            }

            $output = $target.'::'.$name;
        } else if($callable instanceof \Closure) {
            $output = 'closure-'.spl_object_hash($callable);
        } else if(is_object($callable)) {
            $output = get_class($callable);
        }

        return $output;
    }


    public static function factory($callback, array $extraArgs=[]) {
        if($callback instanceof ICallback) {
            if(!empty($extraArgs)) {
                $callback->setExtraArgs($extraArgs);
            }

            return $callback;
        }

        if($callback === null) {
            return $callback;
        }

        return new self($callback, $extraArgs);
    }

    public static function call($callback) {
        return self::callArgs($callback, array_slice(func_get_args(), 1));
    }

    public static function callArgs($callback, array $args=[]) {
        if($callback = self::factory($callback)) {
            return $callback->invokeArgs($args);
        }

        return null;
    }

    protected function __construct($callback, array $extraArgs) {
        $this->setExtraArgs($extraArgs);

        if(is_callable($callback)) {
            $this->_mode = ICallback::DIRECT;
            $this->_callback = $callback;
            return;
        }

        if(is_array($callback) && count($callback) == 2) {
            $class = array_shift($callback);
            $method = array_shift($callback);

            if(method_exists($class, $method)) {
                try {
                    $reflection = new \ReflectionMethod($class, $method);
                } catch(\Exception $e) {
                    throw new InvalidArgumentException(
                        'Callback is not callable'
                    );
                }

                $reflection->setAccessible(true);

                if($reflection->isStatic()) {
                    $this->_reflectionInstance = null;
                } else {
                    if(!is_object($class)) {
                        throw new InvalidArgumentException(
                            'Callback is not callable'
                        );
                    }

                    $this->_reflectionInstance = $class;
                }

                $this->_callback = $reflection;
                $this->_mode = ICallback::REFLECTION;
                return;
            }
        }

        throw new InvalidArgumentException(
            'Callback is not callable'
        );
    }

    public function setExtraArgs(array $args) {
        $this->_extraArgs = $args;
        return $this;
    }

    public function getExtraArgs() {
        return $this->_extraArgs;
    }

    public function __invoke() {
        $args = func_get_args();
        return $this->invokeArgs($args);
    }

    public function invoke() {
        $args = func_get_args();
        return $this->invokeArgs($args);
    }

    public function invokeArgs(array $args) {
        if(!empty($this->_extraArgs)) {
            $args = array_merge($args, $this->_extraArgs);
        }

        switch($this->_mode) {
            case ICallback::DIRECT:
                return call_user_func_array($this->_callback, $args);

            case ICallback::REFLECTION:
                return $this->_callback->invokeArgs($this->_reflectionInstance, $args);
        }
    }

    public function getParameters() {
        if($this->_mode === ICallback::REFLECTION) {
            $reflection = $this->_callback;
        } else if(is_array($this->_callback)) {
            $reflection = new \ReflectionMethod($this->_callback[0], $this->_callback[1]);
        } else if(is_object($this->_callback) && !$this->_callback instanceof \Closure) {
            $reflection = new \ReflectionMethod($this->_callback, '__invoke');
        } else {
            $reflection = new \ReflectionFunction($this->_callback);
        }

        return $reflection->getParameters();
    }

// Dump
    public function getDumpProperties() {
        if(is_array($this->_callback) && is_object($this->_callback[0])) {
            return get_class($this->_callback[0]).'->'.$this->_callback[1].'()';
        }

        return $this->_callback;
    }
}
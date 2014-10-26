<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\lang;

use df;
use df\core;

class Callback implements ICallback {
    
    protected $_callback;
    protected $_reflectionInstance;
    protected $_mode;
    protected $_extraArgs = [];

    public static function factory($callback, array $extraArgs=[]) {
        if($callback instanceof ICallback) {
            return $callback;
        }

        return new self($callback, $extraArgs);
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
}
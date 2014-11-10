<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\lang;

use df;
use df\core;


class TypeRef implements ITypeRef, core\IDumpable {

    protected $_class;
    protected $_reflection;

    public static function __callStatic($method, array $args) {
        if(method_exists(__CLASS__, '__'.$method)) {
            return call_user_func_array([__CLASS__, '__'.$method], $args); 
        }

        throw new BadMethodCallException(
            'Method '.$method.' is not available on class '.__CLASS__
        );
    }

    public static function __factory($type, $extends=null) {
        if(!$type instanceof self) {
            $type = new self($type);
        }

        if($extends !== null) {
            $type->checkType($extends);
        }

        return $type;
    }

    protected static function _normalizeClassName($type) {
        if(false !== strpos($type, '/')) {
            $parts = explode('/', trim($type, '/'));
            $type = 'df\\'.implode('\\', $parts);
        }

        return $type;
    }

    public function __construct($type) {
        $class = self::_normalizeClassName($type);

        if(!class_exists($class)) {
            throw new InvalidArgumentException(
                'Class '.$class.' could not be found'
            );
        }

        $this->_class = $class;
        $this->_reflection = new \ReflectionClass($this->_class);
    }

    public function newInstance() {
        return $this->_reflection->newInstanceArgs(func_get_args());
    }

    public function newInstanceArgs(array $args) {
        return $this->_reflection->newInstanceArgs($args);
    }

    public function checkType($extends) {
        if(!is_array($extends)) {
            $extends = [$extends];
        }

        foreach($extends as $checkType) {
            $checkType = self::_normalizeClassName($checkType);

            if(class_exists($checkType)) {
                if(!$this->_reflection->isSubclassOf($checkType)) {
                    throw new RuntimeException(
                        $type->_class.' does not extend '.$checkType
                    );
                }
            } else if(interface_exists($checkType)) {
                if(!$this->_reflection->implementsInterface($checkType)) {
                    throw new RuntimeException(
                        $this->_class.' does not implement '.$checkType
                    );
                }
            }
        }

        return $this;
    }

    public function __call($method, array $args) {
        if(!$this->_reflection->hasMethod($method)) {
            throw new BadMethodCallException(
                'Method '.$method.' is not available on class '.$this->_class
            );
        }

        $method = $this->_reflection->getMethod($method);

        if(!$method->isStatic() || !$method->isPublic()) {
            throw new BadMethodCallException(
                'Method '.$method.' is not accessible on class '.$this->_class
            );
        }

        return $method->invokeArgs(null, $args);
    }


// Dump
    public function getDumpProperties() {
        return implode('/', array_slice(explode('\\', $this->_class), 1));
    }
}
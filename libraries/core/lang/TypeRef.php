<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\lang;

use df;
use df\core;

use DecodeLabs\Glitch\Dumpable;
use DecodeLabs\Exceptional;

class TypeRef implements ITypeRef, \Serializable, Dumpable
{
    protected $_class;
    protected $_reflection;

    public static function __callStatic($method, array $args)
    {
        if (method_exists(__CLASS__, '__'.$method)) {
            $class = __CLASS__;
            return $class::{'__'.$method}(...$args);
        }

        throw Exceptional::BadMethodCall(
            'Method '.$method.' is not available on class '.__CLASS__
        );
    }

    public static function __factory($type, $extends=null)
    {
        if (!$type instanceof self) {
            $type = new self($type);
        }

        if ($extends !== null) {
            $type->checkType($extends);
        }

        return $type;
    }

    public function serialize()
    {
        return $this->_class;
    }

    public function unserialize(string $data): void
    {
        $this->_class = $data;
        $this->_reflection = new \ReflectionClass($this->_class);
    }

    protected static function _normalizeClassName($type)
    {
        if (false !== strpos($type, '/')) {
            $parts = explode('/', trim($type, '/'));
            $type = 'df\\'.implode('\\', $parts);
        }

        return $type;
    }

    public function __construct($type)
    {
        if (is_object($type)) {
            $type = get_class($type);
        }

        $class = self::_normalizeClassName($type);

        if (!class_exists($class)) {
            throw Exceptional::InvalidArgument(
                'Class '.$class.' could not be found'
            );
        }

        $this->_class = $class;
        $this->_reflection = new \ReflectionClass($this->_class);
    }

    public function newInstance(...$args)
    {
        return $this->_reflection->newInstanceArgs($args);
    }

    public function checkType($extends)
    {
        if (!is_array($extends)) {
            $extends = [$extends];
        }

        foreach ($extends as $checkType) {
            $checkType = self::_normalizeClassName($checkType);

            if (class_exists($checkType)) {
                if (!$this->_reflection->isSubclassOf($checkType)) {
                    throw Exceptional::Runtime(
                        $this->_class.' does not extend '.$checkType
                    );
                }
            } elseif (interface_exists($checkType)) {
                if (!$this->_reflection->implementsInterface($checkType)) {
                    throw Exceptional::Runtime(
                        $this->_class.' does not implement '.$checkType
                    );
                }
            }
        }

        return $this;
    }

    public function getClass()
    {
        return $this->_class;
    }

    public function getClassPath()
    {
        return implode('/', array_slice(explode('\\', $this->_class), 1));
    }

    public function __call($method, array $args)
    {
        if (!$this->_reflection->hasMethod($method)) {
            throw Exceptional::BadMethodCall(
                'Method '.$method.' is not available on class '.$this->_class
            );
        }

        $method = $this->_reflection->getMethod($method);

        if (!$method->isStatic() || !$method->isPublic()) {
            throw Exceptional::BadMethodCall(
                'Method '.$method.' is not accessible on class '.$this->_class
            );
        }

        return $method->invokeArgs(null, $args);
    }


    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'text' => $this->getClassPath();
    }
}

<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\lang;

use DecodeLabs\Exceptional;
use DecodeLabs\Glitch\Dumpable;

class Callback implements ICallback, Dumpable
{
    protected $_callback;
    protected $_reflectionInstance;
    protected $_mode;
    protected $_extraArgs = [];

    public static function getCallableId(callable $callable)
    {
        $output = '';

        if (is_array($callable)) {
            @list($target, $name) = $callable;

            if (is_object($target)) {
                $target = get_class($target);
            }

            $output = $target . '::' . $name;
        } elseif ($callable instanceof \Closure) {
            $output = 'closure-' . spl_object_hash($callable);
        } elseif (is_object($callable)) {
            $output = get_class($callable);
        }

        return $output;
    }


    public static function factory($callback, array $extraArgs = [])
    {
        if ($callback instanceof ICallback) {
            if (!empty($extraArgs)) {
                $callback->setExtraArgs($extraArgs);
            }

            return $callback;
        }

        if ($callback === null) {
            return null;
        }

        return new self($callback, $extraArgs);
    }

    public static function normalize($callback): ?ICallback
    {
        if ($callback instanceof ICallback || $callback === null) {
            return $callback;
        }

        return self::factory($callback);
    }

    public static function call($callback, ...$args)
    {
        if ($callback = self::factory($callback)) {
            return $callback->invoke(...$args);
        }

        return null;
    }

    protected function __construct($callback, array $extraArgs)
    {
        $this->setExtraArgs($extraArgs);

        if (is_callable($callback)) {
            $this->_mode = ICallback::DIRECT;
            $this->_callback = $callback;
            return;
        }

        if (is_array($callback) && count($callback) == 2) {
            $class = array_shift($callback);
            $method = array_shift($callback);

            if (method_exists($class, $method)) {
                try {
                    $reflection = new \ReflectionMethod($class, $method);
                } catch (\Throwable $e) {
                    throw Exceptional::InvalidArgument(
                        'Callback is not callable'
                    );
                }

                $reflection->setAccessible(true);

                if ($reflection->isStatic()) {
                    $this->_reflectionInstance = null;
                } else {
                    if (!is_object($class)) {
                        throw Exceptional::InvalidArgument(
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

        throw Exceptional::InvalidArgument(
            'Callback is not callable'
        );
    }

    public function setExtraArgs(array $args)
    {
        $this->_extraArgs = $args;
        return $this;
    }

    public function getExtraArgs()
    {
        return $this->_extraArgs;
    }

    public function __invoke(...$args)
    {
        return $this->invoke(...$args);
    }

    public function invoke(...$args)
    {
        if (!empty($this->_extraArgs)) {
            $args = array_merge($args, $this->_extraArgs);
        }

        switch ($this->_mode) {
            case ICallback::DIRECT:
                return call_user_func_array($this->_callback, $args);

            case ICallback::REFLECTION:
                return $this->_callback->invokeArgs($this->_reflectionInstance, $args);
        }
    }

    public function getParameters()
    {
        if ($this->_mode === ICallback::REFLECTION) {
            $reflection = $this->_callback;
        } elseif (is_array($this->_callback)) {
            $reflection = new \ReflectionMethod($this->_callback[0], $this->_callback[1]);
        } elseif (is_object($this->_callback) && !$this->_callback instanceof \Closure) {
            $reflection = new \ReflectionMethod($this->_callback, '__invoke');
        } else {
            $reflection = new \ReflectionFunction($this->_callback);
        }

        return $reflection->getParameters();
    }

    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        if (is_array($this->_callback) && is_object($this->_callback[0])) {
            yield 'definition' => get_class($this->_callback[0]) . '->' . $this->_callback[1] . '()';
            return;
        }

        yield 'value' => $this->_callback;
    }
}

<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core;

use df;
use df\core;

class Error extends \Exception implements IError {

    private static $_instances = [];

    protected $_http = 500;
    protected $_data;
    protected $_rewind = 0;
    protected $_stackTrace;

    public static function __callStatic($method, array $args) {
        return static::_factory(
            $args[0] ?? 'Error',
            $args[1] ?? [],
            explode(',', $method)
        );
    }

    public static function factory($message, array $args=[], array $interfaces=[]) {
        return static::_factory($message, $args, $interfaces);
    }

    protected static function _factory($message, array $args=[], array $interfaces=[]) {
        if(is_array($message)) {
            $args = $message;
            $message = $message['message'] ?? 'Undefined error';
        }

        $args['rewind'] = $rewind = max((int)($args['rewind'] ?? 0), 0);
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $rewind + 3);

        $namespace = explode('\\', array_pop($trace)['class']);
        $className = array_pop($namespace);
        $namespace = implode('\\', $namespace);

        $base = $args['base'] ?? get_called_class();
        $def = self::_buildDefinition($base, $interfaces, $namespace);
        $hash = md5($def);

        if(!isset(self::$_instances[$hash])) {
            self::$_instances[$hash] = eval($def);
        }

        $trace = array_pop($trace);

        if(!isset($args['file'])) {
            $args['file'] = $trace['file'];
        }
        if(!isset($args['line'])) {
            $args['line'] = $trace['line'];
        }

        return new self::$_instances[$hash]($message, $args);
    }

    private static function _buildDefinition(string $base, array $interfaces, string $namespace) {
        $definition = 'return new class(\'\') extends '.$base;
        $traits = $interfaceDefs = [];
        $namespaces = [$namespace];

        if(!empty($interfaces)) {
            foreach($interfaces as $i => $interface) {
                if(false !== strpos($interface, '/')) {
                    $interface = 'df\\'.str_replace('/', '\\', ltrim($interface, '/'));
                }

                $interface = ltrim($interface, '\\');

                if(false === strpos($interface, '\\')) {
                    $isCore = interface_exists('\\df\\core\\'.$interface, true);
                    $interface = $namespace.'\\'.$interface;
                } else {
                    $parts = explode('\\', $interface);
                    $test = '\\df\\core\\'.array_pop($parts);
                    $isCore = interface_exists($test, true);
                }

                if(!interface_exists($interface, true)) {
                    $parts = explode('\\', $interface);
                    $name = array_pop($parts);

                    if($isCore) {
                        $base = '\\df\\core\\'.$name;
                    } else {
                        $base = '\\df\\core\\IError';
                    }

                    if(($parts[1] ?? 'core') !== 'core') {
                        $namespaces[] = implode('\\', $parts);
                    }

                    $interfaceDefs[] = 'namespace '.implode($parts, '\\').';interface '.$name.' extends '.$base.' {}';
                }

                $interfaces[$i] = $interface;
                $parts = explode('\\', $interface);
                $name = array_pop($parts);

                if(!preg_match('/^E[A-Z][a-zA-Z0-9_]+$/', $name)) {
                    unset($interfaces[$i]);
                    continue;
                }

                $traitName = implode('\\', $parts).'\\T'.substr($name, 1);

                if(trait_exists($traitName, true)) {
                    $traits[] = $traitName;
                } else if($isCore) {
                    $traitName = '\\df\\core\\T'.substr($name, 1);

                    if(trait_exists($traitName, true)) {
                        $traits[] = $traitName;
                    }
                }
            }
        }

        $interfaces = array_merge(
            $interfaces,
            static::_extractNamespaceInterfaces(...array_unique($namespaces))
        );

        foreach($interfaceDefs as $interfaceDef) {
            eval($interfaceDef);
        }

        if(!empty($interfaces)) {
            $definition .= ' implements '.implode(',', array_unique($interfaces));
        }

        $definition .= ' {';

        foreach($traits as $trait) {
            $definition .= 'use '.$trait.';';
        }

        $definition .= '};';

        return $definition;
    }

    private static function _extractNamespaceInterfaces(string ...$namespaces): array {
        $extra = [];

        foreach($namespaces as $namespace) {
            $parts = explode('\\', $namespace);
            $parts = array_slice($parts, 1, 3);
            $parent = 'df';

            foreach($parts as $part) {
                $first = $parent == 'df';
                $ins = $parent.'\\'.$part;
                $interface = $ins.'\\IError';

                if(!interface_exists($interface, true)) {
                    $base = $first ? '\df\core\IError' : '\\'.$parent.'\\IError';
                    $interfaceDef = 'namespace '.$ins.';interface IError extends '.$base.' {}';
                    eval($interfaceDef);
                }

                $parent = $ins;
                $extra[] = $interface;
            }
        }

        return $extra;
    }

    public function __construct($message, array $args=[]) {
        parent::__construct(
            $message,
            $args['code'] ?? 0,
            $args['previous'] ?? null
        );

        if(isset($args['file'])) {
            $this->file = $args['file'];
        }

        if(isset($args['line'])) {
            $this->line = $args['line'];
        }

        unset($args['code'], $args['previous'], $args['file'], $args['line']);

        $this->_data = $args['data'] ?? (
            isset($args['dataType']) ?
                core\lang\TypeRef::factory($args['dataType']) : null
        );

        $this->_rewind = $args['rewind'] ?? 0;
        $this->_http = (int)($args['http'] ?? 500);
    }

    public function setData($data) {
        $this->_data = $data;
        return $this;
    }

    public function getData() {
        return $this->_data;
    }

    public function setHttpCode(/*?int*/ $code) {
        $this->_http = $code;
        return $this;
    }

    public function getHttpCode() {//: ?int {
        return $this->_http;
    }

    public function getStackCall(): core\debug\IStackCall {
        return $this->getStackTrace()->getFirstCall();
    }

    public function getStackTrace(): core\debug\IStackTrace {
        if(!$this->_stackTrace) {
            $this->_stackTrace = core\debug\StackTrace::factory($this->_rewind + 1, $this->getTrace());
        }

        return $this->_stackTrace;
    }
}
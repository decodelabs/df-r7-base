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

    public static function __callStatic($method, array $args) {
        return static::_factory(
            $args[0] ?? 'Error',
            $args[1] ?? [],
            explode(',', $method)
        );
    }

    public static function factory(string $message, array $args=[], array $interfaces=[]) {
        return static::_factory($message, $args, $interfaces);
    }

    protected static function _factory(string $message, array $args=[], array $interfaces=[]) {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $namespace = explode('\\', array_pop($trace)['class']);
        $className = array_pop($namespace);
        $namespace = implode('\\', $namespace);

        $base = $args['base'] ?? get_called_class();
        $def = self::_buildDefinition($base, $interfaces, $namespace);
        $hash = md5($def);

        if(!isset(self::$_instances[$hash])) {
            self::$_instances[$hash] = eval($def);
        }

        return new self::$_instances[$hash]($message, $args);
    }

    private static function _buildDefinition(string $base, array $interfaces, string $namespace) {
        $definition = 'return new class(\'\') extends '.$base;
        $traits = [];

        if(!empty($interfaces)) {
            foreach($interfaces as $i => $interface) {
                if(false === strpos($interface, '\\')) {
                    $isCore = interface_exists('\\df\\core\\'.$interface, true);

                    if(interface_exists($namespace.'\\'.$interface, true)) {
                        $interface = $namespace.'\\'.$interface;
                    } else {
                        $interfaceDef = 'namespace '.$namespace.';interface '.$interface;

                        if($isCore) {
                            $interfaceDef .= ' extends \\df\\core\\'.$interface;
                        } else {
                            $interfaceDef .= ' extends \\df\\core\\IError';
                        }

                        $interfaceDef .= ' {}';
                        eval($interfaceDef);
                        $interface = $namespace.'\\'.$interface;
                    }

                    $interfaces[$i] = $interface;
                } else {
                    $parts = explode('\\', $interface);
                    $test = '\\df\\core\\'.array_pop($interface);
                    $isCore = interface_exists($test, true);
                }

                if(!interface_exists($interface, true)) {
                    unset($interfaces[$i]);
                    continue;
                }

                $parts = explode('\\', $interface);
                $name = array_pop($parts);

                if(!preg_match('/^E[A-Z][a-zA-Z0-9_]+$/', $name)) {
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

            if(!empty($interfaces)) {
                $definition .= ' implements '.implode(',', $interfaces);
            }
        }

        $definition .= ' {';

        foreach($traits as $trait) {
            $definition .= 'use '.$trait.';';
        }

        $definition .= '};';

        return $definition;
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
    }
}
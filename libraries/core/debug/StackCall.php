<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug;

use df;
use df\core;

use DecodeLabs\Glitch;
use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class StackCall implements IStackCall, Inspectable
{
    protected $_function;
    protected $_className;
    protected $_namespace;
    protected $_type = 4;
    protected $_args = [];

    protected $_callingFile;
    protected $_callingLine;
    protected $_originFile;
    protected $_originLine;
    protected $_local = true;

    public static function factory($rewind=0)
    {
        $data = debug_backtrace();

        while ($rewind > 0) {
            $rewind--;
            array_shift($data);
        }

        $last = array_shift($data);
        $output = array_shift($data);
        $output['fromFile'] = @$output['file'];
        $output['fromLine'] = @$output['line'];
        $output['file'] = @$last['file'];
        $output['line'] = @$last['line'];

        return new self($output, true);
    }

    public function __construct(array $callData, $local=true)
    {
        $this->_local = $local;

        if (isset($callData['fromFile']) && $callData['fromFile'] !== @$callData['file']) {
            $this->_callingFile = $callData['fromFile'];
        }

        if (isset($callData['fromLine'])) {
            $this->_callingLine = $callData['fromLine'];
        }

        $this->_originFile = @$callData['file'];
        $this->_originLine = @$callData['line'];

        if (isset($callData['function'])) {
            $this->_function = $callData['function'];
        }

        if (isset($callData['class'])) {
            $this->_className = $callData['class'];
            $parts = explode('\\', $this->_className);
            $this->_className = array_pop($parts);
        } else {
            $parts = explode('\\', $this->_function);
            $this->_function = array_pop($parts);
        }

        $this->_namespace = implode('\\', $parts);

        if (isset($callData['type'])) {
            switch ($callData['type']) {
                case '::':
                    $this->_type = IStackCall::STATIC_METHOD;
                    break;

                case '->':
                    $this->_type = IStackCall::OBJECT_METHOD;
                    break;

                default:
                    throw core\Error::EValue('Unknown call type: '.$callData['type']);
            }
        } elseif ($this->_namespace !== null) {
            $this->_type = 3;
        }

        if (isset($callData['args'])) {
            $this->_args = (array)$callData['args'];
        }
    }


    // Args
    public function getArgs(): array
    {
        return $this->_args;
    }

    public function hasArgs(): bool
    {
        return !empty($this->_args);
    }

    public function countArgs(): int
    {
        return count($this->_args);
    }

    public function getArgString(): string
    {
        $output = [];

        if (!is_array($this->_args)) {
            $this->_args = [$this->_args];
        }

        foreach ($this->_args as $arg) {
            if (is_string($arg)) {
                if (strlen($arg) > 16) {
                    $arg = substr($arg, 0, 16).'...';
                }

                $arg = '\''.$arg.'\'';
            } elseif (is_array($arg)) {
                $arg = 'Array('.count($arg).')';
            } elseif (is_object($arg)) {
                $arg = core\lang\Util::normalizeClassName(get_class($arg)).' Object';
            } elseif (is_bool($arg)) {
                if ($arg) {
                    $arg = 'true';
                } else {
                    $arg = 'false';
                }
            } elseif (is_null($arg)) {
                $arg = 'null';
            }

            $output[] = $arg;
        }

        return '('.implode(', ', $output).')';
    }


    // Type
    public function getType(): ?string
    {
        return $this->_type;
    }

    public function getTypeString(): ?string
    {
        switch ($this->_type) {
            case IStackCall::STATIC_METHOD:
                return '::';

            case IStackCall::OBJECT_METHOD:
                return '->';
        }

        return null;
    }

    public function isStatic(): bool
    {
        return $this->_type === IStackCall::STATIC_METHOD;
    }

    public function isObject(): bool
    {
        return $this->_type === IStackCall::OBJECT_METHOD;
    }

    public function isNamespaceFunction(): bool
    {
        return $this->_type === IStackCall::NAMESPACE_FUNCTION;
    }

    public function isGlobalFunction(): bool
    {
        return $this->_type === IStackCall::GLOBAL_FUNCTION;
    }


    // Namespace
    public function getNamespace(): ?string
    {
        return $this->_namespace;
    }

    public function hasNamespace(): bool
    {
        return $this->_namespace !== null;
    }


    // Class
    public function getClass(): ?string
    {
        if ($this->_className === null) {
            return null;
        }

        $output = '';

        if ($this->_namespace !== null) {
            $output = $this->_namespace.'\\';
        }

        $output .= $this->_className;
        return $output;
    }

    public function hasClass(): bool
    {
        return $this->_className !== null;
    }

    public function getClassName(): ?string
    {
        return $this->_className;
    }


    // Function
    public function getFunctionName(): ?string
    {
        return $this->_function;
    }

    public function getSignature(?bool $argString=false): string
    {
        $output = '';

        if ($this->_namespace !== null) {
            $output = $this->_namespace.'\\';
        }

        if ($this->_className !== null) {
            $output .= core\lang\Util::normalizeClassName($this->_className);
        }

        if ($this->_type) {
            $output .= $this->getTypeString();
        }

        $output .= $this->_function;

        if ($argString) {
            $output .= $this->getArgString();
        } elseif ($argString !== null) {
            $output .= '(';

            if (!empty($this->_args)) {
                $output .= count($this->_args);
            }

            $output .= ')';
        }

        return $output;
    }

    public function toArray(): array
    {
        return [
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'function' => $this->_function,
            'class' => $this->_className,
            'type' => $this->getTypeString(),
            'args' => $this->_args
        ];
    }

    public function toJsonArray(): array
    {
        return [
            'file' => $this->_local ?
                Glitch::normalizePath($this->getFile()) :
                $this->getFile(),
            'line' => $this->getLine(),
            'signature' => $this->getSignature()
            /*
            'function' => $this->_function,
            'class' => $this->_className,
            'type' => $this->getTypeString(),
            'args' => $this->getArgString()
            */
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toJsonArray());
    }



    // Location
    public function getFile(): ?string
    {
        return $this->_originFile;
    }

    public function getLine(): ?int
    {
        return $this->_originLine;
    }

    public function getCallingFile(): ?string
    {
        if ($this->_callingFile !== null) {
            return $this->_callingFile;
        }

        return $this->_originFile;
    }

    public function getCallingLine(): ?int
    {
        if ($this->_callingLine !== null) {
            return $this->_callingLine;
        }

        return $this->_originLine;
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setDefinition($this->getSignature(true))
            ->setProperty('*file', $this->getFile())
            ->setProperty('*line', $this->getLine());
    }
}

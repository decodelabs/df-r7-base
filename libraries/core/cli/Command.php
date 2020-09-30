<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\cli;

use df;
use df\core;

use DecodeLabs\Glitch;
use DecodeLabs\Exceptional;

class Command implements ICommand
{
    use core\TStringProvider;
    use core\collection\TArrayCollection;
    use core\collection\TArrayCollection_Constructor;
    use core\collection\TArrayCollection_ProcessedIndexedValueMap;
    use core\collection\TArrayCollection_ProcessedShiftable;
    use core\collection\TArrayCollection_IndexedMovable;

    protected $_executable;

    public static function fromArgv(): ICommand
    {
        if (!isset($_SERVER['argv'])) {
            throw Exceptional::Runtime(
                'No argv information is available'
            );
        }

        // TODO: build a proper PHP binary finder
        if (isset($_SERVER['_'])) {
            $executable = $_SERVER['_'];
        } else {
            $executable = 'php';
        }

        $output = new self($executable);

        foreach ($_SERVER['argv'] as $arg) {
            $output->addArgument(new Argument($arg));
        }

        return $output;
    }

    public static function factory($command): ICommand
    {
        if ($command instanceof ICommand) {
            return $command;
        }

        return self::fromString((string)$command);
    }

    public static function fromString(string $string): ICommand
    {
        // TODO: parse properly to account for quoted strings
        $parts = explode(' ', $string);
        $output = new self(array_shift($parts));

        foreach ($parts as $part) {
            $output->addArgument(new Argument($part));
        }

        return $output;
    }

    public function __construct(string $executable=null)
    {
        $this->setExecutable($executable);
    }

    public function import(...$input)
    {
        Glitch::incomplete($input);
    }



    // Executable
    public function setExecutable(?string $executable)
    {
        $this->_executable = $executable;
        return $this;
    }

    public function getExecutable(): ?string
    {
        return $this->_executable;
    }


    // Arguments
    public function addArgument($argument)
    {
        return $this->push($argument);
    }

    public function getArguments(): array
    {
        return $this->toArray();
    }

    public function insert(...$arguments)
    {
        return $this->push(...$arguments);
    }

    protected function _onInsert()
    {
    }

    protected function _expandInput($input): array
    {
        if ($input instanceof core\collection\ICollection) {
            $input = $input->toArray();
        }

        if (!is_array($input)) {
            if (is_string($input)) {
                $input = explode(' ', $input);
            } else {
                $input = [$input];
            }
        }

        foreach ($input as $i => $value) {
            if (!$value instanceof IArgument) {
                $input[$i] = new Argument($value);
            }
        }

        return $input;
    }


    // String
    public function toString(): string
    {
        $output = $this->_executable;

        foreach ($this->_collection as $argument) {
            $output .= ' '.$argument;
        }

        return $output;
    }
}

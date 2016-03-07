<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\cli;

use df;
use df\core;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}


// Interfaces
interface ICommand extends core\collection\IQueue, core\collection\IRandomAccessCollection, core\IStringProvider {
    public function setExecutable($executable);
    public function getExecutable();

    public function addArgument($argument);
    public function getArguments();
}


interface IArgument extends core\IStringProvider {
    public function getOption();
    public function isOption();
    public function isLongOption();
    public function isShortOption();
    public function isOptionCluster();
    public function getClusterOptions();

    public function getValue();
    public function hasValue();
}

interface IInspector extends \ArrayAccess {
    public function inspect($command);
    public function reset();
    public function getCommand();
    public function getValueArguments();
    public function getOptionArguments();
}

interface IRule {
    public function setNames($names);
    public function getName();
    public function getNames();
    public function getFlags();
    public function requiresValue(bool $flag=null);
    public function canHaveValue(bool $flag=null);
    public function isRequired(bool $flag=null);
    public function setDefaultValue($value);
    public function getDefaultValue();
    public function setValueType($type);
    public function getValueType();
    public function setDescription($description);
    public function getDescription();
}

class ValueType extends core\lang\Enum {
    const INTEGER = 'i';
    const WORD = 'w';
    const STRING = 's';
}
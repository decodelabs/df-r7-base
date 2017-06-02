<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\cli;

use df;
use df\core;

interface ICommand extends core\collection\IQueue, core\collection\IRandomAccessCollection, core\IStringProvider {
    public static function fromArgv(): ICommand;

    public function setExecutable(?string $executable);
    public function getExecutable(): ?string;

    public function addArgument($argument);
    public function getArguments(): array;
}


interface IArgument extends core\IStringProvider {
    public function setOption(?string $option);
    public function getOption(): ?string;
    public function getOptions(): array;
    public function isOption(): bool;
    public function isLongOption(): bool;
    public function isShortOption(): bool;
    public function isOptionCluster(): bool;
    public function getClusterOptions(): array;

    public function setValue(?string $value);
    public function getValue(): ?string;
    public function hasValue(): bool;
}

interface IInspector extends \ArrayAccess {
    public function inspect($command);
    public function reset();
    public function getCommand(): ?ICommand;
    public function getValueArguments(): array;
    public function getOptionArguments(): array;
}

interface IRule extends core\constraint\IRequirable {
    public function setNames($names);
    public function getName(): string;
    public function getNames(): array;
    public function getFlags(): array;
    public function requiresValue(bool $flag=null);
    public function canHaveValue(bool $flag=null);
    public function isRequired(bool $flag=null);
    public function setDefaultValue(?string $value);
    public function getDefaultValue(): ?string;
    public function setValueType($type);
    public function getValueType(): ValueType;
    public function setDescription(?string $description);
    public function getDescription(): ?string;
}

class ValueType extends core\lang\Enum {
    const INTEGER = 'i';
    const WORD = 'w';
    const STRING = 's';
}

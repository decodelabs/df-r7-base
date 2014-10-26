<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\lang;

use df;
use df\core;


// Exceptions
interface IException {}
class LogicException extends \LogicException implements IException {}
class UnexpectedValueException extends \UnexpectedValueException implements IException {}
class RuntimeException extends \RuntimeException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}
class BadMethodCallException extends \BadMethodCallException {}


// Interfaces
interface ICallback {
    
    const DIRECT = 1;
    const REFLECTION = 2;

    public function setExtraArgs(array $args);
    public function getExtraArgs();

    public function invoke();
    public function invokeArgs(array $args);
}

interface IEnum extends core\IStringProvider {
    public static function getOptions();
    public static function getLabels();
    public function getIndex();
    public function getOption();
    public function getLabel();
    public static function label($option);
    public function is($value);
}

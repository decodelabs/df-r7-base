<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\crypt;

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
interface IUtil {
    public static function passwordHash($password, $salt, $iterations=1000);
    public static function encrypt($message, $password, $salt);
    public static function decrypt($message, $password, $salt);
}

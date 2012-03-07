<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\user;

use df;
use df\core;
use df\user;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}
class AuthenticationException extends RuntimeException {}


// Interfaces
interface IAccessLock {
    public function getAccessLockDomain();
    public function lookupAccessKey(array $keys);
    public function getDefaultAccess();
}
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



interface IAddress {
    public function getPostOfficeBox();
    public function getStreetAddress();
    public function getExtendedAddress();
    public function getFullStreetAddress();
    public function getLocality();
    public function getRegion();
    public function getPostalCode();
    public function getCountryCode();
}
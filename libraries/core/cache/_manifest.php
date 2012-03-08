<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\cache;

use df;
use df\core;

// Exceptions
interface IException {}
class LogicException extends \LogicException implements IException {}
class RuntimeException extends \RuntimeException implements IException {}


// Interfaces
interface ICache extends core\IValueMap, \ArrayAccess, core\IApplicationAware, core\IRegistryObject {
    public static function getCacheId();
    public function getLifeTime();
    public function getDefaultLifeTime();
    public function isCacheDistributed();
    public function clear();
    public function getCreationTime($key);
}

interface IBackend extends core\IValueMap {
    public static function isLoadable();
    public function getLifeTime();
    public function clear();
    public function getCreationTime($key);
}

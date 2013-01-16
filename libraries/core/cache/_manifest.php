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
interface ICache extends core\IValueMap, \ArrayAccess, core\IApplicationAware, core\IRegistryObject, \Countable {
    public static function getCacheId();
    public function getLifeTime();
    public function getDefaultLifeTime();
    public function isCacheDistributed();
    public function clear();
    public function getCreationTime($key);
    public function getKeys();

    public function hasDirectFileBackend();
    public function getDirectFilePath($key);
    public function getDirectFileSize($key);
    public function getDirectFile($key);
    public function getDirectFileList();
}



interface IBackend extends core\IValueMap, \Countable {
    public static function isLoadable();
    public function setLifeTime($lifeTime);
    public function getLifeTime();
    public function clear();
    public function getCreationTime($key);
    public function getKeys();
}

interface IDirectFileBackend extends IBackend {
    public function shouldSerialize($flag=null);
    public function getDirectFilePath($id);
    public function getDirectFileSize($id);
    public function getDirectFile($id);
    public function getDirectFileList();
}

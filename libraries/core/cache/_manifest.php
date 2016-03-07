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
interface ICache extends core\IValueMap, \ArrayAccess, core\IRegistryObject, \Countable {
    public static function getCacheId();
    public function getCacheBackend();
    public function getCacheStats();
    public function getLifeTime();
    public function getDefaultLifeTime();
    public function isCacheDistributed();
    public function mustCacheBeLocal();
    public function clear();
    public function clearAll();
    public function clearBegins($key);
    public function clearMatches($regex);
    public function getCreationTime($key);
    public function getKeys();

    public function hasDirectFileBackend();
    public function getDirectFilePath($key);
    public function getDirectFileSize($key);
    public function getDirectFile($key);
    public function getDirectFileList();
}


interface ISessionExtendedCache extends ICache {
    public function setSession($key, $value);
    public function getSession($key, $default=null);
    public function hasSession($key);
    public function removeSession($key);
    public function clearSession();
}



interface IBackend extends core\IValueMap, \Countable {
    public static function purgeApp(core\collection\ITree $options);
    public static function purgeAll(core\collection\ITree $options);
    public static function prune(core\collection\ITree $options);
    public static function clearFor(core\collection\ITree $options, ICache $cache);
    public static function isLoadable();
    public function getConnectionDescription();
    public function getStats();
    public function setLifeTime($lifeTime);
    public function getLifeTime();
    public function clear();
    public function clearBegins($key);
    public function clearMatches($regex);
    public function getCreationTime($key);
    public function getKeys();
}

interface IDirectFileBackend extends IBackend {
    public function shouldSerialize(bool $flag=null);
    public function getDirectFilePath($id);
    public function getDirectFileSize($id);
    public function getDirectFile($id);
    public function getDirectFileList();
}
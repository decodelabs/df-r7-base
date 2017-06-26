<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\cache;

use df;
use df\core;

interface IStore extends core\IValueMap, \ArrayAccess, core\IRegistryObject, \Countable {
    public static function getInstance(): IStore;
    public static function getCacheId(): string;

    public function isCacheDistributed(): bool;
    public function getCacheStats(): array;

    public function clear();
    public function clearBegins(string $key);
    public function clearMatches(string $regex);
    public function getCreationTime(string $key): ?int;
    public function getCreationDate(string $key): ?core\time\IDate;
    public function getKeys(): array;
}

interface ICache extends IStore {
    public function getCacheBackend(): IBackend;
    public function getLifeTime(): int;
    public function getDefaultLifeTime(): int;
    public function mustCacheBeLocal(): bool;
    public function clearAll();

    public function hasDirectFileBackend(): bool;
    public function getDirectFilePath(string $key): ?string;
    public function getDirectFileSize(string $key): ?int;
    public function getDirectFile(string $key): ?core\fs\IFile;
    public function getDirectFileList(): array;
}

interface IFileStore extends IStore {
    public function clearOlderThan($lifeTime);
}


interface ISessionExtendedCache extends ICache {
    public function clearGlobal();
    public function setSession(string $key, $value);
    public function getSession(string $key, $default=null);
    public function hasSession(string $key): bool;
    public function removeSession(string $key);
    public function clearSession();
    public function clearSessionForUser($userId);
    public function clearSessionForClient();
    public function clearSessionForAll();
}



interface IBackend extends core\IValueMap, \Countable {
    public static function purgeApp(core\collection\ITree $options);
    public static function purgeAll(core\collection\ITree $options);
    public static function prune(core\collection\ITree $options);
    public static function clearFor(core\collection\ITree $options, ICache $cache);
    public static function isLoadable(): bool;
    public function getConnectionDescription(): string;
    public function getStats(): array;
    public function setLifeTime(int $lifeTime);
    public function getLifeTime(): int;
    public function clear();
    public function clearBegins(string $key);
    public function clearMatches(string $regex);
    public function getCreationTime(string $key): ?int;
    public function getKeys(): array;
}

interface IDirectFileBackend extends IBackend {
    public function shouldSerialize(bool $flag=null);
    public function getDirectFilePath(string $key): ?string;
    public function getDirectFileSize(string $key): ?int;
    public function getDirectFile(string $key): ?core\fs\IFile;
    public function getDirectFileList(): array;
}

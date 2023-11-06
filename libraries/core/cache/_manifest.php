<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\cache;

use DecodeLabs\Dovetail\Repository;
use DecodeLabs\Terminus\Session;

use df\core;

interface IStore extends core\IValueMap, \ArrayAccess, core\IRegistryObject, \Countable
{
    public static function getInstance();
    public static function getCacheId(): string;
    public static function createCacheId(): string;

    public function isCacheDistributed(): bool;
    public function getCacheStats(): array;

    public function clear();
    public function clearBegins(string $key);
    public function clearMatches(string $regex);
    public function getCreationTime(string $key): ?int;
    public function getCreationDate(string $key): ?core\time\IDate;
    public function getKeys(): array;
}

interface ICache extends IStore
{
    public function getCacheBackend(): IBackend;
    public function getLifeTime(): int;
    public function getDefaultLifeTime(): int;
    public function clearAll();
}

interface IFileStore extends IStore
{
    public function clearOlderThan($lifeTime);
    public function getFileList(): array;
}

interface IBackend extends \Countable
{
    public static function purgeAll(Repository $options, ?Session $session = null);
    public static function prune(Repository $options);
    public static function clearFor(Repository $options, ICache $cache);
    public static function isLoadable(): bool;
    public function getConnectionDescription(): string;
    public function getStats(): array;
    public function setLifeTime(int $lifeTime);
    public function getLifeTime(): int;
    public function set($key, $value, $lifeTime = null);
    public function get($key, $default = null);
    public function has(...$keys);
    public function remove(...$keys);
    public function clear();
    public function clearBegins(string $key);
    public function clearMatches(string $regex);
    public function getCreationTime(string $key): ?int;
    public function getKeys(): array;
}

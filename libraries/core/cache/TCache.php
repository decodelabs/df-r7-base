<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\cache;

use DecodeLabs\R7\Legacy;

use df\core;

trait TCache
{
    use core\TValueMap;

    //const REGISTRY_PREFIX = 'cache://';
    //const IS_DISTRIBUTED = true;

    private static $_cacheIds = [];

    public static function getInstance()
    {
        $id = self::REGISTRY_PREFIX . static::getCacheId();

        if (!$cache = Legacy::getRegistryObject($id)) {
            Legacy::setRegistryObject(
                $cache = new static()
            );
        }

        return $cache;
    }

    final public function getRegistryObjectKey(): string
    {
        return self::REGISTRY_PREFIX . static::getCacheId();
    }

    public static function getCacheId(): string
    {
        $class = get_called_class();

        if (!isset(self::$_cacheIds[$class])) {
            self::$_cacheIds[$class] = $class::createCacheId();
        }

        return self::$_cacheIds[$class];
    }

    public static function createCacheId(): string
    {
        $parts = explode('\\', get_called_class());
        array_shift($parts);
        return implode('/', $parts);
    }

    public function isCacheDistributed(): bool
    {
        return static::IS_DISTRIBUTED;
    }


    public function getCreationDate(string $key): ?core\time\IDate
    {
        if (null === ($time = $this->getCreationTime($key))) {
            return null;
        }

        return new core\time\Date($time);
    }


    public function offsetSet(
        mixed $key,
        mixed $value
    ): void {
        $this->set($key, $value);
    }

    public function offsetGet(mixed $key): mixed
    {
        return $this->get($key);
    }

    public function offsetExists(mixed $key): bool
    {
        return $this->has($key);
    }

    public function offsetUnset(mixed $key): void
    {
        $this->remove($key);
    }
}

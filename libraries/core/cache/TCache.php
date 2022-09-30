<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\cache;

use df;
use df\core;

use DecodeLabs\Exceptional;

trait TCache
{
    use core\TValueMap;

    //const REGISTRY_PREFIX = 'cache://';
    //const IS_DISTRIBUTED = true;

    private static $_cacheIds = [];

    public static function getInstance()
    {
        $id = self::REGISTRY_PREFIX.static::getCacheId();

        if (!$cache = df\Launchpad::$app->getRegistryObject($id)) {
            df\Launchpad::$app->setRegistryObject(
                $cache = new static()
            );
        }

        return $cache;
    }

    final public function getRegistryObjectKey(): string
    {
        return self::REGISTRY_PREFIX.static::getCacheId();
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


    public function offsetSet($key, $value): void
    {
        $this->set($key, $value);
    }

    public function offsetGet($key)
    {
        return $this->get($key);
    }

    public function offsetExists($key): bool
    {
        return $this->has($key);
    }

    public function offsetUnset($key): void
    {
        $this->remove($key);
    }
}

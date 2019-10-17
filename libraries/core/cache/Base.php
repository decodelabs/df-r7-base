<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\cache;

use df;
use df\core;

abstract class Base implements ICache
{
    use TCache;

    const REGISTRY_PREFIX = 'cache://';

    const IS_DISTRIBUTED = true;
    const DEFAULT_LIFETIME = 1800;

    private $_backend;

    public static function purgeApp(core\io\IMultiplexer $io=null): void
    {
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }

        $config = Config::getInstance();

        foreach (df\Launchpad::$loader->lookupClassList('core/cache/backend') as $name => $class) {
            $options = $config->getBackendOptions($name);
            $class::purgeApp($options, $io);
        }
    }

    public static function purgeAll(core\io\IMultiplexer $io=null): void
    {
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }

        $config = Config::getInstance();

        foreach (df\Launchpad::$loader->lookupClassList('core/cache/backend') as $name => $class) {
            $options = $config->getBackendOptions($name);
            $class::purgeAll($options, $io);
        }
    }


    // Construct
    protected function __construct()
    {
        $this->_backend = $this->_loadBackend();
    }

    protected function _loadBackend(): IBackend
    {
        $config = Config::getInstance();

        $options = $config->getOptionsFor($this);
        $backendName = null;

        if ($options->has('backend')) {
            $backendName = $options->get('backend');
        }

        if (!$backendName) {
            throw core\Error::{'ESetup'}(
                'There are no available backends for cache '.$this->getCacheId()
            );
        }

        $output = self::backendFactory($this, $backendName, $options);

        return $output;
    }

    public static function backendFactory(ICache $cache, $name, core\collection\ITree $options, $lifeTime=0): IBackend
    {
        $class = 'df\\core\\cache\\backend\\'.$name;

        if (isset($options->lifeTime)) {
            $lifeTime = (int)$options['lifeTime'];
        }

        if ($lifeTime < 1) {
            $lifeTime = $cache->getDefaultLifeTime();
        }

        return new $class($cache, $lifeTime, $options);
    }



    // Properties
    public function getCacheBackend(): IBackend
    {
        return $this->_backend;
    }

    public function getCacheStats(): array
    {
        return $this->_backend->getStats();
    }

    public function getLifeTime(): int
    {
        return $this->_backend->getLifeTime();
    }

    public function getDefaultLifeTime(): int
    {
        return static::DEFAULT_LIFETIME;
    }


    // Access
    public function set($key, $value, $lifeTime=null)
    {
        if ($lifeTime !== null) {
            $lifeTime = (int)$lifeTime;

            if ($lifeTime <= 0) {
                $lifeTime = null;
            }
        }

        $this->_backend->set($key, $value, $lifeTime);
        return $this;
    }

    public function get($key, $default=null)
    {
        return $this->_backend->get($key, $default);
    }

    public function has(...$keys)
    {
        return $this->_backend->has(...$keys);
    }

    public function remove(...$keys)
    {
        $this->_backend->remove(...$keys);
        return $this;
    }

    public function clear()
    {
        $this->_backend->clear();
        return $this;
    }

    public function clearAll()
    {
        $config = Config::getInstance();

        foreach (df\Launchpad::$loader->lookupClassList('core/cache/backend') as $name => $class) {
            $options = $config->getBackendOptions($name);
            $class::clearFor($options, $this);
        }

        return $this;
    }

    public function clearBegins(string $key)
    {
        $this->_backend->clearBegins($key);
        return $this;
    }

    public function clearMatches(string $regex)
    {
        $this->_backend->clearMatches($regex);
        return $this;
    }

    public function count()
    {
        return $this->_backend->count();
    }

    public function getKeys(): array
    {
        return $this->_backend->getKeys();
    }


    public function getCreationTime(string $key): ?int
    {
        return $this->_backend->getCreationTime($key);
    }
}

<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\cache\backend;

use DecodeLabs\Dovetail\Repository;
use DecodeLabs\R7\Legacy;
use DecodeLabs\Terminus\Session;
use df\core;

class Memcache implements core\cache\IBackend
{
    use core\TValueMap;

    protected $_connection;
    protected $_prefix;
    protected $_lifeTime;
    protected $_cache;

    public static function purgeAll(
        Repository $options,
        ?Session $session = null
    ) {
        if (!self::isLoadable()) {
            return;
        }

        $connection = self::_loadConnection($options);
        $connection->flush();
    }

    public static function prune(Repository $options)
    {
        // pruning is automatic :)
    }

    public static function isLoadable(): bool
    {
        return extension_loaded('memcache');
    }

    protected static function _loadConnection(Repository $options)
    {
        $output = new \Memcache();

        if ($options->has('servers')) {
            $serverList = $options->servers;
        } else {
            $serverList = [$options];
        }

        foreach ($serverList as $serverOptions) {
            $output->addServer(
                $serverOptions->get('host') ?? '127.0.0.1',
                $serverOptions->get('port') ?? 11211,
                (bool)($serverOptions->get('persistent') ?? true)
            );
        }

        return $output;
    }

    public static function clearFor(
        Repository $options,
        core\cache\ICache $cache
    ) {
        if (self::isLoadable()) {
            (new self($cache, 0, $options))->clear();
        }
    }

    public function __construct(
        core\cache\ICache $cache,
        int $lifeTime,
        Repository $options
    ) {
        $this->_cache = $cache;
        $this->_lifeTime = $lifeTime;
        $this->_prefix = Legacy::getUniquePrefix() . '-' . $cache->getCacheId() . ':';

        $this->_connection = self::_loadConnection($options);
    }

    public function getConnectionDescription(): string
    {
        $stats = $this->_connection->getExtendedStats();
        return implode(' + ', array_keys($stats));
    }

    public function getStats(): array
    {
        return $this->_connection->getStats();
    }

    public function setLifeTime(int $lifeTime)
    {
        $this->_lifeTime = $lifeTime;
        return $this;
    }

    public function getLifeTime(): int
    {
        return $this->_lifeTime;
    }


    public function set($key, $value, $lifeTime = null)
    {
        if ($lifeTime === null) {
            $lifeTime = $this->_lifeTime;
        }

        return $this->_connection->set(
            $this->_prefix . $key,
            [serialize($value), time()],
            0,
            $lifeTime
        );
    }

    public function get($key, $default = null)
    {
        $val = $this->_connection->get($this->_prefix . $key);

        if (is_array($val)) {
            try {
                return unserialize($val[0]);
            } catch (\Throwable $e) {
                core\logException($e);
                return $default;
            }
        }

        return $default;
    }

    public function has(...$keys)
    {
        foreach ($keys as $key) {
            if (is_array($this->_connection->get($this->_prefix . $key))) {
                return true;
            }
        }

        return false;
    }

    public function remove(...$keys)
    {
        foreach ($keys as $key) {
            $this->_connection->delete($this->_prefix . $key);
        }

        return true;
    }

    public function clear()
    {
        foreach ($this->getKeys() as $key) {
            $this->remove($key);
        }

        return $this;
    }

    public function clearBegins(string $key)
    {
        foreach ($this->getKeys() as $test) {
            if (0 === strpos($test, $key)) {
                $this->remove($test);
            }
        }

        return $this;
    }

    public function clearMatches(string $regex)
    {
        foreach ($this->getKeys() as $test) {
            if (preg_match($regex, $test)) {
                $this->remove($test);
            }
        }

        return $this;
    }

    public function count(): int
    {
        $output = 0;
        $allSlabs = $this->_connection->getExtendedStats('slabs');

        foreach ($allSlabs as $server => $slabs) {
            foreach ($slabs as $slabId => $slabMeta) {
                $cdump = $this->_connection->getExtendedStats('cachedump', $slabId);

                foreach ($cdump as $keys => $arrVal) {
                    if (!is_array($arrVal)) {
                        continue;
                    }

                    foreach ($arrVal as $key => $value) {
                        if (0 === strpos($key, $this->_prefix)) {
                            $output++;
                        }
                    }
                }
            }
        }

        return $output;
    }

    public function getKeys(): array
    {
        $output = [];
        $allSlabs = $this->_connection->getExtendedStats('slabs');
        $length = strlen((string)$this->_prefix);

        foreach ($allSlabs as $server => $slabs) {
            foreach ($slabs as $slabId => $slabMeta) {
                if (is_string($slabId)) {
                    continue;
                }

                $cdump = $this->_connection->getExtendedStats('cachedump', $slabId);

                foreach ($cdump as $keys => $arrVal) {
                    if (!is_array($arrVal)) {
                        continue;
                    }

                    foreach ($arrVal as $key => $value) {
                        if (0 === strpos($key, $this->_prefix)) {
                            $output[] = substr($key, $length);
                        }
                    }
                }
            }
        }

        return $output;
    }

    public function getCreationTime(string $key): ?int
    {
        $val = $this->_connection->get($this->_prefix . $key);

        if (is_array($val)) {
            return $val[1];
        }

        return null;
    }
}

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

class Apcu implements core\cache\IBackend
{
    use core\TValueMap;

    protected $_prefix;
    protected $_lifeTime;
    protected $_cache;
    protected $_isCli = false;

    public static function purgeAll(
        Repository $options,
        ?Session $session = null
    ) {
        if (extension_loaded('apcu')) {
            apcu_clear_cache();
        }
    }

    public static function prune(Repository $options)
    {
        // pruning is automatic :)
    }

    public static function clearFor(
        Repository $options,
        core\cache\ICache $cache
    ) {
        if (!extension_loaded('apcu')) {
            return;
        }

        (new self($cache, 0, $options))->clear();
    }

    public static function isLoadable(): bool
    {
        if ($output = extension_loaded('apcu')) {
            if (php_sapi_name() == 'cli' && !ini_get('apc.enable_cli')) {
                $output = false;
            }
        }

        return $output;
    }

    public function __construct(
        core\cache\ICache $cache,
        int $lifeTime,
        Repository $options
    ) {
        $this->_cache = $cache;
        $this->_lifeTime = $lifeTime;
        $this->_prefix = Legacy::getUniquePrefix() . '-' . $cache->getCacheId() . ':';
        $this->_isCli = php_sapi_name() == 'cli';
        unset($options);
    }

    public function getConnectionDescription(): string
    {
        return 'localhost/' . $this->_cache->getCacheId();
    }

    public function getStats(): array
    {
        $info = apcu_cache_info();

        $info = [
            'totalEntries' => count($info['cache_list']),
            'entries' => $this->count(),
            'size' => $info['mem_size']
        ] + $info;

        unset($info['cache_list'], $info['deleted_list'], $info['slot_distribution'], $info['mem_size']);
        return $info;
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

        return apcu_store(
            $this->_prefix . $key,
            [serialize($value), time()],
            $lifeTime
        );
    }

    public function get($key, $default = null)
    {
        $val = apcu_fetch($this->_prefix . $key);

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
            if (is_array(apcu_fetch($this->_prefix . $key))) {
                return true;
            }
        }

        return false;
    }

    public function remove(...$keys)
    {
        foreach ($keys as $key) {
            @apcu_delete($this->_prefix . $key);
        }

        return true;
    }

    public function clear()
    {
        if (!($this->_isCli && !ini_get('apc.enable_cli'))) {
            foreach ($this->getCacheList() as $set) {
                if (0 === strpos($set['info'], $this->_prefix)) {
                    @apcu_delete($set['info']);
                }
            }
        }

        return $this;
    }

    public function clearBegins(string $key)
    {
        foreach ($this->getCacheList() as $set) {
            if (0 === strpos($set['info'], $this->_prefix . $key)) {
                @apcu_delete($set['info']);
            }
        }

        return $this;
    }

    public function clearMatches(string $regex)
    {
        $prefixLength = strlen((string)$this->_prefix);

        foreach ($this->getCacheList() as $set) {
            if (0 === strpos($set['info'], $this->_prefix)
            && preg_match($regex, substr((string)$set['info'], $prefixLength))) {
                @apcu_delete($set['info']);
            }
        }

        return $this;
    }

    public function count(): int
    {
        $output = 0;

        foreach ($this->getCacheList() as $set) {
            if (0 === strpos($set['info'], $this->_prefix)) {
                $output++;
            }
        }

        return $output;
    }

    public function getKeys(): array
    {
        $output = [];
        $length = strlen((string)$this->_prefix);

        foreach ($this->getCacheList() as $set) {
            if (0 === strpos($set['info'], $this->_prefix)) {
                $output[] = substr($set['info'], $length);
            }
        }

        return $output;
    }

    public function getCreationTime(string $key): ?int
    {
        $val = apcu_fetch($this->_prefix . $key);

        if (is_array($val)) {
            return $val[1];
        }

        return null;
    }

    public static function getCacheList()
    {
        $info = apcu_cache_info();
        $output = [];

        if (isset($info['cache_list'])) {
            $output = $info['cache_list'];

            if (isset($output[0]['key'])) {
                foreach ($output as $i => $set) {
                    $key = $set['key'];
                    unset($set['key']);

                    $output[$i] = array_merge([
                        'info' => $key
                    ], $set);
                }
            }
        }

        return $output;
    }
}

<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\cache\backend;

use DecodeLabs\R7\Legacy;
use DecodeLabs\Terminus\Session;

use df\arch;
use df\core;

class Apcu implements core\cache\IBackend
{
    use core\TValueMap;

    protected $_prefix;
    protected $_lifeTime;
    protected $_cache;
    protected $_isCli = false;

    public static function purgeApp(core\collection\ITree $options, ?Session $session = null)
    {
        if (extension_loaded('apcu') && !(php_sapi_name() == 'cli' && !ini_get('apc.enable_cli'))) {
            $prefix = Legacy::getUniquePrefix() . '-';
            $list = self::getCacheList();

            foreach ($list as $set) {
                if (0 === strpos($set['info'], $prefix)) {
                    @apcu_delete($set['info']);
                }
            }
        }

        $request = new arch\Request('cache/apcu-clear.json?purge=app');
        $request->query->mode = (php_sapi_name() == 'cli' ? 'http' : 'cli');
        $session ? Legacy::runTask($request) : Legacy::launchTask($request);
    }

    public static function purgeAll(core\collection\ITree $options, ?Session $session = null)
    {
        if (extension_loaded('apcu')) {
            apcu_clear_cache();
        }

        $request = new arch\Request('cache/apcu-clear.json?purge=all');
        $request->query->mode = (php_sapi_name() == 'cli' ? 'http' : 'cli');
        $session ? Legacy::runTask($request) : Legacy::launchTask($request);
    }

    public static function prune(core\collection\ITree $options)
    {
        // pruning is automatic :)
    }

    public static function clearFor(core\collection\ITree $options, core\cache\ICache $cache)
    {
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

    public function __construct(core\cache\ICache $cache, int $lifeTime, core\collection\ITree $options)
    {
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
            $output = @apcu_delete($this->_prefix . $key);

            /*
            if($this->_isCli) {
                $this->_retrigger('remove', $key);
            }
             */
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

        $this->_retrigger('clear');
        return $this;
    }

    public function clearBegins(string $key)
    {
        foreach ($this->getCacheList() as $set) {
            if (0 === strpos($set['info'], $this->_prefix . $key)) {
                @apcu_delete($set['info']);
            }
        }

        $this->_retrigger('clearBegins', $key);
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

        $this->_retrigger('clearMatches', $regex);
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

    protected function _retrigger($method, $arg = null)
    {
        $request = new arch\Request('cache/apcu-clear');
        $request->query->cacheId = $this->_cache->getCacheId();
        $request->query->mode = $this->_isCli ? 'http' : 'cli';
        $request->query->{$method} = $arg;

        try {
            Legacy::runTaskQuietly($request);
        } catch (\Throwable $e) {
            core\log\Manager::getInstance()->logException($e);
        }
    }
}

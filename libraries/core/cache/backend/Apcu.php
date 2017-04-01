<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\cache\backend;

use df;
use df\core;
use df\arch;

class Apcu implements core\cache\IBackend {

    use core\TValueMap;

    protected $_prefix;
    protected $_lifeTime;
    protected $_cache;
    protected $_isCli = false;

    public static function purgeApp(core\collection\ITree $options) {
        if(extension_loaded('apcu') && !(php_sapi_name() == 'cli' && !ini_get('apc.enable_cli'))) {
            $prefix = df\Launchpad::$application->getUniquePrefix().'-';
            $list = self::getCacheList();

            foreach($list as $set) {
                if(0 === strpos($set['info'], $prefix)) {
                    @apcu_delete($set['info']);
                }
            }
        }

        $request = new arch\Request('cache/apcu-clear.json?purge=app');
        $request->query->mode = (php_sapi_name() == 'cli' ? 'http' : 'cli');

        arch\node\task\Manager::getInstance()->launchBackground($request);
    }

    public static function purgeAll(core\collection\ITree $options) {
        if(extension_loaded('apcu')) {
            apcu_clear_cache();
        }

        $request = new arch\Request('cache/apcu-clear.json?purge=all');
        $request->query->mode = (php_sapi_name() == 'cli' ? 'http' : 'cli');

        arch\node\task\Manager::getInstance()->launchBackground($request);
    }

    public static function prune(core\collection\ITree $options) {
        // pruning is automatic :)
    }

    public static function clearFor(core\collection\ITree $options, core\cache\ICache $cache) {
        if(!extension_loaded('apcu')) {
            return;
        }

        (new self($cache, 0, $options))->clear();
    }

    public static function isLoadable() {
        if($output = extension_loaded('apcu')) {
            if(php_sapi_name() == 'cli' && !ini_get('apc.enable_cli')) {
                $output = false;
            }
        }

        return $output;
    }

    public function __construct(core\cache\ICache $cache, $lifeTime, core\collection\ITree $options) {
        $this->_cache = $cache;
        $this->_lifeTime = $lifeTime;
        $this->_prefix = df\Launchpad::$application->getUniquePrefix().'-'.$cache->getCacheId().':';
        $this->_isCli = php_sapi_name() == 'cli';
    }

    public function getConnectionDescription() {
        return 'localhost/'.$this->_cache->getCacheId();
    }

    public function getStats() {
        $info = apcu_cache_info();

        $info = [
            'totalEntries' => count($info['cache_list']),
            'entries' => $this->count(),
            'size' => $info['mem_size']
        ] + $info;

        unset($info['cache_list'], $info['deleted_list'], $info['slot_distribution'], $info['mem_size']);
        return $info;
    }

    public function setLifeTime($lifeTime) {
        $this->_lifeTime = $lifeTime;
        return $this;
    }

    public function getLifeTime() {
        return $this->_lifeTime;
    }


    public function set($key, $value, $lifeTime=null) {
        if($lifeTime === null) {
            $lifeTime = $this->_lifeTime;
        }

        return apcu_store(
            $this->_prefix.$key,
            [serialize($value), time()],
            $lifeTime
        );
    }

    public function get($key, $default=null) {
        $val = apcu_fetch($this->_prefix.$key);

        if(is_array($val)) {
            try {
                return unserialize($val[0]);
            } catch(\Throwable $e) {
                core\logException($e);
                return $default;
            }
        }

        return $default;
    }

    public function has(...$keys) {
        foreach($keys as $key) {
            if(is_array(apcu_fetch($this->_prefix.$key))) {
                return true;
            }
        }

        return false;
    }

    public function remove(...$keys) {
        foreach($keys as $key) {
            $output = @apcu_delete($this->_prefix.$key);

            /*
            if($this->_isCli) {
                $this->_retrigger('remove', $key);
            }
            */
        }

        return true;
    }

    public function clear() {
        if(!($this->_isCli && !ini_get('apc.enable_cli'))) {
            foreach($this->getCacheList() as $set) {
                if(0 === strpos($set['info'], $this->_prefix)) {
                    @apcu_delete($set['info']);
                }
            }
        }

        $this->_retrigger('clear');
        return $this;
    }

    public function clearBegins($key) {

        foreach($this->getCacheList() as $set) {
            if(0 === strpos($set['info'], $this->_prefix.$key)) {
                @apcu_delete($set['info']);
            }
        }

        $this->_retrigger('clearBegins', $key);
        return $this;
    }

    public function clearMatches($regex) {
        $prefixLength = strlen($this->_prefix);

        foreach($this->getCacheList() as $set) {
            if(0 === strpos($set['info'], $this->_prefix)
            && preg_match($regex, substr($set['info'], $prefixLength))) {
                @apcu_delete($set['info']);
            }
        }

        $this->_retrigger('clearMatches', $regex);
        return $this;
    }

    public function count() {
        $output = 0;

        foreach($this->getCacheList() as $set) {
            if(0 === strpos($set['info'], $this->_prefix)) {
                $output++;
            }
        }

        return $output;
    }

    public function getKeys() {
        $output = [];
        $length = strlen($this->_prefix);

        foreach($this->getCacheList() as $set) {
            if(0 === strpos($set['info'], $this->_prefix)) {
                $output[] = substr($set['info'], $length);
            }
        }

        return $output;
    }

    public function getCreationTime($key) {
        $val = apcu_fetch($this->_prefix.$key);

        if(is_array($val)) {
            return $val[1];
        }

        return null;
    }

    public static function getCacheList() {
        $info = apcu_cache_info();
        $output = [];

        if(isset($info['cache_list'])) {
            $output = $info['cache_list'];

            if(isset($output[0]['key'])) {
                foreach($output as $i => $set) {
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

    protected function _retrigger($method, $arg=null) {
        $request = new arch\Request('cache/apcu-clear');
        $request->query->cacheId = $this->_cache->getCacheId();
        $request->query->mode = $this->_isCli ? 'http' : 'cli';
        $request->query->{$method} = $arg;

        try {
            arch\node\task\Manager::getInstance()->launchQuietly($request);
        } catch(\Throwable $e) {
            core\log\Manager::getInstance()->logException($e);
        }
    }
}
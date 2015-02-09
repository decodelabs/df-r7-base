<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\cache\backend;

use df;
use df\core;
use df\arch;

class Apc implements core\cache\IBackend {
    
    use core\TValueMap;

    protected static $_apcu = null;
    protected static $_setKey = null;
    
    protected $_prefix;
    protected $_lifeTime;
    protected $_cache;
    protected $_isCli = false;

    public static function purgeApp(core\collection\ITree $options) {
        $prefix = df\Launchpad::$application->getUniquePrefix().'-';
        $list = self::_getCacheList();

        foreach($list as $set) {
            if(0 === strpos($set[self::$_setKey], $prefix)) {
                @apc_delete($set[self::$_setKey]);
            }
        }

        $request = new arch\Request('cache/apc-clear.json?purge=app');
        $request->query->mode = (php_sapi_name() == 'cli' ? 'http' : 'cli');

        arch\task\Manager::getInstance()->launchBackground($request);
    }

    public static function purgeAll(core\collection\ITree $options) {
        if(extension_loaded('apc')) {
            if(self::$_apcu) {
                apc_clear_cache();
            } else {
                apc_clear_cache('user');
                apc_clear_cache('system');
            }
        }

        $request = new arch\Request('cache/apc-clear.json?purge=all');
        $request->query->mode = (php_sapi_name() == 'cli' ? 'http' : 'cli');

        arch\task\Manager::getInstance()->launchBackground($request);
    }

    public static function prune(core\collection\ITree $options) {
        // pruning is automatic :)
    }

    public static function clearFor(core\collection\ITree $options, core\cache\ICache $cache) {
        if(!extension_loaded('apc')) {
            return;
        }

        (new self($cache, 0, $options))->clear();
    }

    public static function isLoadable() {
        if(self::$_apcu === null) {
            self::$_apcu = version_compare(PHP_VERSION, '5.5.0') >= 0;
        }

        if($output = extension_loaded('apc')) {
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
        if(self::$_apcu) {
            $info = apc_cache_info();
        } else {
            $info = apc_cache_info('user');
        }

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

        return @apc_store(
            $this->_prefix.$key, 
            [serialize($value), time()], 
            $lifeTime
        );
    }
    
    public function get($key, $default=null) {
        $val = apc_fetch($this->_prefix.$key);
        
        if(is_array($val)) {
            return unserialize($val[0]);
        }
        
        return $default;
    }
    
    public function has($key) {
        return is_array(apc_fetch($this->_prefix.$key));
    }
    
    public function remove($key) {
        $output = @apc_delete($this->_prefix.$key);

        /*
        if($this->_isCli) {
            $this->_retrigger('remove', $key);
        }
        */

        return $output;
    }
    
    public function clear() {
        if(!($this->_isCli && !ini_get('apc.enable_cli'))) {
            foreach($this->_getCacheList() as $set) {
                if(0 === strpos($set[self::$_setKey], $this->_prefix)) {
                    @apc_delete($set[self::$_setKey]);
                }
            }
        }

        $this->_retrigger('clear');
        return $this;
    }

    public function clearBegins($key) {

        foreach($this->_getCacheList() as $set) {
            if(0 === strpos($set[self::$_setKey], $this->_prefix.$key)) {
                @apc_delete($set[self::$_setKey]);
            }
        }
        
        $this->_retrigger('clearBegins', $key);
        return $this;
    }

    public function clearMatches($regex) {
        $prefixLength = strlen($this->_prefix);

        foreach($this->_getCacheList() as $set) {
            if(0 === strpos($set[self::$_setKey], $this->_prefix)
            && preg_match($regex, substr($set[self::$_setKey], $prefixLength))) {
                @apc_delete($set[self::$_setKey]);
            }
        }
        
        $this->_retrigger('clearMatches', $regex);
        return $this;
    }

    public function count() {
        $output = 0;

        foreach($this->_getCacheList() as $set) {
            if(0 === strpos($set[self::$_setKey], $this->_prefix)) {
                $output++;
            }
        }

        return $output;
    }

    public function getKeys() {
        $output = [];
        $length = strlen($this->_prefix);

        foreach($this->_getCacheList() as $set) {
            if(0 === strpos($set[self::$_setKey], $this->_prefix)) {
                $output[] = substr($set[self::$_setKey], $length);
            }
        }

        return $output;
    }
    
    public function getCreationTime($key) {
        $val = apc_fetch($this->_prefix.$key);
        
        if(is_array($val)) {
            return $val[1];
        }
        
        return null;
    }

    protected static function _getCacheList() {
        if(self::$_apcu) {
            $info = apc_cache_info();
        } else {
            $info = apc_cache_info('user');
        }

        $output = [];

        if(isset($info['cache_list'])) {
            $output = $info['cache_list'];
            
            if(isset($output[0])) {
                self::$_setKey = isset($output[0]['key']) ? 'key' : 'info';
            }
        }

        return $output;
    }

    protected function _retrigger($method, $arg=null) {
        $request = new arch\Request('cache/apc-clear');
        $request->query->cacheId = $this->_cache->getCacheId();
        $request->query->mode = $this->_isCli ? 'http' : 'cli';
        $request->query->{$method} = $arg;

        arch\task\Manager::getInstance()->launchQuietly($request);
    }
}
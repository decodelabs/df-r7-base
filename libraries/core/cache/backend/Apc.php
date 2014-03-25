<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\cache\backend;

use df;
use df\core;

class Apc implements core\cache\IBackend {
    
    use core\TValueMap;

    protected static $_apcu = null;
    
    protected $_prefix;
    protected $_lifeTime;
    protected $_cache;
    
    public static function purgeAll(core\collection\ITree $options) {
        if(!self::isLoadable()) {
            return;
        }

        if(self::$_apcu) {
            apc_clear_cache();
        } else {
            apc_clear_cache('user');
            apc_clear_cache('system');
        }
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
        $this->_prefix = $cache->getApplication()->getUniquePrefix().'-'.$cache->getCacheId().':';
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

        return apc_store(
            $this->_prefix.$key, 
            array(serialize($value), time()), 
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
        return apc_delete($this->_prefix.$key);
    }
    
    public function clear() {
        $setKey = self::$_apcu ? 'key' : 'info';

        foreach($this->_getCacheList() as $set) {
            if(0 === strpos($set[$setKey], $this->_prefix)) {
                apc_delete($set[$setKey]);
            }
        }
        
        return $this;
    }

    public function clearBegins($key) {
        $setKey = self::$_apcu ? 'key' : 'info';

        foreach($this->_getCacheList() as $set) {
            if(0 === strpos($set[$setKey], $this->_prefix.$key)) {
                apc_delete($set[$setKey]);
            }
        }
        
        return $this;
    }

    public function clearMatches($regex) {
        $prefixLength = strlen($this->_prefix);
        $setKey = self::$_apcu ? 'key' : 'info';

        foreach($this->_getCacheList() as $set) {
            if(0 === strpos($set[$setKey], $this->_prefix)
            && preg_match($regex, substr($set[$setKey], $prefixLength))) {
                apc_delete($set[$setKey]);
            }
        }
        
        return $this;
    }

    public function count() {
        $output = 0;
        $setKey = self::$_apcu ? 'key' : 'info';

        foreach($this->_getCacheList() as $set) {
            if(0 === strpos($set[$setKey], $this->_prefix)) {
                $output++;
            }
        }

        return $output;
    }

    public function getKeys() {
        $output = array();
        $length = strlen($this->_prefix);
        $setKey = self::$_apcu ? 'key' : 'info';

        foreach($this->_getCacheList() as $set) {
            if(0 === strpos($set[$setKey], $this->_prefix)) {
                $output[] = substr($set[$setKey], $length);
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

    protected function _getCacheList() {
        if(self::$_apcu) {
            $info = apc_cache_info();
        } else {
            $info = apc_cache_info('user');
        }

        if(isset($info['cache_list'])) {
            return $info['cache_list'];
        }

        return [];
    }
}
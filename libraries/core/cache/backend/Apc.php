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

        return extension_loaded('apc');
    }
    
    public function __construct(core\cache\ICache $cache, $lifeTime, core\collection\ITree $options) {
        $this->_cache = $cache;
        $this->_lifeTime = $lifeTime;
        $this->_prefix = $cache->getApplication()->getUniquePrefix().'-'.$cache->getCacheId().':';
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
        $info = apc_cache_info('user');

        foreach($info['cache_list'] as $set) {
            if(0 === strpos($set['info'], $this->_prefix)) {
                apc_delete($set['info']);
            }
        }
        
        return $this;
    }

    public function clearBegins($key) {
        $info = apc_cache_info('user');

        foreach($info['cache_list'] as $set) {
            if(0 === strpos($set['info'], $this->_prefix.$key)) {
                apc_delete($set['info']);
            }
        }
        
        return $this;
    }

    public function clearMatches($regex) {
        $info = apc_cache_info('user');
        $prefixLength = strlen($this->_prefix);

        foreach($info['cache_list'] as $set) {
            if(0 === strpos($set['info'], $this->_prefix)
            && preg_match($regex, substr($set['info'], $prefixLength))) {
                apc_delete($set['info']);
            }
        }
        
        return $this;
    }

    public function count() {
        $output = 0;
        $info = apc_cache_info('user');

        foreach($info['cache_list'] as $key) {
            if(0 === strpos($key['info'], $this->_prefix)) {
                $output++;
            }
        }

        return $output;
    }

    public function getKeys() {
        $output = array();
        $info = apc_cache_info('user');
        $length = strlen($this->_prefix);

        foreach($info['cache_list'] as $key) {
            if(0 === strpos($key['info'], $this->_prefix)) {
                $output[] = substr($key['info'], $length);
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
}
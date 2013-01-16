<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\cache\backend;

use df;
use df\core;

class Apc implements core\cache\IBackend {
    
    protected $_prefix;
    protected $_lifeTime;
    protected $_cache;
    
    public static function isLoadable() {
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

        foreach($info['cache_list'] as $key) {
            if(0 === strpos($key['info'], $this->_prefix)) {
                apc_delete($key['info']);
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
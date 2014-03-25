<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\cache\backend;

use df;
use df\core;

class Memcache implements core\cache\IBackend {
    
    use core\TValueMap;
    
    protected $_connection;
    protected $_prefix;
    protected $_lifeTime;
    protected $_cache;
    
    public static function purgeAll(core\collection\ITree $options) {
        if(!self::isLoadable()) {
            return;
        }

        $connection = self::_loadConnection($options);
        $connection->flush();
    }

    public static function isLoadable() {
        return extension_loaded('memcache');
    }

    protected static function _loadConnection(core\collection\ITree $options) {
        $output = new \Memcache();
        
        if($options->has('servers')) {
            $serverList = $options->servers;
        } else {
            $serverList = array($options);
        }
        
        foreach($serverList as $serverOptions) {
            $output->addServer(
                $serverOptions->get('host', '127.0.0.1'),
                $serverOptions->get('port', 11211),
                (bool)$serverOptions->get('persistent', true)
            );
        }

        return $output;
    }
    
    public function __construct(core\cache\ICache $cache, $lifeTime, core\collection\ITree $options) {
        $this->_cache = $cache;
        $this->_lifeTime = $lifeTime;
        $this->_prefix = $cache->getApplication()->getUniquePrefix().'-'.$cache->getCacheId().':';
        
        $this->_connection = self::_loadConnection($options);
    }

    public function getConnectionDescription() {
        $stats = $this->_connection->getExtendedStats();
        return implode(' + ', array_keys($stats));
    }

    public function getStats() {
        return $this->_connection->getStats();
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

        return $this->_connection->set(
            $this->_prefix.$key, 
            array(serialize($value), time()), 
            0,
            $lifeTime
        );
    }
    
    public function get($key, $default=null) {
        $val = $this->_connection->get($this->_prefix.$key);
        
        if(is_array($val)) {
            return unserialize($val[0]);
        }
        
        return $default;
    }
    
    public function has($key) {
        return is_array($this->_connection->get($this->_prefix.$key));
    }
    
    public function remove($key) {
        return $this->_connection->delete($this->_prefix.$key);
    }
    
    public function clear() {
        foreach($this->getKeys() as $key) {
            $this->remove($key);
        }

        return $this;
    }

    public function clearBegins($key) {
        foreach($this->getKeys() as $test) {
            if(0 === strpos($test, $key)) {
                $this->remove($test);
            }
        }

        return $this;
    }

    public function clearMatches($regex) {
        foreach($this->getKeys() as $test) {
            if(preg_match($regex, $test)) {
                $this->remove($test);
            }
        }

        return $this;
    }

    public function count() {
        $output = 0;
        $allSlabs = $this->_connection->getExtendedStats('slabs');

        foreach($allSlabs as $server => $slabs) {
            foreach($slabs as $slabId => $slabMeta) {
               $cdump = $this->_connection->getExtendedStats('cachedump', $slabId);

                foreach($cdump as $keys => $arrVal) {
                    if(!is_array($arrVal)) {
                        continue;
                    }

                    foreach($arrVal as $key => $value) {
                        if(0 === strpos($key, $this->_prefix)) {
                            $output++;
                        }
                    }
                }
            }
        }

        return $output;
    }

    public function getKeys() {
        $output = array();
        $allSlabs = $this->_connection->getExtendedStats('slabs');
        $length = strlen($this->_prefix);

        foreach($allSlabs as $server => $slabs) {
            foreach($slabs as $slabId => $slabMeta) {
                if(is_string($slabId)) {
                    continue;
                }

                $cdump = $this->_connection->getExtendedStats('cachedump', $slabId);

                foreach($cdump as $keys => $arrVal) {
                    if(!is_array($arrVal)) {
                        continue;
                    }

                    foreach($arrVal as $key => $value) {
                        if(0 === strpos($key, $this->_prefix)) {
                            $output[] = substr($key, $length);
                        }
                    }
                }
            }
        }

        return $output;
    }
    
    public function getCreationTime($key) {
        $val = $this->_connection->get($this->_prefix.$key);
        
        if(is_array($val)) {
            return $val[1];
        }
        
        return null;
    }
}
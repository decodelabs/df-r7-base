<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\cache;

use df;
use df\core;

abstract class Base implements ICache {
    
    use core\TApplicationAware;
    
    const REGISTRY_PREFIX = 'cache://';
    
    const IS_DISTRIBUTED = true;
    const DEFAULT_LIFETIME = 1800;
    
    protected static $_cacheId;
    
    private $_backend;
    
    public static function getInstance(core\IApplication $application=null) {
        if(!$application) {
            $application = df\Launchpad::getActiveApplication();
        }
        
        $class = get_called_class();
        $id = self::REGISTRY_PREFIX.$class::getCacheId();
        
        if(!$cache = $application->_getCacheObject($id)) {
            $application->_setCacheObject(
                $cache = new $class($application)
            );
        }
        
        return $cache;
    }
    
    
// Construct
    protected function __construct(core\IApplication $application) {
        $this->_application = $application;
        $this->_backend = $this->_loadBackend();
    }
    
    protected function _loadBackend() {
        $config = Config::getInstance($this->_application);
        $options = $config->getOptionsFor($this);
        
        if(!$options->has('backend')) {
            throw new RuntimeException(
                'There are no available backends for cache '.$this->getCacheId()
            );
        }
        
        $class = 'df\\core\\cache\\backend\\'.$options['backend'];
        
        $lifeTime = 0;
        
        if(isset($options->lifeTime)) {
            $lifeTime = (int)$options['lifeTime'];
        }
        
        if($lifeTime < 1) {
            $lifeTime = $this->getDefaultLifeTime();
        }
        
        return new $class($this, $lifeTime, $options);
    }
    
    
// Properties
    public static function getCacheId() {
        if(!static::$_cacheId) {
            $parts = explode('\\', get_called_class());
            array_shift($parts);
            static::$_cacheId = implode('/', $parts);
        }
        
        return static::$_cacheId; 
    }
    
    final public function getRegistryObjectKey() {
        return self::REGISTRY_PREFIX.static::getCacheId();
    }
    
    public function getLifeTime() {
        return $this->_backend->getLifeTime();
    }
    
    public function getDefaultLifeTime() {
        return static::DEFAULT_LIFETIME;
    }
    
    public function isCacheDistributed() {
        return static::IS_DISTRIBUTED && $this->_application->isDistributed();
    }
    
    
// Access
    public function set($key, $value) {
        $this->_backend->set($key, $value);
        return $this;
    }
    
    public function get($key, $default=null) {
        return $this->_backend->get($key, $default);
    }
    
    public function has($key) {
        return $this->_backend->has($key);
    }
    
    public function remove($key) {
        $this->_backend->remove($key);
        return $this;
    }
    
    public function clear() {
        $this->_backend->clear();
        return $this;
    }
    
    public function offsetSet($key, $value) {
        return $this->set($key, $value);
    }
    
    public function offsetGet($key) {
        return $this->get($key);
    }
    
    public function offsetExists($key) {
        return $this->has($key);
    }
    
    public function offsetUnset($key) {
        return $this->remove($key);
    }
    
    
    public function getCreationTime($key) {
        return $this->_backend->getCreationTime($key);
    }
}

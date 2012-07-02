<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\application;

use df;
use df\core;

abstract class Base implements core\IApplication, core\IDumpable {
    
    const RUN_MODE = null;
    
    protected $_name;
    protected $_uniquePrefix;
    protected $_passKey;
    protected $_activeLocations = array();
    protected $_activePackages = array();
    
    protected $_isDistributed = false;
    protected $_debugTransport;
    
    protected $_isRunning = false;
    protected $_objectCache = array();
    
    public static function factory($appType) {
        $class = 'df\\core\\application\\'.$appType;
        
        if(!class_exists($class)) {
            throw new core\ApplicationNotFoundException(
                'Application type '.$appType.' could not be found'
            );
        }
        
        return new $class();
    }
    
    
// Construct
    protected function __construct() {
        $appConfig = Config::getInstance($this);
        $this->_name = $appConfig->getApplicationName();
        $this->_uniquePrefix = $appConfig->getUniquePrefix();
        $this->_passKey = $appConfig->getPassKey();
        $this->_activePackages = $appConfig->getActivePackages();
        
        $envConfig = core\Environment::getInstance($this);
        $this->_activeLocations = $envConfig->getActiveLocations();
        $this->_isDistributed = $envConfig->isDistributed();
    }
    
    
// Paths
    public static function getApplicationPath() {
        return df\Launchpad::$applicationPath;
    }
    
    
    
    public function getLocalDataStoragePath() {
        return df\Launchpad::$applicationPath.'/data/local';
    }
    
    public function getSharedDataStoragePath() {
        return df\Launchpad::$applicationPath.'/data/shared';
    }
    
    public function getLocalStaticStoragePath() {
        return df\Launchpad::$applicationPath.'/static/local';
    }
    
    public function getSharedStaticStoragePath() {
        return df\Launchpad::$applicationPath.'/static/shared';
    }
    
    
    
// Execute
    protected function _beginDispatch() {
        if($this->_isRunning) {
            throw new core\RuntimeException(
                'Application instance is already running'
            );
        }
        
        $this->_isRunning = true;
        
        if(df\Launchpad::$application !== $this) {
            throw new core\RuntimeException(
                'Application cannot be dispatched unless it is the active application in Launchpad - use df\\Launchpad::runApplication() instead'
            );
        }
        
        return $this;
    }

    public function capture() {
        core\stub();
    }
    
    public function isRunning() {
        return $this->_isRunning;
    }
    
    public function shutdown() {}
    
    
// Environment
    public function getEnvironmentId() {
        return df\Launchpad::$environmentId;
    }
    
    public function getEnvironmentMode() {
        return df\Launchpad::$environmentMode;
    }
    
    public function isDevelopment() {
        return $this->_environmentMode == 'development';
    }
    
    public function isTesting() {
        return $this->_environmentMode == 'testing';
    }
    
    public function isProduction() {
        return $this->_environmentMode == 'production';
    }

    public function canDebug() {
        return $this->_environmentMode == 'development' || $this->_environmentMode == 'testing';
    }
    
    public function getRunMode() {
        if(static::RUN_MODE !== null) {
            return static::RUN_MODE;
        }
        
        $parts = explode('\\', get_class($this));
        return array_pop($parts);
    }

    public function isDistributed() {
        return $this->_isDistributed;
    }
    
    public function getDebugTransport() {
        if(!$this->_debugTransport) {
            $this->_debugTransport = $this->_getNewDebugTransport();
        }
        
        return $this->_debugTransport;
    }
    
    protected function _getNewDebugTransport() {
        return new core\debug\transport\Base();
    }
    
    
// Members
    public function setName($name) {
        $this->_name = $name;
        return $this;
    }
    
    public function getName() {
        return $this->_name;
    }
    
    public function getUniquePrefix() {
        return $this->_uniquePrefix;
    }
    
    public function getPassKey() {
        return $this->_passKey;
    }
    
    public function getActiveLocations() {
        return $this->_activeLocations;
    }

    public function getActivePackages() {
        return $this->_activePackages;
    }
    
// Cache objects
    public function _setCacheObject(core\IRegistryObject $object) {
        $this->_objectCache[$object->getRegistryObjectKey()] = $object;
        return $this;
    }
    
    public function _getCacheObject($key) {
        if(isset($this->_objectCache[$key])) {
            return $this->_objectCache[$key];
        }
        
        return null;
    }
    
    public function _hasCacheObject($key) {
        return isset($this->_objectCache[$key]);
    }
    
    public function _removeCacheObject($key) {
        if($key instanceof IRegistryObject) {
            $key = $key->getRegistryObjectKey();
        }
        
        unset($this->_objectCache[$key]);
        return $this;
    }
    
    
// Dump
    public function getDumpProperties() {
        return [
            'name' => $this->_name,
            'path' => $this->getApplicationPath(),
            'environmentId' => $this->getEnvironmentId(),
            'environmentMode' => $this->_environmentMode,
            'runMode' => $this->getRunMode(),
            'cacheObjects' => count($this->_objectCache)
        ];
    }
} 
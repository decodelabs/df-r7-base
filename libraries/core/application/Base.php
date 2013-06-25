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
    
    protected $_debugTransport;
    
    protected $_isRunning = false;
    protected $_registry = array();
    
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
    protected function __construct() {}
    
    
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
    
    public function shutdown() {
        foreach($this->_registry as $object) {
            $object->onApplicationShutdown();
        }
    }
    
    
// Environment
    public function getEnvironmentId() {
        return df\Launchpad::$environmentId;
    }
    
    public function getEnvironmentMode() {
        return df\Launchpad::getEnvironmentMode();
    }
    
    public function isDevelopment() {
        return df\Launchpad::isDevelopment();
    }
    
    public function isTesting() {
        return df\Launchpad::isTesting();
    }
    
    public function isProduction() {
        return df\Launchpad::isProduction();
    }

    public function canDebug() {
        return df\Launchpad::isTesting();
    }
    
    public function getRunMode() {
        if(static::RUN_MODE !== null) {
            return static::RUN_MODE;
        }
        
        $parts = explode('\\', get_class($this));
        return array_pop($parts);
    }

    public function isDistributed() {
        return df\Launchpad::$isDistributed;
    }


// Debug
    public function createDebugContext() {
        return new core\debug\Context();
    }
    
    public function renderDebugContext(core\debug\IContext $context) {
        df\Launchpad::loadBaseClass('core/debug/renderer/PlainText');
        echo (new core\debug\renderer\PlainText($context))->render();

        return $this;
    }    
    
// Members
    public function setName($name) {
        df\Launchpad::$applicationName = $name;
        return $this;
    }
    
    public function getName() {
        return df\Launchpad::$applicationName;
    }
    
    public function getUniquePrefix() {
        return df\Launchpad::$uniquePrefix;
    }
    
    public function getPassKey() {
        return df\Launchpad::$passKey;
    }

    
// Cache objects
    public function setRegistryObject(core\IRegistryObject $object) {
        $this->_registry[$object->getRegistryObjectKey()] = $object;
        return $this;
    }
    
    public function getRegistryObject($key) {
        if(isset($this->_registry[$key])) {
            return $this->_registry[$key];
        }
        
        return null;
    }
    
    public function hasRegistryObject($key) {
        return isset($this->_registry[$key]);
    }
    
    public function removeRegistryObject($key) {
        if($key instanceof IRegistryObject) {
            $key = $key->getRegistryObjectKey();
        }
        
        unset($this->_registry[$key]);
        return $this;
    }
    
    
// Dump
    public function getDumpProperties() {
        return [
            'name' => $this->getName(),
            'path' => $this->getApplicationPath(),
            'environmentId' => $this->getEnvironmentId(),
            'environmentMode' => $this->getEnvironmentMode(),
            'runMode' => $this->getRunMode(),
            'registry' => count($this->_registry)
        ];
    }
} 
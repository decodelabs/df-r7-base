<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\application;

use df;
use df\core;

abstract class Base implements core\IApplication {
    
    const RUN_MODE = null;
    
    protected $_name;
    protected $_uniquePrefix;
    protected $_passKey;
    
    protected $_environmentMode = 'development';
    protected $_isDistributed = false;
    
    protected $_isRunning = false;
    protected $_managers = array();
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
        
    }
    
    
    
// Execute
    public function dispatch() {
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
        
        core\dump($this);
        
        df\Launchpad::benchmark();
    }
    
    public function capture() {
        core\stub();
    }
    
    public function isRunning() {
        core\stub();
    }
    
    public function launchPayload($payload) {
        core\stub();
    }
    
    public function shutdown() {}
    
    
// Environment
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
} 
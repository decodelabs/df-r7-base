<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\system;

use df;
use df\core;
use df\halo;

abstract class Base implements ISystem {
    
    private static $_instance;
    
    protected $_platformType;
    protected $_osName;
    protected $_osVersion;
    protected $_osRelease;
    protected $_hostName;
    
    public static function getInstance() {
        if(!self::$_instance) {
            self::$_instance = self::factory();
        }
        
        return self::$_instance;
    }
    
    public static function factory() {
        $osName = php_uname('s');
        
        if(substr(strtolower($osName), 0, 3) == 'win') {
            $osName = 'Windows';
        }
        
        $class = 'df\\halo\\system\\'.ucfirst($osName);
        
        if(!class_exists($class)) {
            $class = 'df\\halo\\system\\Unix';
        }
        
        return new $class($osName);
    }
    
    protected function __construct($osName) {
        $this->_osName = $osName;
        $this->_osVersion = php_uname('v');
        $this->_osRelease = php_uname('r');
        $this->_hostName = php_uname('n');
    }
    

    public function getPlatformType() {
        return $this->_platformType;
    }
    
    public function getOSName() {
        return $this->_osName;
    }
    
    public function getOSDistribution() {
        return $this->_osName;
    }
    
    public function getOSVersion() {
        return $this->_osVersion;
    }
    
    public function getOSRelease() {
        return $this->_osRelease;
    }
    
    
    public function getHostName() {
        return $this->_hostName;
    }
    
    
    public function getProcess() {
        return halo\process\Base::getCurrent();
    }
}
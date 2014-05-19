<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\process\launcher;

use df;
use df\core;
use df\halo;

abstract class Base implements halo\process\ILauncher {
    
    protected $_processName;
    protected $_args = [];
    protected $_path;
    protected $_user;
    protected $_title;
    protected $_priority;
    protected $_workingDirectory;
    protected $_multiplexer;
    
    public static function factory($processName, $args=null, $path=null) {
        $system = halo\system\Base::getInstance();
        $class = 'df\\halo\\process\\launcher\\'.$system->getOSName();
        
        if(!class_exists($class)) {
            $class = 'df\\halo\\process\\launcher\\'.$system->getPlatformType();
            
            if(!class_exists($class)) {
                throw new halo\process\RuntimeException(
                    'Sorry, I don\'t know how to launch processes on this platform!'
                );
            }
        }
        
        return new $class($processName, $args, $path);
    }
    
    
    protected function __construct($processName, $args=null, $path=null) {
        $this->setProcessName($processName);
        $this->setArgs($args);
        $this->setPath($path);
        $this->setTitle($this->_processName);
    }
    
    
    public function setProcessName($name) {
        $this->_processName = $name;
        return $this;
    }
    
    public function getProcessName() {
        return $this->_processName;
    }
    
    public function setArgs($args) {
        $this->_args = core\collection\Util::flattenArray(func_get_args());
        return $this;
    }
    
    public function getArgs() {
        return $this->_args;
    }
    
    public function setPath($path) {
        $this->_path = $path;
        return $this;
    }
    
    public function getPath() {
        return $this->_path;
    }
    
    public function setUser($user) {
        $this->_user = $user;
        return $this;
    }

    public function getUser() {
        return $this->_user;
    }
    
    
    public function isPrivileged() {
        core\stub();
    }
    
    public function setTitle($title) {
        $this->_title = $title;
        return $this;
    }
    
    public function getTitle() {
        return $this->_title;
    }
    
    public function setPriority($priority) {
        $this->_priority = (int)$priority;
        return $this;
    }
    
    public function getPriority() {
        return $this->_priority;
    }

    public function setWorkingDirectory($path) {
        $this->_workingDirectory = $path;
        return $this;
    }

    public function getWorkingDirectory() {
        return $this->_workingDirectory;
    }

    public function setMultiplexer(core\io\IMultiplexer $multiplexer=null) {
        $this->_multiplexer = $multiplexer;
        return $this;
    }

    public function getMultiplexer() {
        return $this->_multiplexer;
    }
}
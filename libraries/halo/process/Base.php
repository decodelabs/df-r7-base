<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\process;

use df;
use df\core;
use df\halo;

abstract class Base implements IProcess {
    
    private static $_current;
    
    protected $_processId;
    protected $_title;
    
    public static function setCurrent(IManagedProcess $process) {
        core\stub();
    }
    
    public static function getCurrent() {
        if(!self::$_current) {
            $system = halo\system\Base::getInstance();
            $class = 'df\\halo\\process\\'.$system->getOSName().'Managed';
            
            if(!class_exists($class)) {
                $class = 'df\\halo\\process\\'.$system->getPlatformType().'Managed';
                
                if(!class_exists($class)) {
                    throw new halo\process\RuntimeException(
                        'Sorry, managed processes aren\'t currently supported on this platform!'
                    );
                }
            }
            
            $pid = $class::getCurrentProcessId();
            self::$_current = new $class($pid, 'Current process');
        }
        
        return self::$_current;
    }
    
    
    public static function launch($process, $args=null, $path=null) {
        return self::newLauncher($process, $args, $path)->launch();
    }

    public static function launchScript($path, $args=null) {
        return self::newScriptLauncher($path, $args)->launch();
    }
    
    public static function launchBackground($process, $args=null, $path=null) {
        return self::newLauncher($process, $args, $path)->launchBackground();
    }

    public static function launchBackgroundScript($path, $args=null) {
        return self::newScriptLauncher($path, $args)->launchBackground();
    }
    
    public static function launchManaged($process, $args=null, $path=null) {
        return self::newLauncher($process, $args, $path)->launchManaged();
    }

    public static function launchManagedScript($path, $args=null) {
        return self::newScriptLauncher($path, $args)->launchManaged();
    }

    public static function newLauncher($process, $args=null, $path=null) {
        return halo\process\launcher\Base::factory($process, $args, $path);
    }

    public static function newScriptLauncher($path, $args=null) {
        $envConfig = core\Environment::getInstance(df\Launchpad::getActiveApplication());
        $binaryPath = $envConfig->getPhpBinaryPath();
        $phpName = basename($binaryPath);
        $phpPath = null;

        if($phpName != $binaryPath) {
            $phpPath = dirname($binaryPath);
        }

        return halo\process\launcher\Base::factory($phpName, trim($path.' '.$args), $phpPath);
    }
    
    
    
    public function __construct($processId, $title) {
        $this->_processId = $processId;
        $this->_title = $title;
    }
    
    public function getProcessId() {
        return $this->_processId;
    }
    
    public function getTitle() {
        return $this->_title;
    }
}
<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core;

use df;
use df\core;

// Exceptions
interface IException {}
class LogicException extends \LogicException implements IException {}
class UnexpectedValueException extends \UnexpectedValueException implements IException {}
class RuntimeException extends \RuntimeException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}
class ApplicationNotFoundException extends RuntimeException {}

// Generic interfaces
interface IStringProvider {
    public function toString();
    public function __toString();
}


trait TStringProvider {
    
    public function __toString() {
        try {
            return (string)$this->toString();
        } catch(\Exception $e) {
            return '';
        }
    }
}


interface IArrayProvider {
    public function toArray();
}

interface IValueMap {
    public function set($key, $value);
    public function get($key, $default=null);
    public function has($key);
    public function remove($key);
}


interface IValueContainer {
    public function setValue($value);
    public function getValue($default=null);
}

interface IUserValueContainer extends IValueContainer {
    public function getStringValue($default='');
    public function hasValue();
}

interface IErrorContainer {
    public function isValid();
    public function setErrors(array $errors);
    public function addErrors(array $errors);
    public function addError($code, $message);
    public function getErrors();
    public function getError($code);
    public function hasErrors();
    public function hasError($code);
    public function clearErrors();
    public function clearError($code);
}

trait TErrorContainer {
    
    protected $_errors = array();
    
    public function isValid() {
        return $this->hasErrors();
    }
    
    public function setErrors(array $errors) {
        $this->_errors = array();
        return $this->addErrors($errors);
    }
    
    public function addErrors(array $errors) {
        foreach($errors as $code => $message) {
            $this->addError($code, $message);
        }    
        
        return $this;
    }
    
    public function addError($code, $message) {
        $this->_errors[$code] = $message;
        return $this;
    }
    
    public function getErrors() {
        return $this->_errors;
    }
    
    public function getError($code) {
        if(isset($this->_errors[$code])) {
            return $this->_errors[$code];
        }
        
        return null;
    }
    
    public function hasErrors() {
        return !empty($this->_errors);
    }
    
    public function hasError($code) {
        return isset($this->_errors[$code]);
    }
    
    public function clearErrors() {
        $this->_errors = array();
        return $this;
    }
    
    public function clearError($code) {
        unset($this->_errors[$code]);
        return $this;
    }
}


interface IAttributeContainer {
    public function setAttributes(array $attributes);
    public function addAttributes(array $attributes);
    public function getAttributes();
    public function setAttribute($key, $value);
    public function getAttribute($key, $default=null);
    public function removeAttribute($key);
    public function hasAttribute($key);
}


trait TAttributeContainer {
    
    protected $_attributes = array();
    
    public function setAttributes(array $attributes) {
        $this->_attributes = array();
        return $this->addAttributes($attributes);
    }
    
    public function addAttributes(array $attributes) {
        foreach($this->_attributes as $key => $value){
            $this->setAttribute($key, $value);
        }
        
        return $this;
    }
    
    public function getAttributes() {
        return $this->_attributes;
    }
    
    public function setAttribute($key, $value) {
        $this->_attributes[$key] = $value;
        return $this;
    }
    
    public function getAttribute($key, $default=null) {
        if(isset($this->_attributes[$key])) {
            return $this->_attributes[$key];
        }
        
        return $default;
    }
    
    public function removeAttribute($key) {
        unset($this->_attributes[$key]);
        return $this;
    }
    
    public function hasAttribute($key) {
        return isset($this->_attributes[$key]);
    }
}


interface IDumpable {
    public function getDumpProperties();
}



// Loader
interface ILoader {
    public function activate();
    public function deactivate();
    public function isActive();
    
    public function loadClass($class);
    public function getClassSearchPaths($class);
    
    public function findFile($path);
    public function getFileSearchPaths($path);
    
    public function registerLocation($name, $path);
    public function unregisterLocation($name);
    public function getLocations();
    
    public function loadBasePackages();
    public function loadPackages(array $packages);
    public function getPackages();
    public function hasPackage($package);
    public function getPackage($package);
    
    public function shutdown();
}



interface IPackage {}

class Package implements IPackage {
    
    const PRIORITY = 20;
    
    public $path;
    public $name;
    public $priority;
    
    public static function factory($name) {
        $class = 'df\\apex\\packages\\'.$name.'\\Package';
        
        if(!class_exists($class)) {
            throw new RuntimeException('Package '.$name.' could not be found');
        }
        
        return new $class($name);
    }
    
    public function __construct($name, $priority=null, $path=null) {
        if($path === null) {
            $ref = new \ReflectionObject($this);
            $path = dirname($ref->getFileName());
        }
        
        if(df\Launchpad::IS_COMPILED) {
            $this->path = df\Launchpad::ROOT_PATH.'/apex/packages/'.$name;
        } else {
            $this->path = $path;
        }
        
        $this->name = $name;
        
        if($priority === null) {
            $priority = static::PRIORITY;
        }
        
        $this->priority = $priority;
    }
}


// Applications
interface IApplication {
    // Paths
    public static function getApplicationPath();
    public function getLocalDataStoragePath();
    public function getSharedDataStoragePath();
    public function getLocalStaticStoragePath();
    public function getSharedStaticStoragePath();
    
    // Execute
    public function dispatch();
    public function capture();
    public function isRunning();
    public function launchPayload($payload);
    public function shutdown();
    
    // Environment
    public function getEnvironmentId();
    public function getEnvironmentMode();
    public function isDevelopment();
    public function isTesting();
    public function isProduction();
    public function canDebug();
    public function getRunMode();
    public function isDistributed();
    public function getDebugTransport();
    
    // Members
    public function setName($name);
    public function getName();
    public function getUniquePrefix();
    public function getPassKey();
    public function getActivePackages();
    
    // Cache
    public function _setCacheObject(IRegistryObject $object);
    public function _getCacheObject($key);
    public function _hasCacheObject($key);
    public function _removeCacheObject($key);
}

interface IRegistryObject {
    public function getRegistryObjectKey();
}

interface IApplicationAware {
    public function getApplication();
}

trait TApplicationAware {
    
    protected $_application;
    
    public function getApplication() {
        return $this->_application;
    }
}



// Manager
interface IManager extends IApplicationAware, IRegistryObject {}

trait TManager {
    
    use TApplicationAware;
    
    public static function getInstance(IApplication $application=null) {
        if(!$application) {
            $application = df\Launchpad::getActiveApplication();
        }
        
        if(!$output = $application->_getCacheObject(static::REGISTRY_PREFIX)) {
            $application->_setCacheObject(
                $output = new self($application)
            );
        }
        
        return $output;
    }
    
    protected function __construct(core\IApplication $application) {
        $this->_application = $application;
    }
    
    public function getRegistryObjectKey() {
        return static::REGISTRY_PREFIX;
    }
}


// Payload
interface IPayload {}
interface IDeferredPayload extends IPayload, IApplicationAware {}



// Config
interface IConfig extends IApplicationAware, IRegistryObject {
    public function getDefaultValues();
    public function getConfigId();
    public function getConfigValues();
}


include __DIR__.'/Loader.php';

if(!df\Launchpad::IS_COMPILED) {
    include __DIR__.'/DevLoader.php';
}



// Debug
function qDump($arg1) {
    while(ob_get_level()) {
        ob_end_clean();
    }
    
    if(func_num_args() > 1) {
        $args = func_get_args();
    } else {
        $args = $arg1;
    }
    
    echo '<pre>'.print_r($args, true).'</pre>';
    df\Launchpad::benchmark();
}

function stub() {
    return df\Launchpad::getDebugContext()->addStub(
            func_get_args(), 
            core\debug\StackCall::factory(1)
        )
        ->flush();
}

function dump($arg1) {
    return df\Launchpad::getDebugContext()->addDumpList(
            func_get_args(), 
            false, 
            core\debug\StackCall::factory(1)
        )
        ->flush();
}

function dumpDeep($arg1) {
    return df\Launchpad::getDebugContext()->addDumpList(
            func_get_args(), 
            true, 
            core\debug\StackCall::factory(1)
        )
        ->flush();
}

function debug() {
    return df\Launchpad::getDebugContext();
}

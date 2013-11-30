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
class HelperNotFoundException extends RuntimeException {}

### Generic interfaces

// String provider
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

// Array provider
interface IArrayProvider {
    public function toArray();
}

interface IArrayInterchange extends IArrayProvider {
    public static function fromArray(array $array);
}

interface IExtendedArrayProvider extends IArrayProvider {
    public function getJsonString();
}

trait TExtendedArrayProvider {
    
    public function toJsonString() {
        return json_encode($this->toArray());
    }
}

interface IExtendedArrayInterchange extends IExtendedArrayProvider, IArrayInterchange {

    public static function fromJsonString($json);
}

trait TExtendedArrayInterchange {

    use TExtendedArrayProvider;

    public static function fromJsonString($json) {
        return self::fromArray((array)json_decode($json, true));
    }
}


// Value map
interface IValueMap {
    public function set($key, $value);
    public function get($key, $default=null);
    public function has($key);
    public function remove($key);
    public function importFrom(IValueMap $source, array $fields);
}

trait TValueMap {

    public function importFrom(IValueMap $source, array $fields) {
        foreach($fields as $key => $field) {
            if(is_string($key)) {
                $field = $key;
                $value = $field;
            } else {
                $value = $source->get($field);
            }

            $this->set($field, $value);
        }

        return $this;
    }
}

// Value container
interface IValueContainer {
    public function setValue($value);
    public function getValue($default=null);
}

interface IUserValueContainer extends IValueContainer {
    public function getStringValue($default='');
    public function hasValue();
}


// Chainer
interface IChainable {
    public function chain(Callable $callback);
    public function chainIf($test, Callable $callback);
}

trait TChainable {
    public function chain(Callable $callback) {
        $callback($this);
        return $this;
    }

    public function chainIf($test, Callable $callback) {
        if($test) {
            $callback($this);
        }

        return $this;
    }
}


// Error container
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


// Attribute container
interface IAttributeContainer {
    public function setAttributes(array $attributes);
    public function addAttributes(array $attributes);
    public function getAttributes();
    public function setAttribute($key, $value);
    public function getAttribute($key, $default=null);
    public function removeAttribute($key);
    public function hasAttribute($key);
    public function countAttributes();
}

trait TAttributeContainer {
    
    protected $_attributes = array();
    
    public function setAttributes(array $attributes) {
        $this->_attributes = array();
        return $this->addAttributes($attributes);
    }
    
    public function addAttributes(array $attributes) {
        foreach($attributes as $key => $value){
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

    public function countAttributes() {
        return count($this->_attributes);
    }
}

trait TAttributeContainerArrayAccessProxy {

    public function offsetSet($key, $value) {
        return $this->setAttribute($key, $value);
    }
    
    public function offsetGet($key) {
        return $this->getAttribute($key);
    }
    
    public function offsetExists($key) {
        return $this->hasAttribute($key);
    }
    
    public function offsetUnset($key) {
        return $this->removeAttribute($key);
    }
}

trait TArrayAccessedAttributeContainer {
    use TAttributeContainer;
    use TAttributeContainerArrayAccessProxy;
}



// Arg container
interface IArgContainer {
    public function setArgs(array $args);
    public function addArgs(array $args);
    public function getArgs(array $add=array());
    public function setArg($name, $value);
    public function getArg($name, $default=null);
    public function hasArg($name);
    public function removeArg($name);
}

trait TArgContainer {
    
    protected $_args = array();
    
    public function setArgs(array $args) {
        $this->_args = array();
        return $this->addArgs($args);
    }
    
    public function addArgs(array $args) {
        foreach($args as $key => $value){
            $this->setArg($key, $value);
        }
        
        return $this;
    }
    
    public function getArgs(array $add=array()) {
        return array_merge($this->_args, $add);
    }
    
    public function setArg($key, $value) {
        $this->_args[$key] = $value;
        return $this;
    }
    
    public function getArg($key, $default=null) {
        if(isset($this->_args[$key])) {
            return $this->_args[$key];
        }
        
        return $default;
    }
    
    public function removeArg($key) {
        unset($this->_args[$key]);
        return $this;
    }
    
    public function hasArg($key) {
        return isset($this->_args[$key]);
    }
}

trait TArgContainerArrayAccessProxy {

    public function offsetSet($key, $value) {
        return $this->setArg($key, $value);
    }
    
    public function offsetGet($key) {
        return $this->getArg($key);
    }
    
    public function offsetExists($key) {
        return $this->hasArg($key);
    }
    
    public function offsetUnset($key) {
        return $this->removeArg($key);
    }
}

trait TArrayAccessedArgContainer {
    use TArgContainer;
    use TArgContainerArrayAccessProxy;
}


// Helpers
interface IHelperProvider {
    public function getHelper($name, $returnNull=false);
    public function __get($member);
}

trait THelperProvider {
    
    protected $_helpers = array();
    
    public function __get($key) {
        return $this->getHelper($key);
    }
    
    public function getHelper($name, $returnNull=false) {
        $name = ucfirst($name);
        
        if(!isset($this->_helpers[$name])) {
            if(!$output = $this->_loadHelper($name)) {
                $this->_helpers[$name] = $output = null;
            } else {
                $this->_helpers[$name] = $output;
            }
        } else {
            $output = $this->_helpers[$name];
        }
        
        if(!$output && !$returnNull) {
            throw new HelperNotFoundException(
                'Helper '.$name.' could not be found'
            );
        }

        return $output;
    }
    
    abstract protected function _loadHelper($name);

    protected function _loadSharedHelper($name) {
        $class = 'df\\plug\\shared\\'.$this->application->getRunMode().$name;
        
        if(!class_exists($class)) {
            $class = 'df\\plug\\shared\\'.$name;
            
            if(!class_exists($class)) {
                return null;
            }
        }

        $context = $this;

        if(!$context instanceof IContext) {
            if($this instanceof IApplicationAware) {
                $application = $this->getApplication();
            } else {
                $application = df\Launchpad::$application;
            }

            $context = new SharedContext($application);
        }

        return new $class($context);
    }
}

interface IHelper {}



// Dumpable
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
    public function lookupFileList($path, $extensions=null);
    
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


// Package
interface IPackage {}

class Package implements IPackage {
    
    const PRIORITY = 20;
    
    public static $dependencies = [];

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
            $this->path = df\Launchpad::DF_PATH.'/apex/packages/'.$name;
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
    public function getStaticStoragePath();
    
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

    // Debug
    public function createDebugContext();
    public function renderDebugContext(core\debug\IContext $context);
    
    // Members
    public function setName($name);
    public function getName();
    public function getUniquePrefix();
    public function getPassKey();
    
    // Cache
    public function setRegistryObject(IRegistryObject $object);
    public function getRegistryObject($key);
    public function hasRegistryObject($key);
    public function removeRegistryObject($key);
}

interface IRegistryObject {
    public function getRegistryObjectKey();
    public function onApplicationShutdown();
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
        
        if(!$output = $application->getRegistryObject(static::REGISTRY_PREFIX)) {
            $output = static::_getDefaultInstance($application);
            static::setInstance($output);
        }
        
        return $output;
    }

    public static function setInstance(IManager $manager) {
        return $manager->getApplication()->setRegistryObject($manager);
    }

    protected static function _getDefaultInstance(IApplication $application) {
        return new self($application);
    }
    
    protected function __construct(core\IApplication $application) {
        $this->_application = $application;
    }
    
    public function getRegistryObjectKey() {
        return static::REGISTRY_PREFIX;
    }

    public function onApplicationShutdown() {}
}


// Context
interface IContext extends core\IApplicationAware, core\IHelperProvider {
    public function setRunMode($mode);
    public function getRunMode();

    // Locale
    public function setLocale($locale);
    public function getLocale();

    // Helpers
    public function throwError($code=500, $message='');
    public function findFile($path);
    
    public function getI18nManager();
    public function getPolicyManager();
    public function getSystemInfo();
    public function getUserManager();
}


trait TContext {

    use THelperProvider;

    public $application;
    protected $_locale;
    protected $_runMode;

// Application
    public function getApplication() {
        return $this->application;
    }
    
    public function setRunMode($runMode) {
        $this->_runMode = $runMode;
        return $this;
    }

    public function getRunMode() {
        if($this->_runMode) {
            return $this->_runMode;
        }

        return $this->application->getRunMode();
    }


// Locale
    public function setLocale($locale) {
        if($locale === null) {
            $this->_locale = null;
        } else {
            $this->_locale = core\i18n\Locale::factory($locale);
        }
        
        return $this;
    }
    
    public function getLocale() {
        if($this->_locale) {
            return $this->_locale;
        } else {
            return core\i18n\Manager::getInstance($this->application)->getLocale(); 
        }
    }


// Helpers
    public function throwError($code=500, $message='') {
        throw new ContextException($message, (int)$code);
    }
    
    public function findFile($path) {
        return df\Launchpad::$loader->findFile($path);
    }

    public function getI18nManager() {
        return core\i18n\Manager::getInstance($this->application);
    }
    
    public function getPolicyManager() {
        return core\policy\Manager::getInstance($this->application);
    }
    
    public function getSystemInfo() {
        return df\halo\system\Base::getInstance();
    }
    
    public function getUserManager() {
        return df\user\Manager::getInstance($this->application);
    }

    public function __get($key) {
        return $this->_getDefaultMember($key);
    }

    protected function _getDefaultMember($key) {
        switch($key) {
            case 'context':
                return $this;
            
            case 'application':
                return $this->application;
                
            case 'runMode':
                return $this->application->getRunMode();
                
            case 'locale':
                return $this->getLocale();
                
                
            case 'i18n':
                return core\i18n\Manager::getInstance($this->application);
                
            case 'policy':
                return core\policy\Manager::getInstance($this->application);
                
            case 'system':
                return df\halo\system\Base::getInstance();
                
            case 'user':
                return df\user\Manager::getInstance($this->application);
                
            default:
                return $this->getHelper($key);
        }
    }
}

class SharedContext implements IContext {

    use TContext;

    public function __construct(IApplication $application) {
        $this->application = $application;
    }

    protected function _loadHelper($name) {
        return $this->_loadSharedHelper($name);
    }
}

class ContextException extends \RuntimeException implements IException {}

interface IContextAware {
    public function getContext();
    public function hasContext();
}

trait TContextAware {
    
    protected $_context;
    
    public function getContext() {
        return $this->_context;
    }

    public function hasContext() {
        return $this->_context !== null;
    }
}


trait TContextProxy {
    
    use TContextAware;
    
    public function __call($method, $args) {
        if($this->_context) {
            return call_user_func_array(array($this->_context, $method), $args);
        }
    }
    
    public function __get($key) {
        if($this->_context) {
            return $this->_context->__get($key);
        }
    }
}

interface ISharedHelper extends IHelper {}

trait TSharedHelper {

    protected $_context;

    public function __construct(IContext $context) {
        $this->_context = $context;
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
    public function reset();
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
            core\debug\StackCall::factory(1),
            true
        )
        ->render();
}

function stubQuiet() {
    return df\Launchpad::getDebugContext()->addStub(
            func_get_args(), 
            core\debug\StackCall::factory(1),
            false
        );
}

function dump($arg1) {
    return df\Launchpad::getDebugContext()->addDumpList(
            func_get_args(), 
            core\debug\StackCall::factory(1),
            false,
            true
        )
        ->render();
}

function dumpQuiet($arg1) {
    return df\Launchpad::getDebugContext()->addDumpList(
            func_get_args(), 
            core\debug\StackCall::factory(1),
            false,
            false
        );
}

function dumpDeep($arg1) {
    return df\Launchpad::getDebugContext()->addDumpList(
            func_get_args(), 
            core\debug\StackCall::factory(1),
            true,
            true
        )
        ->render();
}

function dumpDeepQuiet($arg1) {
    return df\Launchpad::getDebugContext()->addDumpList(
            func_get_args(), 
            core\debug\StackCall::factory(1),
            true,
            false
        )
        ->render();
}

function debug() {
    return df\Launchpad::getDebugContext();
}

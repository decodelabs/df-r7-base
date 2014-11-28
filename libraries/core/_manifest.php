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
class BadMethodCallException extends \BadMethodCallException {}
class ApplicationNotFoundException extends RuntimeException {}
class HelperNotFoundException extends RuntimeException {}

### Generic interfaces

// String provider
interface IStringProvider {
    public function toString();
    public function __toString();
}

interface IStringValueProvider {
    public function getStringValue($default='');
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

trait TStringValueProvider {

    protected function _getStringValue($value, $default='') {
        if($value instanceof IStringValueProvider) {
            $value = $value->getStringValue($default);
        }

        if($value === null) {
            $value = $default;
        }

        return (string)$value;
    }
}

interface IDescribable {
    public function getOutputDescription();
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
        return df\flex\json\Codec::encode($this->toArray());
    }
}

interface IExtendedArrayInterchange extends IExtendedArrayProvider, IArrayInterchange {

    public static function fromJsonString($json);
}

trait TExtendedArrayInterchange {

    use TExtendedArrayProvider;

    public static function fromJsonString($json) {
        return self::fromArray((array)df\flex\json\Codec($json));
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

interface IExporterValueMap extends IValueMap {
    public function export($key, $default=null);
}

trait TValueMap {

    public function importFrom(IValueMap $source, array $fields) {
        $values = [];
        $shouldImport = $this instanceof core\collection\ICollection;

        foreach($fields as $toField => $fromField) {
            if(!is_string($toField)) {
                $toField = $fromField;
            } 

            if($source instanceof IExporterValueMap) {
                $value = $source->export($fromField);
            } else {
                $value = $source->get($fromField);
            }

            if($shouldImport) {
                $values[$toField] = $value;
            } else {
                $this->set($toField, $value);
            }
        }

        if($shouldImport) {
            $this->import($values);
        }

        return $this;
    }
}

// Value container
interface IValueContainer {
    public function setValue($value);
    public function getValue($default=null);
}

interface IUserValueContainer extends IValueContainer, IStringValueProvider {
    public function hasValue();
}

trait TUserValueContainer {

    public function getStringValue($default='') {
        $value = $this->getValue();

        if($value !== null) {
            return (string)$value;
        }

        return $default;
    }

    public function hasValue() {
        return $this->getValue() !== null;
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
    
    protected $_errors = [];
    
    public function isValid() {
        return $this->hasErrors();
    }
    
    public function setErrors(array $errors) {
        $this->_errors = [];
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
        $this->_errors = [];
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
    
    protected $_attributes = [];
    
    public function setAttributes(array $attributes) {
        $this->_attributes = [];
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
    public function getArgs(array $add=[]);
    public function setArg($name, $value);
    public function getArg($name, $default=null);
    public function hasArg($name);
    public function removeArg($name);
}

trait TArgContainer {
    
    protected $_args = [];
    
    public function setArgs(array $args) {
        $this->_args = [];
        return $this->addArgs($args);
    }
    
    public function addArgs(array $args) {
        foreach($args as $key => $value){
            $this->setArg($key, $value);
        }
        
        return $this;
    }
    
    public function getArgs(array $add=[]) {
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
    public function lookupClass($path);
    
    public function findFile($path);
    public function getFileSearchPaths($path);
    public function lookupFileList($path, $extensions=null);
    public function lookupFileListRecursive($path, $extensions=null, $folderCheck=null);
    public function lookupClassList($path, $test=true);
    public function lookupFolderList($path);
    
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
    public function getLocalStoragePath();
    public function getSharedStoragePath();
    public function getStaticStoragePath();
    
    // Execute
    public function dispatch();
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



// Manager
interface IManager extends IRegistryObject {}

trait TManager {
    
    public static function getInstance() {
        $application = df\Launchpad::getApplication();
        
        if(!$output = $application->getRegistryObject(static::REGISTRY_PREFIX)) {
            $output = static::_getDefaultInstance();
            static::setInstance($output);
        }
        
        return $output;
    }

    public static function setInstance(IManager $manager) {
        return df\Launchpad::$application->setRegistryObject($manager);
    }

    protected static function _getDefaultInstance() {
        return new self();
    }
    
    protected function __construct() {}
    
    public function getRegistryObjectKey() {
        return static::REGISTRY_PREFIX;
    }

    public function onApplicationShutdown() {}
}




// Helpers
interface IHelperProvider {
    public function getHelper($name, $returnNull=false);
    public function __get($member);
}

trait THelperProvider {
    
    public function __get($key) {
        return $this->getHelper($key);
    }

    public function __call($method, array $args) {
        $helper = $this->getHelper($method);

        if(!is_callable($helper)) {
            throw new core\RuntimeException(
                'Helper '.$method.' is not callable'
            );
        }

        return call_user_func_array($helper, $args);
    }
    
    public function getHelper($name, $returnNull=false) {
        $name = lcfirst($name);

        if(isset($this->{$name})) {
            return $this->{$name};
        }
        
        $output = $this->_loadHelper($name);

        if(!$output && !$returnNull) {
            throw new HelperNotFoundException(
                'Helper '.$name.' could not be found'
            );
        }

        $this->{$name} = $output;

        return $output;
    }
    
    protected function _loadHelper($name) {
        return $this->_loadSharedHelper($name);
    }

    protected function _loadSharedHelper($name, $target=null) {
        $class = 'df\\plug\\'.ucfirst($name);
            
        if(!class_exists($class)) {
            return null;
        }

        $context = $this;
        $target = $target ? $target : $this;

        if(!$context instanceof IContext) {
            if(df\Launchpad::$application instanceof core\IContextAware) {
                $context = df\Launchpad::$application->getContext();
            } else {
                $context = new SharedContext();
            }
        }

        return new $class($context, $target);
    }
}

interface IHelper {}


// Context
interface IContext extends core\IHelperProvider {
    public function setRunMode($mode);
    public function getRunMode();

    // Locale
    public function setLocale($locale);
    public function getLocale();

    // Helpers
    public function loadRootHelper($name);
    public function throwError($code=500, $message='');
    public function findFile($path);
    
    public function getLogManager();    
    public function getI18nManager();
    public function getMeshManager();
    public function getSystemInfo();
    public function getUserManager();
    public function getTaskManager();

    public function getConfig($path);
    public function getCache($path);
}


trait TContext {

    use THelperProvider;

    public $application;
    protected $_locale;
    protected $_runMode;

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
            return core\i18n\Manager::getInstance()->getLocale(); 
        }
    }


// Helpers
    public function throwError($code=500, $message='') {
        throw new ContextException($message, (int)$code);
    }
    
    public function findFile($path) {
        return df\Launchpad::$loader->findFile($path);
    }

    public function getLogManager() {
        return core\log\Manager::getInstance();
    }

    public function getI18nManager() {
        return core\i18n\Manager::getInstance();
    }
    
    public function getMeshManager() {
        return df\mesh\Manager::getInstance();
    }
    
    public function getSystemInfo() {
        return df\halo\system\Base::getInstance();
    }
    
    public function getUserManager() {
        return df\user\Manager::getInstance();
    }

    public function getTaskManager() {
        return df\arch\task\Manager::getInstance();
    }


    public function getConfig($path) {
        if(!$class = df\Launchpad::$loader->lookupClass($path)) {
            throw new core\RuntimeException(
                'Config '.$path.' could not be found'
            );
        }
        
        return $class::getInstance();
    }

    public function getCache($path) {
        if(!$class = df\Launchpad::$loader->lookupClass($path)) {
            throw new core\RuntimeException(
                'Cache '.$path.' could not be found'
            );
        }
        
        return $class::getInstance();
    }

    public function loadRootHelper($name, $target=null) {
        switch($name) {
            case 'context':
                return $this;
            
            case 'application':
                return $this->application;
                
            case 'runMode':
                return $this->application->getRunMode();
                
            case 'locale':
                return $this->getLocale();
                

            case 'logs':
                return core\log\Manager::getInstance();
                
            case 'i18n':
                return core\i18n\Manager::getInstance();
                
            case 'mesh':
                return df\mesh\Manager::getInstance();
                
            case 'system':
                return df\halo\system\Base::getInstance();
                
            case 'user':
                return df\user\Manager::getInstance();

            case 'task':
                return df\arch\task\Manager::getInstance();

            default:
                return $this->_loadSharedHelper($name, $target);
        }
    }

    public function _($phrase, array $data=null, $plural=null, $locale=null) {
        if($locale === null) {
            $locale = $this->_locale;
        }

        $translator = core\i18n\translate\Handler::factory('core/Context', $locale);
        return $translator->_($phrase, $data, $plural);
    }
}

class SharedContext implements IContext {

    use TContext;

    public function __construct() {
        $this->application = df\Launchpad::$application;
    }

    protected function _loadHelper($name) {
        return $this->loadRootHelper($name);
    }
}

class ContextException extends \RuntimeException implements IException {}

interface IContextAware {
    public function getContext();
    public function hasContext();
}

trait TContextAware {
    
    public $context;
    
    public function getContext() {
        return $this->context;
    }

    public function hasContext() {
        return $this->context !== null;
    }
}



trait TContextProxy {
    
    use TContextAware;
    
    public function __call($method, $args) {
        if($this->context) {
            return call_user_func_array([$this->context, $method], $args);
        }
    }
    
    public function __get($key) {
        if(isset($this->{$key})) {
            return $this->{$key};
        }

        if(!$this->context) {
            return null;
        }

        return $this->{$key} = $this->context->__get($key);
    }

    public function _($phrase, array $data=null, $plural=null, $locale=null) {
        return $this->context->_($phrase, $data, $plural, $locale);
    }
}

interface ISharedHelper extends IHelper {}

trait TSharedHelper {

    public $context;

    public function __construct(IContext $context, $target) {
        $this->context = $context;
    }
}



// Config
interface IConfig extends IRegistryObject, IValueMap, \ArrayAccess {
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

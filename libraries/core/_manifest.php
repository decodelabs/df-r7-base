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
            core\debug()->exception($e);
            return '';
        } catch(\Error $e) {
            core\debug()->exception($e);
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
    public function has(...$keys);
    public function remove(...$keys);
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


// Dumpable
interface IDumpable {
    public function getDumpProperties();
}



// Loader
interface ILoader {
    public function loadClass($class);
    public function getClassSearchPaths($class);
    public function lookupClass($path);

    public function findFile($path);
    public function getFileSearchPaths($path);
    public function lookupFileList($path, $extensions=null);
    public function lookupFileListRecursive($path, $extensions=null, $folderCheck=null);
    public function lookupClassList($path, $test=true);
    public function lookupFolderList($path);
    public function lookupLibraryList();

    public function registerLocation($name, $path);
    public function unregisterLocation($name);
    public function getLocations();

    public function loadPackages(array $packages);
    public function getPackages();
    public function hasPackage($package);
    public function getPackage($package);

    public function shutdown();
}


// Package
interface IPackage {
    public function init();
}

class Package implements IPackage {

    const PRIORITY = 20;
    const DEPENDENCIES = [];

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

    public function init() {}
}


// Applications
interface IApplication {
    // Paths
    public static function getApplicationPath();
    public function getLocalStoragePath();
    public function getSharedStoragePath();

    // Execute
    public function dispatch();
    public function shutdown();
    public function getDispatchException();

    // Environment
    public function getEnvironmentId();
    public function getEnvironmentMode();
    public function hasEnvironmentMode($mode);
    public function isDevelopment();
    public function isTesting();
    public function isProduction();
    public function getRunMode();
    public function isDistributed();

    // Debug
    public function renderDebugContext(core\debug\IContext $context);

    // Members
    public function getName();
    public function getUniquePrefix();
    public function getPassKey();

    // Cache
    public function setRegistryObject(IRegistryObject $object);
    public function getRegistryObject($key);
    public function hasRegistryObject($key);
    public function removeRegistryObject($key);
    public function findRegistryObjects($beginningWith);
    public function getRegistryObjects();
}

interface IRegistryObject {
    public function getRegistryObjectKey();
}

interface IShutdownAware {
    public function onApplicationShutdown();
}

interface IDispatchAware {
    public function onApplicationDispatch(df\arch\node\INode $node);
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

        return $helper(...$args);
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
        $target = $target ?? $this;

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


// Translator
interface ITranslator {
    public function _($phrase='');
    public function translate(array $args);
}

trait TTranslator {

    public function _($phrase='') {
        return $this->translate(func_get_args());
    }

    public function translate(array $args) {
        return core\i18n\Manager::getInstance()->translate($args);
    }
}


// Context
interface IContext extends core\IHelperProvider, core\ITranslator {
    public function setRunMode($mode);
    public function getRunMode();

    // Locale
    public function setLocale($locale);
    public function getLocale();

    // Helpers
    public function loadRootHelper($name);
    public function throwError($code=500, $message='', array $data=null);
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

    use core\TTranslator;
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
    public function throwError($code=500, $message='', array $data=null) {
        throw new ContextException($message, (int)$code, $data);
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
        return df\arch\node\task\Manager::getInstance();
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
                return df\arch\node\task\Manager::getInstance();

            default:
                return $this->_loadSharedHelper($name, $target);
        }
    }

    public function translate(array $args) {
        return $this->i18n->translate($args);
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

class ContextException extends \RuntimeException implements IException, core\IDumpable {

    public $data;

    public function __construct($code, $message, array $data=null) {
        $this->data = $data;
        parent::__construct($code, $message);
    }

    public function getDumpProperties() {
        return $this->data;
    }
}

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
    use core\TTranslator;

    public function __call($method, $args) {
        if($this->context) {
            return $this->context->{$method}(...$args);
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

    public function translate(array $args) {
        return $this->context->i18n->translate($args);
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
    public function tidyConfigValues();
    public function reset();
}


include __DIR__.'/Loader.php';

if(!df\Launchpad::IS_COMPILED) {
    include __DIR__.'/DevLoader.php';
}



// Debug
function qDump(...$args) {
    while(ob_get_level()) {
        ob_end_clean();
    }

    if(count($args) == 1) {
        $args = array_shift($args);
    }

    if($count) {
        echo '<pre>'.print_r($args, true).'</pre>';
    }

    df\Launchpad::benchmark();
}

function stub(...$args) {
    return df\Launchpad::getDebugContext()->addStub(
            $args,
            core\debug\StackCall::factory(1),
            true
        )
        ->render();
}

function stubQuiet(...$args) {
    return df\Launchpad::getDebugContext()->addStub(
            $args,
            core\debug\StackCall::factory(1),
            false
        );
}

function dump(...$args) {
    return df\Launchpad::getDebugContext()->addDumpList(
            $args,
            core\debug\StackCall::factory(1),
            false,
            true
        )
        ->render();
}

function dumpQuiet(...$args) {
    return df\Launchpad::getDebugContext()->addDumpList(
            $args,
            core\debug\StackCall::factory(1),
            false,
            false
        );
}

function dumpDeep(...$args) {
    return df\Launchpad::getDebugContext()->addDumpList(
            $args,
            core\debug\StackCall::factory(1),
            true,
            true
        )
        ->render();
}

function dumpDeepQuiet(...$args) {
    return df\Launchpad::getDebugContext()->addDumpList(
            $args,
            core\debug\StackCall::factory(1),
            true,
            false
        )
        ->render();
}

function debug() {
    return df\Launchpad::getDebugContext();
}

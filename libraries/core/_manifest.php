<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core;

use df;
use df\core;

// Error
interface IError
{
    public function setKey(?string $key);
    public function getKey(): ?string;

    public function setData($data);
    public function getData();

    public function setHttpCode(?int $code);
    public function getHttpCode(): ?int;

    public function getStackCall(): core\debug\IStackCall;
    public function getStackTrace(): core\debug\IStackTrace;
}

interface ELogic extends IError
{
}
interface ERuntime extends IError
{
}
interface EDefinition extends ELogic
{
}
interface EImplementation extends ELogic
{
}
interface EUnsupported extends ELogic
{
}
interface EValue extends ERuntime
{
}
interface ESetup extends EValue
{
}
interface EArgument extends ELogic
{
}
interface ECall extends ELogic
{
}
interface ERecursion extends ELogic
{
}
interface EApi extends ERuntime
{
}
interface EDomain extends ELogic
{
}
interface EBounds extends ERuntime
{
}

interface EBadRequest extends ERuntime
{
} // 400
interface EUnauthorized extends ERuntime
{
} // 401
interface EForbidden extends EUnauthorized
{
} // 403
interface ENotFound extends ERuntime
{
} // 404
interface ENotImplemented extends EImplementation
{
} // 501
interface EServiceUnavailable extends ERuntime
{
} // 503

interface ENoContext extends ERuntime
{
}
interface EApplicationNotFound extends ENotFound
{
}
interface EHelperNotFound extends ENotFound
{
}


### Generic interfaces

// String provider
interface IStringProvider
{
    public function toString(): string;
    public function __toString(): string;
}

interface IStringValueProvider
{
    public function getStringValue($default=''): string;
}

trait TStringProvider
{
    public function __toString(): string
    {
        try {
            return $this->toString();
        } catch (\Throwable $e) {
            core\logException($e);
            core\debug()->exception($e);
            return '';
        }
    }
}

trait TStringValueProvider
{
    protected function _getStringValue($value, $default=''): string
    {
        if ($value instanceof IStringValueProvider) {
            $value = $value->getStringValue($default);
        }

        if ($value === null) {
            $value = $default;
        }

        return (string)$value;
    }
}

interface IDescribable
{
    public function getOutputDescription(): ?string;
}

// Array provider
interface IArrayProvider
{
    public function toArray(): array;
}

interface IArrayInterchange extends IArrayProvider
{
    public static function fromArray(array $array);
}


// Value map
interface IValueMap
{
    public function set($key, $value);
    public function get($key, $default=null);
    public function has(...$keys);
    public function remove(...$keys);
    public function importFrom($source, array $fields);
}

interface IExporterValueMap extends IValueMap
{
    public function export($key, $default=null);
}

trait TValueMap
{
    public function importFrom($source, array $fields)
    {
        $values = [];
        $shouldImport = $this instanceof core\collection\ICollection;

        foreach ($fields as $toField => $fromField) {
            if (!is_string($toField)) {
                $toField = $fromField;
            }

            if ($source instanceof IExporterValueMap) {
                $value = $source->export($fromField);
            } elseif ($source instanceof IValueMap) {
                $value = $source->get($fromField);
            } elseif (is_array($source)) {
                $value = $source[$fromField] ?? null;
            } else {
                core\stub($source);
            }

            if ($shouldImport) {
                $values[$toField] = $value;
            } else {
                $this->set($toField, $value);
            }
        }

        if ($shouldImport) {
            $this->import($values);
        }

        return $this;
    }
}

// Value container
interface IValueContainer
{
    public function setValue($value);
    public function getValue($default=null);
}

interface IUserValueContainer extends IValueContainer, IStringValueProvider
{
    public function hasValue(): bool;
}

trait TUserValueContainer
{
    public function getStringValue($default=''): string
    {
        $value = $this->getValue();

        if ($value !== null) {
            return (string)$value;
        }

        return (string)$default;
    }

    public function hasValue(): bool
    {
        return $this->getValue() !== null;
    }
}


// Dumpable
interface IDumpable
{
    public function getDumpProperties();
}



// Loader
interface ILoader
{
    public function loadClass(string $class): bool;
    public function getClassSearchPaths(string $class): ?array;
    public function lookupClass(string $path): ?string;

    public function findFile(string $path): ?string;
    public function getFileSearchPaths(string $path): array;
    public function lookupFileList(string $path, array $extensions=null): \Generator;
    public function lookupFileListRecursive(string $path, array $extensions=null, callable $folderCheck=null): \Generator;
    public function lookupClassList(string $path, bool $test=true): \Generator;
    public function lookupFolderList(string $path): \Generator;
    public function lookupLibraryList(): array;

    public function registerLocations(array $locations);
    public function registerLocation(string $name, string $path);
    public function unregisterLocation(string $name);
    public function getLocations(): array;

    public function initRootPackages(string $rootPath, string $appPath);
    public function loadPackages(array $packages);
    public function getPackages(): array;
    public function hasPackage(string $package): bool;
    public function getPackage(string $package): ?IPackage;

    public function shutdown(): void;
}


// Package
interface IPackage
{
    public function init();
}

class Package implements IPackage
{
    const PRIORITY = 20;
    const DEPENDENCIES = [];

    public $path;
    public $name;
    public $priority;

    public static function factory($name): IPackage
    {
        $class = 'df\\apex\\packages\\'.$name.'\\Package';

        if (!class_exists($class)) {
            throw core\Error::ERuntime('Package '.$name.' could not be found');
        }

        return new $class($name);
    }

    public function __construct($name, $priority=null, $path=null)
    {
        if ($path === null) {
            $ref = new \ReflectionObject($this);
            $path = dirname($ref->getFileName());
        }

        if (df\Launchpad::$isCompiled) {
            $this->path = df\Launchpad::$rootPath.'/apex/packages/'.$name;
        } else {
            $this->path = $path;
        }

        Glitch::registerPathAliases([
            $name => $this->path
        ]);

        $this->name = $name;

        if ($priority === null) {
            $priority = static::PRIORITY;
        }

        $this->priority = $priority;
    }

    public function init()
    {
    }
}


// Applications
interface IApp
{
    public static function factory(string $envId, string $path): IApp;

    // Paths
    public function getPath(): string;
    public function getLocalDataPath(): string;
    public function getSharedDataPath(): string;


    // Environment
    public function getEnvId(): string;
    public function getEnvMode(): string;

    public function isDevelopment(): bool;
    public function isTesting(): bool;
    public function isProduction(): bool;
    public function isDistributed(): bool;

    public function getUniquePrefix(): string;
    public function getPassKey(): string;

    // Details
    public function getName(): string;
    public function getStartTime(): float;
    public function getRunningTime(): float;


    // Runner
    public function startup(float $startTime=null): void;
    public function run(): void;
    public function shutdown(): void;
    public function getRunMode(): string;


    // Registry
    public function setRegistryObject(IRegistryObject $object);
    public function getRegistryObject(string $key): ?core\IRegistryObject;
    public function hasRegistryObject(string $key): bool;
    public function removeRegistryObject(string $key);
    public function findRegistryObjects(string $beginningWith): array;
    public function getRegistryObjects(): array;


    // Errors
    public static function handleError(int $errorNumber, string $errorMessage, string $fileName, int $lineNumber): void;
    public static function handleException(\Throwable $e): void;
}





interface IRunner
{
    // Execute
    public function dispatch(): void;
    public function getDispatchException(): ?\Throwable;

    // Debug
    public function renderDebugContext(core\debug\IContext $context): void;
}

interface IRegistryObject
{
    public function getRegistryObjectKey(): string;
}

interface IShutdownAware
{
    public function onAppShutdown(): void;
}

interface IDispatchAware
{
    public function onAppDispatch(df\arch\node\INode $node): void;
}



// Manager
interface IManager extends IRegistryObject
{
}

trait TManager
{
    public static function getInstance(): IManager
    {
        if (!$output = df\Launchpad::$app->getRegistryObject(static::REGISTRY_PREFIX)) {
            $output = static::_getDefaultInstance();
            static::setInstance($output);
        }

        return $output;
    }

    public static function setInstance(IManager $manager): void
    {
        df\Launchpad::$app->setRegistryObject($manager);
    }

    protected static function _getDefaultInstance(): IManager
    {
        return new self();
    }

    protected function __construct()
    {
    }

    public function getRegistryObjectKey(): string
    {
        return static::REGISTRY_PREFIX;
    }

    public function onAppShutdown(): void
    {
    }
}




// Helpers
interface IHelperProvider
{
    public function getHelper(string $name, bool $returnNull=false);
    public function __get($member);
}

trait THelperProvider
{
    public function __get($key)
    {
        return $this->getHelper($key);
    }

    public function __call($method, array $args)
    {
        $helper = $this->getHelper($method);

        if (!is_callable($helper)) {
            throw core\Error::{'ECall'}(
                'Helper '.$method.' is not callable'
            );
        }

        return $helper(...$args);
    }

    public function getHelper(string $name, bool $returnNull=false)
    {
        $name = lcfirst($name);

        if (isset($this->{$name})) {
            return $this->{$name};
        }

        $output = $this->_loadHelper($name);

        if (!$output && !$returnNull) {
            throw core\Error::EHelperNotFound(
                'Helper '.$name.' could not be found'
            );
        }

        $this->{$name} = $output;

        return $output;
    }

    protected function _loadHelper($name)
    {
        return $this->_loadSharedHelper($name);
    }

    protected function _loadSharedHelper(string $name, $target=null): ?IHelper
    {
        $class = 'df\\apex\\helpers\\'.ucfirst($name);

        if (!class_exists($class)) {
            $class = 'df\\plug\\'.ucfirst($name);

            if (!class_exists($class)) {
                return null;
            }
        }

        $context = $this;
        $target = $target ?? $this;

        if (!$context instanceof IContext) {
            if (df\Launchpad::$runner instanceof core\IContextAware) {
                $context = df\Launchpad::$runner->getContext();
            } else {
                $context = new SharedContext();
            }
        }

        return new $class($context, $target);
    }
}

interface IHelper
{
}


// Translator
interface ITranslator
{
    public function _($phrase=''): string;
    public function translate(array $args): string;
}

trait TTranslator
{
    public function _($phrase=''): string
    {
        return $this->translate(func_get_args());
    }

    public function translate(array $args): string
    {
        return core\i18n\Manager::getInstance()->translate($args);
    }
}


// Context
interface IContext extends core\IHelperProvider, core\ITranslator
{
    public function getRunMode(): string;

    // Locale
    public function setLocale($locale);
    public function getLocale();

    // Helpers
    public function loadRootHelper($name);
    public function findFile(string $path): ?string;

    public function getLogManager();
    public function getI18nManager();
    public function getMeshManager();
    public function getSystemInfo();
    public function getUserManager();
    public function getTaskManager();
}


trait TContext
{
    use core\TTranslator;
    use THelperProvider;

    public $runner;
    protected $_locale;

    public function getRunMode(): string
    {
        return df\Launchpad::$app->getRunMode();
    }


    // Locale
    public function setLocale($locale)
    {
        if ($locale === null) {
            $this->_locale = null;
        } else {
            $this->_locale = core\i18n\Locale::factory($locale);
        }

        return $this;
    }

    public function getLocale()
    {
        if ($this->_locale) {
            return $this->_locale;
        } else {
            return core\i18n\Manager::getInstance()->getLocale();
        }
    }


    // Helpers
    public function findFile(string $path): ?string
    {
        return df\Launchpad::$loader->findFile($path);
    }

    public function getLogManager()
    {
        return core\log\Manager::getInstance();
    }

    public function getI18nManager()
    {
        return core\i18n\Manager::getInstance();
    }

    public function getMeshManager()
    {
        return df\mesh\Manager::getInstance();
    }

    public function getSystemInfo()
    {
        return df\halo\system\Base::getInstance();
    }

    public function getUserManager()
    {
        return df\user\Manager::getInstance();
    }

    public function getTaskManager()
    {
        return df\arch\node\task\Manager::getInstance();
    }

    public function loadRootHelper($name, $target=null)
    {
        switch ($name) {
            case 'context':
                return $this;

            case 'app':
                return df\Launchpad::$app;

            case 'runner':
                return $this->runner;

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

    public function translate(array $args): string
    {
        return $this->i18n->translate($args);
    }
}

class SharedContext implements IContext
{
    use TContext;

    public function __construct()
    {
        $this->runner = df\Launchpad::$runner;
    }

    protected function _loadHelper($name)
    {
        return $this->loadRootHelper($name);
    }
}

interface IContextAware
{
    public function getContext();
    public function hasContext();
}

trait TContextAware
{
    public $context;

    public function getContext()
    {
        return $this->context;
    }

    public function hasContext()
    {
        return $this->context !== null;
    }
}



trait TContextProxy
{
    use TContextAware;
    use core\TTranslator;

    public function __call($method, $args)
    {
        if ($this->context) {
            return $this->context->{$method}(...$args);
        }
    }

    public function __get($key)
    {
        if (isset($this->{$key})) {
            return $this->{$key};
        }

        if (!$this->context) {
            return null;
        }

        return $this->{$key} = $this->context->__get($key);
    }

    public function translate(array $args): string
    {
        return $this->context->i18n->translate($args);
    }
}

interface ISharedHelper extends IHelper
{
}

trait TSharedHelper
{
    public $context;

    public function __construct(IContext $context, $target)
    {
        $this->context = $context;
    }
}



// Config
interface IConfig extends IRegistryObject, IValueMap, \ArrayAccess
{
    public function getDefaultValues(): array;
    public function getConfigId(): string;
    public function getConfigValues(): array;
    public function tidyConfigValues(): void;
    public function reset();
}


include __DIR__.'/loader/Base.php';

if (!df\Launchpad::$isCompiled) {
    include __DIR__.'/loader/Development.php';
}



// Debug
function stub(...$args)
{
    return df\Launchpad::getDebugContext()->addStub(
            $args,
            core\debug\StackCall::factory(1),
            true
        )
        ->render();
}

function stubQuiet(...$args)
{
    return df\Launchpad::getDebugContext()->addStub(
            $args,
            core\debug\StackCall::factory(1),
            false
        );
}

function dump(...$args)
{
    return df\Launchpad::getDebugContext()->addDumpList(
            $args,
            core\debug\StackCall::factory(1),
            false,
            true
        )
        ->render();
}

function dumpQuiet(...$args)
{
    return df\Launchpad::getDebugContext()->addDumpList(
            $args,
            core\debug\StackCall::factory(1),
            false,
            false
        );
}

function dumpDeep(...$args)
{
    return df\Launchpad::getDebugContext()->addDumpList(
            $args,
            core\debug\StackCall::factory(1),
            true,
            true
        )
        ->render();
}

function dumpDeepQuiet(...$args)
{
    return df\Launchpad::getDebugContext()->addDumpList(
            $args,
            core\debug\StackCall::factory(1),
            true,
            false
        )
        ->render();
}

function debug()
{
    return df\Launchpad::getDebugContext();
}


function logException(\Throwable $exception, $request=null)
{
    // Swallow?
    return core\log\Manager::getInstance()->logException($exception, $request);
}

function logDeprecated($message, $request=null)
{
    // Swallow?
    return core\log\Manager::getInstance()->logDeprecated($message, $request);
}

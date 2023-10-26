<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core;

use DecodeLabs\Exceptional;
use DecodeLabs\Genesis;

use DecodeLabs\Glitch;
use DecodeLabs\R7\Legacy;
use df;
use df\core;

// String provider
interface IStringProvider
{
    public function toString(): string;
    public function __toString(): string;
}

interface IStringValueProvider
{
    public function getStringValue($default = ''): string;
}

trait TStringProvider
{
    public function __toString(): string
    {
        try {
            return $this->toString();
        } catch (\Throwable $e) {
            core\logException($e);
            return '';
        }
    }
}

trait TStringValueProvider
{
    protected function _getStringValue($value, $default = ''): string
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
    public function get($key, $default = null);
    public function has(...$keys);
    public function remove(...$keys);
    public function importFrom($source, array $fields);
}

interface IExporterValueMap extends IValueMap
{
    public function export($key, $default = null);
}

trait TValueMap
{
    public function importFrom($source, array $fields)
    {
        $values = [];

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
                throw Exceptional::UnexpectedValue(
                    'Unsupported data source',
                    null,
                    $source
                );
            }

            if ($this instanceof core\collection\ICollection) {
                $values[$toField] = $value;
            } else {
                $this->set($toField, $value);
            }
        }

        if ($this instanceof core\collection\ICollection) {
            $this->import($values);
        }

        return $this;
    }
}

// Value container
interface IValueContainer
{
    public function setValue($value);
    public function getValue($default = null);
}

interface IUserValueContainer extends IValueContainer, IStringValueProvider
{
    public function hasValue(): bool;
}

trait TUserValueContainer
{
    public function getStringValue($default = ''): string
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


// Loader
interface ILoader
{
    public function loadClass(string $class): void;
    public function getClassSearchPaths(string $class): ?array;
    public function lookupClass(string $path): ?string;

    public function findFile(string $path): ?string;
    public function getFileSearchPaths(string $path): array;

    /**
     * @return \Generator<string, string>
     */
    public function lookupFileList(string $path, array $extensions = null): \Generator;

    /**
     * @return \Generator<string, string>
     */
    public function lookupFileListRecursive(string $path, array $extensions = null, callable $folderCheck = null): \Generator;

    /**
     * @return \Generator<string, string>
     * @return \Generator<string, class-string>
     */
    public function lookupClassList(string $path, bool $test = true): \Generator;

    /**
     * @return \Generator<string, string>
     */
    public function lookupFolderList(string $path): \Generator;
    public function lookupLibraryList(): array;

    public function initRootPackages(string $rootPath, string $appPath);
    public function loadPackages(array $packages);
    public function getPackages(): array;
    public function hasPackage(string $package): bool;
    public function getPackage(string $package): ?Package;
}

class Package
{
    public const PRIORITY = 20;
    public const DEPENDENCIES = [];

    public $path;
    public $name;
    public $priority;

    public static function factory($name): Package
    {
        $class = 'df\\apex\\packages\\' . $name . '\\Package';

        if (!class_exists($class)) {
            throw Exceptional::Runtime(
                'Package ' . $name . ' could not be found'
            );
        }

        return new $class($name);
    }

    public function __construct(
        string $name,
        ?int $priority = null,
        ?string $path = null
    ) {
        if ($path === null) {
            $ref = new \ReflectionObject($this);
            $path = dirname((string)$ref->getFileName());
        }

        if (Genesis::$build->isCompiled()) {
            $this->path = Genesis::$build->path . '/apex/packages/' . $name;
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

    public function init(): void
    {
    }
}


// Applications
interface IApp
{
    public static function factory(): IApp;


    // Runner
    public function shutdown(): void;


    // Registry
    public function setRegistryObject(IRegistryObject $object);
    public function getRegistryObject(string $key): ?core\IRegistryObject;
    public function hasRegistryObject(string $key): bool;
    public function removeRegistryObject(string $key);
    public function findRegistryObjects(string $beginningWith): array;
    public function getRegistryObjects(): array;
}





interface IRunner
{
    // Execute
    public function dispatch(): void;
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
    public static function getInstance(): static
    {
        if (!$output = Legacy::getRegistryObject(static::REGISTRY_PREFIX)) {
            $output = static::_getDefaultInstance();
            static::setInstance($output);
        }

        /** @var static $output */
        return $output;
    }

    public static function setInstance(IManager $manager): void
    {
        Legacy::setRegistryObject($manager);
    }

    protected static function _getDefaultInstance(): IManager
    {
        $class = get_called_class();
        $ref = new \ReflectionClass($class);

        if ($ref->isAbstract()) {
            throw Exceptional::Logic(
                'Unable to instantiate abstract Manager: ' . __CLASS__
            );
        }

        return new $class();
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
    public function getHelper(string $name, bool $returnNull = false);
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
            throw Exceptional::BadMethodCall(
                'Helper ' . $method . ' is not callable'
            );
        }

        return $helper(...$args);
    }

    public function getHelper(string $name, bool $returnNull = false)
    {
        $name = lcfirst($name);

        if (isset($this->{$name})) {
            return $this->{$name};
        }

        $output = $this->_loadHelper($name);

        if (!$output && !$returnNull) {
            throw Exceptional::{'HelperNotFound,NotFound'}(
                'Helper ' . $name . ' could not be found'
            );
        }

        $this->{$name} = $output;

        return $output;
    }

    protected function _loadHelper($name)
    {
        return $this->_loadSharedHelper($name);
    }

    protected function _loadSharedHelper(string $name, $target = null): ?IHelper
    {
        $class = 'df\\apex\\helpers\\' . ucfirst($name);

        if (!class_exists($class)) {
            $class = 'df\\plug\\' . ucfirst($name);

            if (!class_exists($class)) {
                return null;
            }
        }

        $context = $this;
        $target = $target ?? $this;

        if (!$context instanceof IContext) {
            $context = Legacy::getContext();
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
    public function _($phrase = '', $b = null, $c = null): string;
    public function translate(array $args): string;
}

trait TTranslator
{
    public function _($phrase = '', $b = null, $c = null): string
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
    // Locale
    public function setLocale($locale);
    public function getLocale();

    // Helpers
    public function loadRootHelper($name);
    public function findFile(string $path): ?string;

    public function getLogManager();
    public function getI18nManager();
    public function getMeshManager();
    public function getUserManager();
    public function getTaskManager();
}


trait TContext
{
    use core\TTranslator;
    use THelperProvider;

    protected $_locale;


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
        return Legacy::getLoader()->findFile($path);
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

    public function getUserManager()
    {
        return df\user\Manager::getInstance();
    }

    public function getTaskManager()
    {
        return df\arch\node\task\Manager::getInstance();
    }

    public function loadRootHelper($name, $target = null)
    {
        switch ($name) {
            case 'context':
                return $this;

            case 'app':
                return Legacy::app();

            case 'locale':
                return $this->getLocale();


            case 'logs':
                return core\log\Manager::getInstance();

            case 'i18n':
                return core\i18n\Manager::getInstance();

            case 'mesh':
                return df\mesh\Manager::getInstance();

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

    public function __construct(IContext $context)
    {
        $this->context = $context;
    }
}



// Debug
function logException(\Throwable $exception, $request = null)
{
    // Swallow?
    return core\log\Manager::getInstance()->logException($exception, $request);
}

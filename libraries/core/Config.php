<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core;

use df;
use df\core;

use DecodeLabs\Atlas;

use DecodeLabs\Glitch;
use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

abstract class Config implements IConfig, Inspectable
{
    use core\TValueMap;

    const REGISTRY_PREFIX = 'config://';

    const ID = null;
    const USE_ENVIRONMENT_ID_BY_DEFAULT = false;
    const STORE_IN_MEMORY = true;

    /**
     * @var df\core\collection\Tree
     */
    public $values;

    protected $_id;
    private $_filePath = null;

    // Loading
    public static function getInstance()
    {
        if (!static::ID) {
            throw Glitch::EDefinition('Invalid config id set for '.get_called_class());
        }

        return static::_factory(static::ID);
    }

    final protected static function _factory(?string $id)
    {
        $handlerClass = get_called_class();

        if (empty($id)) {
            throw Glitch::EImplementation('Invalid config id passed for '.$handlerClass);
        }

        if ($handlerClass::STORE_IN_MEMORY) {
            if (!$config = df\Launchpad::$app->getRegistryObject(self::REGISTRY_PREFIX.$id)) {
                df\Launchpad::$app->setRegistryObject(
                    $config = new $handlerClass($id)
                );
            }
        } else {
            $config = new $handlerClass($id);
        }

        return $config;
    }

    public static function clearLiveCache()
    {
        foreach (df\Launchpad::$app->findRegistryObjects('config://') as $config) {
            df\Launchpad::$app->removeRegistryObject($config->getRegistryObjectKey());
        }
    }


    // Construct
    public function __construct($id)
    {
        $parts = explode('/', $id);
        $parts[] = ucfirst((string)array_pop($parts));

        $this->_id = implode('/', $parts);

        if (null === ($values = $this->_loadValues())) {
            $this->reset();
            $this->save();
        } else {
            $this->values = new core\collection\Tree($values);
        }

        $this->_sanitizeValuesOnLoad();
    }


    // Values
    final public function getConfigId(): string
    {
        return $this->_id;
    }

    final public function getRegistryObjectKey(): string
    {
        return self::REGISTRY_PREFIX.$this->_id;
    }

    final public function getConfigValues(): array
    {
        return $this->values->toArray();
    }

    final public function save()
    {
        $this->_sanitizeValuesOnSave();
        $this->_saveValues();
        $this->onSave();

        return $this;
    }

    public function reset()
    {
        $this->values = new core\collection\Tree($this->getDefaultValues());
        $this->_sanitizeValuesOnCreate();

        return $this;
    }

    public function set($key, $value)
    {
        $this->values[$key] = $value;
        return $this;
    }

    public function get($key, $default=null)
    {
        if (isset($this->values[$key])) {
            return $this->values[$key];
        }

        return $default;
    }

    public function has(...$keys)
    {
        foreach ($keys as $key) {
            if (isset($this->values[$key])) {
                return true;
            }
        }

        return false;
    }

    public function remove(...$keys)
    {
        foreach ($keys as $key) {
            unset($this->values[$key]);
        }

        return $this;
    }

    public function offsetSet($key, $value)
    {
        return $this->set($key, $value);
    }

    public function offsetGet($key)
    {
        return $this->get($key);
    }

    public function offsetExists($key)
    {
        return $this->has($key);
    }

    public function offsetUnset($key)
    {
        return $this->remove($key);
    }


    protected function _sanitizeValuesOnCreate()
    {
        return null;
    }

    protected function _sanitizeValuesOnLoad()
    {
        return null;
    }

    protected function _sanitizeValuesOnSave()
    {
        return null;
    }

    protected function onSave()
    {
    }

    // IO
    private function _loadValues()
    {
        $parts = explode('/', $this->_id);
        $name = array_pop($parts);
        $envId = df\Launchpad::$app->envId;
        $basePath = $this->_getBasePath();

        if (!empty($parts)) {
            $basePath .= '/'.implode('/', $parts);
        }

        $basePath .= '/'.$name;
        $paths = [];

        $paths[] = $basePath.'#'.$envId.'.php';
        $paths[] = $basePath.'.php';
        $output = null;


        foreach ($paths as $path) {
            if (is_file($path)) {
                $this->_filePath = $path;
                $output = require $path;
                break;
            }
        }

        if ($output !== null && !is_array($output)) {
            $output = [];
        }

        return $output;
    }

    private function _saveValues()
    {
        if ($this->_filePath) {
            $savePath = $this->_filePath;
        } else {
            $envId = df\Launchpad::$app->envId;
            $parts = explode('/', $this->_id);
            $name = array_pop($parts);
            $basePath = $this->_getBasePath();

            if (!empty($parts)) {
                $basePath .= '/'.implode('/', $parts);
            }

            $corePath = $basePath.'/'.$name.'.php';
            $environmentPath = $basePath.'/'.$name.'#'.$envId.'.php';
            $isEnvironment = static::USE_ENVIRONMENT_ID_BY_DEFAULT || is_file($environmentPath);

            if ($isEnvironment) {
                $savePath = $environmentPath;
            } else {
                $savePath = $corePath;
            }
        }

        $values = $this->values->toArray();
        $content = '<?php'."\n".'return '.core\collection\Util::exportArray($values).';';
        Atlas::$fs->createFile($savePath, $content);
    }

    private function _getBasePath()
    {
        return df\Launchpad::$app->path.'/config';
    }


    public function tidyConfigValues(): void
    {
        $defaults = new core\collection\Tree($this->getDefaultValues());
        $current = new core\collection\Tree($this->getConfigValues());

        $current = $this->_tidyNode($defaults, $current);

        foreach ($current as $key => $node) {
            if (!$node->isEmpty() || $defaults->hasKey($key) || substr($key, 0, 1) == '!') {
                continue;
            }

            $current->remove($key);
        }

        $this->values = $current;
        $this->save();
    }

    private function _tidyNode(core\collection\ITree $defaults, core\collection\ITree $current)
    {
        $output = [];
        $value = $current->getValue();

        foreach ($defaults as $key => $node) {
            $output[$key] = null;

            if (substr($key, 0, 1) == '!') {
                continue;
            }

            if (!$current->hasKey($key)) {
                $output[$key] = $node;
            } else {
                $output[$key] = $this->_tidyNode($node, $current->{$key});
            }
        }

        foreach ($current as $key => $node) {
            if (!array_key_exists($key, $output)) {
                $output[$key] = $node;
            }
        }

        return new core\collection\Tree($output, $value);
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setProperty('*id', $this->_id)
            ->setValues($inspector->inspectList($this->getConfigValues()));
    }
}

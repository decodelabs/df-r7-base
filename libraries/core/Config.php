<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core;

use df;
use df\core;

abstract class Config implements IConfig, core\IDumpable {
    
    use core\TValueMap;

    const REGISTRY_PREFIX = 'config://';
    
    const ID = null;
    const USE_ENVIRONMENT_ID_BY_DEFAULT = false;
    const STORE_IN_MEMORY = true;
    
    public $values = [];
    
    protected $_id;
    private $_filePath = null;
    
// Loading
    public static function getInstance() {
        if(!static::ID) {
            throw new LogicException('Invalid config id set for '.get_called_class());
        }
        
        return static::_factory(static::ID);
    }
    
    final protected static function _factory($id) {
        $handlerClass = get_called_class();
        
        if(empty($id)) {
            throw new LogicException('Invalid config id passed for '.$handlerClass);
        }
        
        $application = df\Launchpad::getApplication();
        
        if($handlerClass::STORE_IN_MEMORY) {
            if(!$config = $application->getRegistryObject(self::REGISTRY_PREFIX.$id)) {
                $application->setRegistryObject(
                    $config = new $handlerClass($id)
                );
            }
        } else {
            $config = new $handlerClass($id);
        }
        
        return $config;
    }
    
    
// Construct
    public function __construct($id) {
        $parts = explode('/', $id);
        $parts[] = ucfirst(array_pop($parts));
        
        $this->_id = implode('/', $parts);
        
        if(null === ($values = $this->_loadValues())) {
            $this->reset();
            $this->save();
        } else {
            $this->values = new core\collection\Tree($values);
        }
        
        $this->_sanitizeValuesOnLoad();
    }

    public function onApplicationShutdown() {}
    
    
// Values
    final public function getConfigId() {
        return $this->_id;
    }
    
    final public function getRegistryObjectKey() {
        return self::REGISTRY_PREFIX.$this->_id;
    }
    
    final public function getConfigValues() {
        return $this->values->toArray();
    }
    
    final public function save() {
        $this->_sanitizeValuesOnSave();
        $this->_saveValues();
        $this->_onSave();
        
        return $this;
    }

    public function reset() {
        $values = $this->getDefaultValues();
            
        if(!is_array($values)) {
            throw new UnexpectedValueException(
                'Default values must be an array'
            );
        }

        $this->values = new core\collection\Tree($values);
        $this->_sanitizeValuesOnCreate();

        return $this;
    }

    public function set($key, $value) {
        $this->values[$key] = $value;
        return $this;
    }

    public function get($key, $default=null) {
        if(isset($this->values[$key])) {
            return $this->values[$key];
        }

        return $default;
    }

    public function has($key) {
        return isset($this->values[$key]);
    }

    public function remove($key) {
        unset($this->values[$key]);
    }

    public function offsetSet($key, $value) {
        return $this->set($key, $value);
    }

    public function offsetGet($key) {
        return $this->get($key);
    }

    public function offsetExists($key) {
        return $this->has($key);
    }

    public function offsetUnset($key) {
        return $this->remove($key);
    }


    protected function _sanitizeValuesOnCreate() {
        return null;
    }
    
    protected function _sanitizeValuesOnLoad() {
        return null;
    }
    
    protected function _sanitizeValuesOnSave() {
        return null;
    }

    protected function _onSave() {}
    
// IO
    private function _loadValues() {
        $parts = explode('/', $this->_id);
        $name = array_pop($parts);
        $environmentId = df\Launchpad::$application->getEnvironmentId();
        $environmentMode = df\Launchpad::$application->getEnvironmentMode();
        $basePath = $this->_getBasePath();
        
        if(!empty($parts)) {
            $basePath .= '/'.implode('/', $parts);
        }

        $basePath .= '/'.$name;
        $paths = [];

        if($environmentMode != 'production') {
            $paths[] = $basePath.'#'.$environmentId.'--'.$environmentMode.'.php';
        }

        $paths[] = $basePath.'#'.$environmentId.'.php';

        if($environmentMode != 'production') {
            $paths[] = $basePath.'--'.$environmentMode.'.php';
        }

        $paths[] = $basePath.'.php';
        $output = null;


        foreach($paths as $path) {
            if(is_file($path)) {
                $this->_filePath = $path;
                $output = require $path;
                break;
            }
        }
        
        if($output !== null && !is_array($output)) {
            $output = [];
        }
        
        return $output;
    }
    
    private function _saveValues() {
        if($this->_filePath) {
            $savePath = $this->_filePath;
        } else {
            $environmentId = df\Launchpad::$application->getEnvironmentId();
            $parts = explode('/', $this->_id);
            $name = array_pop($parts);
            $basePath = $this->_getBasePath();
            
            if(!empty($parts)) {
                $basePath .= '/'.implode('/', $parts);
            }
            
            $corePath = $basePath.'/'.$name.'.php';
            $environmentPath = $basePath.'/'.$name.'#'.$environmentId.'.php';
            $isEnvironment = static::USE_ENVIRONMENT_ID_BY_DEFAULT || is_file($environmentPath);
            
            if($isEnvironment) {
                $savePath = $environmentPath;
            } else {
                $savePath = $corePath;
            }
        }

        core\io\Util::ensureDirExists(dirname($savePath));
        
        $values = $this->values->toArray();
        $content = '<?php'."\n".'return '.core\collection\Util::exportArray($values).';';
        file_put_contents($savePath, $content, LOCK_EX);
    }
    
    private function _getBasePath() {
        return df\Launchpad::$application->getApplicationPath().'/config';
    }


    public function tidyConfigValues() {
        $defaults = new core\collection\Tree($this->getDefaultValues());
        $current = new core\collection\Tree($this->getConfigValues());

        $current = $this->_tidyNode($defaults, $current);

        foreach($current as $key => $node) {
            if(!$node->isEmpty() || $defaults->hasKey($key) || substr($key, 0, 1) == '!') {
                continue;
            }

            $current->remove($key);
        }

        $this->values = $current;
        $this->save();
    }

    private function _tidyNode(core\collection\ITree $defaults, core\collection\ITree $current) {
        $output = [];
        $value = $current->getValue();

        foreach($defaults as $key => $node) {
            $output[$key] = null;

            if(substr($key, 0, 1) == '!') {
                continue;
            }

            if(!$current->hasKey($key)) {
                $output[$key] = $node;
            } else {
                $output[$key] = $this->_tidyNode($node, $current->{$key});
            }
        }

        foreach($current as $key => $node) {
            if(!array_key_exists($key, $output)) {
                $output[$key] = $node;
            }
        }

        return new core\collection\Tree($output, $value);
    }
    

// Dump
    public function getDumpProperties() {
        return array_merge(
            ['configId' => new core\debug\dumper\Property('configId', $this->_id, 'protected')],
            $this->getConfigValues()
        );
    }
}

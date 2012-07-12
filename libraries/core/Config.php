<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core;

use df;
use df\core;

abstract class Config implements IConfig, core\IDumpable {
    
    const REGISTRY_PREFIX = 'config://';
    
    const ID = null;
    const IS_DISTRIBUTED = true;
    const USE_ENVIRONMENT_ID_BY_DEFAULT = false;
    const STORE_IN_MEMORY = true;
    
    protected $_id;
    protected $_values = array();
    
    private $_application;
    
// Loading
    public static function getInstance(IApplication $application=null) {
        if(!static::ID) {
            throw new LogicException('Invalid config id set for '.get_called_class());
        }
        
        return static::_factory($application, static::ID);
    }
    
    final protected static function _factory(IApplication $application=null, $id) {
        $handlerClass = get_called_class();
        
        if(empty($id)) {
            throw new LogicException('Invalid config id passed for '.$handlerClass);
        }
        
        if(!$application) {
            $application = df\Launchpad::getActiveApplication();
        }
        
        if($handlerClass::STORE_IN_MEMORY) {
            if(!$config = $application->_getCacheObject(self::REGISTRY_PREFIX.$id)) {
                $application->_setCacheObject(
                    $config = new $handlerClass($application, $id)
                );
            }
        } else {
            $config = new $handlerClass($application, $id);
        }
        
        return $config;
    }
    
    
// Construct
    public function __construct(IApplication $application, $id) {
        $this->_application = $application;
        
        $parts = explode('/', $id);
        $parts[] = ucfirst(array_pop($parts));
        
        $this->_id = implode('/', $parts);
        
        if(null === ($values = $this->_loadValues())) {
            $values = $this->getDefaultValues();
            
            if(!is_array($values)) {
                throw new UnexpectedValueException(
                    'Default values must be an array'
                );
            }

            $this->_values = $values;
            $this->_sanitizeValuesOnCreate();
            
            
            $this->save();
        } else {
            $this->_values = $values;
        }
        
        $this->_sanitizeValuesOnLoad();
    }
    
// Application aware
    public function getApplication() {
        return $this->_application;
    }
    
// Values
    final public function getConfigId() {
        return $this->_id;
    }
    
    final public function getRegistryObjectKey() {
        return self::REGISTRY_PREFIX.$this->_id;
    }
    
    final public function getConfigValues() {
        return $this->_values;
    }
    
    final public function isConfigDistributed() {
        return static::IS_DISTRIBUTED;
    }
    
    final public function save() {
        $this->_sanitizeValuesOnSave();
        $this->_saveValues();
        
        return $this;
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
    
// IO
    private function _loadValues() {
        $parts = explode('/', $this->_id);
        $name = array_pop($parts);
        $environmentId = $this->_application->getEnvironmentId();
        $basePath = $this->_getBasePath();
        
        if(!empty($parts)) {
            $basePath .= '/'.implode('/', $parts);
        }
        
        $corePath = $basePath.'/'.$name.'.php';
        $environmentPath = $basePath.'/'.$name.'#'.$environmentId.'.php';
        
        $output = null;
        
        if(is_file($environmentPath)) {
            $output = require $environmentPath;
        } else if(is_file($corePath)) {
            $output = require $corePath;
        }
        
        if($output !== null && !is_array($output)) {
            $output = array();
        }
        
        return $output;
    }
    
    private function _saveValues() {
        $environmentId = $this->_application->getEnvironmentId();
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
        
        if(!is_dir(dirname($savePath))) {
            mkdir(dirname($savePath), 0777, true);
        }
        
        $content = '<?php'."\n".'return '.$this->_exportArray($this->_values).';';
        
        // TODO: locking
        file_put_contents($savePath, $content);
    }
    
    private function _getBasePath() {
        if(static::IS_DISTRIBUTED) {
            return $this->_application->getSharedStaticStoragePath().'/config';
        } else {
            return $this->_application->getLocalStaticStoragePath().'/config';
        }
    }
    
    private function _exportArray(array $values, $level=1) {
        $output = '['."\n";
        
        $i = 0;
        $count = count($values);
        $isNumericIndex = true;
        
        foreach($values as $key => $val) {
            if($key !== $i++) {
                $isNumericIndex = false;
                break;
            }
        }
        
        $i = 0;
        
        foreach($values as $key => $val) {
            $output .= str_repeat('    ', $level);
            
            if(!$isNumericIndex) {
                $output .= '\''.addslashes($key).'\' => ';
            }
            
            if(is_object($val) || is_null($val)) {
                $output .= 'null';    
            } else if(is_array($val)) {
                $output .= $this->_exportArray($val, $level + 1);
            } else if(is_int($val) || is_float($val)) {
                $output .= $val; 
            } else if(is_bool($val)) {
                if($val) {
                    $output .= 'true';
                } else {
                    $output .= 'false';
                }
            } else {
                $output .= '\''.addslashes($val).'\'';    
            }
            
            if(++$i < $count) {
                $output .= ',';    
            }
            
            $output .= "\n";
        }
        
        $output .= str_repeat('    ', $level - 1).']';
        
        return $output;
    }


// Dump
    public function getDumpProperties() {
        return array_merge(
            ['configId' => new core\debug\dumper\Property('configId', $this->_id, 'protected')],
            $this->_values
        );
    }
}

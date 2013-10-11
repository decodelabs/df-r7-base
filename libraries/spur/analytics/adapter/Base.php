<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\analytics\adapter;

use df;
use df\core;
use df\spur;
    
abstract class Base implements spur\analytics\IAdapter {

    protected $_options = [];
    protected $_defaultUserAttributes = [];

    public static function loadAllFromConfig($enabled=true) {
        $config = spur\analytics\Config::getInstance();
        $output = [];

        foreach($config->getAdapters() as $name => $info) {
            if($enabled && !$info->get('enabled', true)) {
                continue;
            }

            try {
                $adapter = self::factory(
                    $name,
                    $info->options->toArray(),
                    $info->userAttributes->toArray()
                );
            } catch(spur\analytics\IException $e) {
                continue;
            }

            $output[$adapter->getName()] = $adapter;
        }

        return $output;
    }

    public static function loadFromConfig($name) {
        $config = spur\analytics\Config::getInstance();

        if(null === ($info = $config->getAdapter($name))) {
            throw new spur\analytics\RuntimeException('Adapter '.$name.' could not be found');
        }

        $output = self::factory(
            $name,
            $info->options->toArray(),
            $info->userProperties->toArray()
        );

        return $output;
    }

    public static function listAll() {
        foreach(df\Launchpad::$loader->lookupFileList('spur/analytics/adapter', ['php']) as $path) {
            $name = substr($baseName, 0, -4);
            
            if($name === 'Base' || $name === '_manifest') {
                continue;
            }

            try {
                $adapter = self::factory($name);
            } catch(spur\analytics\IException $e) {
                continue;
            }

            $output[$adapter->getName()] = $adapter->getName();
        }

        asort($output);
        return $output;
    }

    public static function factory($name, array $options=array(), array $defaultUserAttributes=array()) {
        $class = 'df\\spur\\analytics\\adapter\\'.ucfirst($name);

        if(!class_exists($class)) {
            throw new spur\analytics\RuntimeException('Adapter '.$name.' could not be found');
        }

        return new $class($options, $defaultUserAttributes);
    }

    public function __construct(array $options=array(), array $defaultUserAttributes=array()) {
        $this->setOptions($options);
        $this->setDefaultUserAttributes($defaultUserAttributes);
    }

    public function getName() {
        $parts = explode('\\', get_class($this));
        return array_pop($parts);
    }


// Options
    public function setOptions(array $options) {
        foreach($options as $key => $val) {
            $this->setOption($key, $val);
        }

        return $this;
    }

    public function setOption($key, $val) {
        $this->_options[$key] = $val;
        return $this;
    }

    public function getOption($key) {
        if(isset($this->_options[$key])) {
            return $this->_options[$key];
        }

        return null;
    }

    public function getOptions() {
        return $this->_options;
    }

    public function getRequiredOptions() {
        return array_keys($this->_options);
    }

    public function clearOptions() {
        $this->_options = [];
        return $this;
    }

    public function validateOptions(core\collection\IInputTree $values, $update=false) {
        $this->_validateOptions($values);

        if($update && $values->isValid()) {
            $this->setOptions($values->toArray());
        }

        return $this;
    }

    protected function _validateOptions(core\collection\IInputTree $values) {}


    public function setDefaultUserAttributes(array $attributes) {
        $available = spur\analytics\Handler::getAvailableUserAttributes();
        $this->_defaultUserAttributes = [];

        foreach($attributes as $attribute) {
            if(in_array($attribute, $available)) {
                $this->_defaultUserAttributes[] = $attribute;
            }
        }

        return $this;
    }

    public function getDefaultUserAttributes() {
        return $this->_defaultUserAttributes;
    }
}
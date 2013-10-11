<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\analytics;

use df;
use df\core;
use df\spur;
    
class Config extends core\Config {

    const ID = 'Analytics';
    const USE_ENVIRONMENT_ID_BY_DEFAULT = true;
    const USE_TREE = true;

    public function getDefaultValues() {
        return [];
    }

    public function isEnabled() {
        foreach($this->_values as $adapter) {
            if(!$adapter->get('enabled', true)) {
                continue;
            }

            return true;
        }

        return false;
    }

    public function getAdapters() {
        return clone $this->_values;
    }

    public function getEnabledAdapters() {
        $output = [];

        foreach($this->_values as $name => $adapter) {
            if($adapter->get('enabled', true)) {
                $output[$name] = clone $adapter;
            }
        }

        return $output;
    }

    public function setAdapter($name, $isEnabled=true, array $options=array(), array $defaultUserAttributes=array()) {
        if($name instanceof IAdapter) {
            $options = $name->getOptions();
            $defaultUserAttributes = $name->getDefaultUserAttributes();
            $name = $name->getName();
        }

        $this->_values->{$name} = [
            'enabled' => $isEnabled,
            'options' => $options,
            'userAttributes' => $defaultUserAttributes
        ];

        return $this;
    }

    public function getAdapter($name) {
        if(!isset($this->_values->{$name})) {
            return null;
        }

        return $this->_values->{$name};
    }

    public function isAdapterEnabled($name, $flag=null) {
        if($flag !== null) {
            if(isset($this->_values->{$name})) {
                $this->_values->{$name}->enabled = (bool)$flag;
            }

            return $this;
        }

        if(!isset($this->_values->{$name})) {
            return false;
        }

        return $this->_values->{$name}->get('enabled', true);
    }

    public function removeAdapter($name) {
        if($name instanceof IAdapter) {
            $name = $name->getName();
        }

        unset($this->_values->{$name});
        return $this;
    }
}
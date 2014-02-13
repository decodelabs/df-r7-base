<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\user\authentication;

use df;
use df\core;
use df\user;

class Config extends core\Config {
    
    const ID = 'Authentication';
    const USE_ENVIRONMENT_ID_BY_DEFAULT = true;
    const USE_TREE = true;
    
    public function getDefaultValues() {
        $output = [];

        foreach(df\Launchpad::$loader->lookupFileList('user/authentication/adapter', ['php']) as $baseName => $path) {
            $name = substr($baseName, 0, -4);
            
            if($name === 'Base' || $name === '_manifest') {
                continue;
            }
            
            $class = 'df\\user\\authentication\\adapter\\'.$name;

            if(!class_exists($class)) {
                continue;
            }

            $output[$name] = $class::getDefaultConfigValues();
            
            if(!isset($output[$name]['enabled'])) {
                $output[$name]['enabled'] = false;
            }
        }
            
        return $output;
    }

    public function isAdapterEnabled($adapter, $flag=null) {
        if($adapter instanceof IAdapter) {
            $adapter = $adapter->getName();
        }

        if($flag !== null) {
            $this->values->{$adapter}->enabled = (bool)$flag;
            return $this;
        } else {
            return (bool)$this->values->{$adapter}['enabled'];
        }
    }

    public function getEnabledAdapters() {
        $output = [];

        foreach($this->values as $name => $data) {
            if(!$data['enabled']) {
                continue;
            }

            $output[$name] = $data;
        }

        return $output;
    }

    public function getFirstEnabledAdapter() {
        foreach($this->values as $name => $data) {
            if($data['enabled']) {
                return $name;
            }
        }

        return null;
    }

    public function setOptionsFor($adapter, $options) {
        if($adapter instanceof IAdapter) {
            $adapter = $adapter->getName();
        }

        if(!isset($options['enabled'])) {
            $options['enabled'] = false;
        }

        $this->values->{$adapter} = $options;
        return $this;
    }

    public function getOptionsFor($adapter) {
        if($adapter instanceof IAdapter) {
            $adapter = $adapter->getName();
        }

        return $this->values->{$adapter};
    }
}
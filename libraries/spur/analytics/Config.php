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

    public function getDefaultValues(): array {
        $output = [];

        foreach(spur\analytics\adapter\Base::loadAll() as $name => $adapter) {
            if($adapter instanceof ILegacyAdapter) {
                continue;
            }

            $set = [
                'enabled' => false,
                'options' => $adapter->getOptions()
            ];

            $attrs = $adapter->getDefaultUserAttributes();

            if(!empty($attrs)) {
                $set['userAttributes'] = $attrs;
            }

            $output[lcfirst($name)] = $set;
        }

        return $output;
    }

    public function isEnabled() {
        foreach($this->values as $adapter) {
            if(!$adapter->get('enabled', true)) {
                continue;
            }

            return true;
        }

        return false;
    }

    public function getAdapters() {
        return clone $this->values;
    }

    public function getEnabledAdapters() {
        $output = [];

        foreach($this->values as $name => $adapter) {
            if($adapter->get('enabled', true)) {
                $output[$name] = clone $adapter;
            }
        }

        return $output;
    }

    public function setAdapter($name, $isEnabled=true, array $options=[], array $defaultUserAttributes=[]) {
        if($name instanceof IAdapter) {
            $options = $name->getOptions();
            $defaultUserAttributes = $name->getDefaultUserAttributes();
            $name = $name->getName();
        }

        $this->values->{$name} = [
            'enabled' => $isEnabled,
            'options' => $options,
            'userAttributes' => $defaultUserAttributes
        ];

        return $this;
    }

    public function getAdapter($name) {
        if(!isset($this->values->{$name})) {
            return null;
        }

        return $this->values->{$name};
    }

    public function isAdapterEnabled($name, bool $flag=null) {
        if($flag !== null) {
            if(isset($this->values->{$name})) {
                $this->values->{$name}->enabled = $flag;
            }

            return $this;
        }

        if(!isset($this->values->{$name})) {
            return false;
        }

        return $this->values->{$name}->get('enabled', true);
    }

    public function removeAdapter($name) {
        if($name instanceof IAdapter) {
            $name = $name->getName();
        }

        unset($this->values->{$name});
        return $this;
    }
}
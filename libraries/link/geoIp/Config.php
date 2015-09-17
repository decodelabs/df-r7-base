<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\geoIp;

use df;
use df\core;
use df\link;

class Config extends core\Config {
    
    const ID = 'GeoIp';

    public function getDefaultValues() {
        return [
            'enabled' => false,
            'defaultAdapter' => 'MaxMindDb',
            'adapters' => [
                'MaxMindDb' => [
                    'file' => null
                ],
                'MaxMindWeb' => [
                    'key' => null
                ]
            ]
        ];
    }

    public function isEnabled($flag=null) {
        if($flag !== null) {
            $this->values->enabled = (bool)$flag;
            return $this;
        }

        return (bool)$this->values['enabled'];
    }

    public function getDefaultAdapter() {
        return $this->values['defaultAdapter'];
    }

    public function setDefaultAdapter($adapter) {
        if($adapter instanceof IAdapter) {
            $adapter = $adapter->getName();
        }

        $this->values->defaultAdapter = $adapter;
        return $this;
    }

    public function getSettingsFor($adapter) {
        if($adapter instanceof IAdapter) {
            $adapter = $adapter->getName();
        }

        return $this->values->adapters->{$adapter};
    }
}
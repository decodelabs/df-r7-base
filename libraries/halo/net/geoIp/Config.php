<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\net\geoIp;

use df;
use df\core;
use df\halo;

class Config extends core\Config {
    
    const ID = 'GeoIp';
    const USE_TREE = true;

    public function getDefaultValues() {
        return [
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

    public function getDefaultAdapter() {
        return $this->values['defaultAdapter'];
    }

    public function getSettingsFor($adapter) {
        if($adapter instanceof IAdapter) {
            $adapter = $adapter->getName();
        }

        return $this->values->adapters->{$adapter};
    }
}
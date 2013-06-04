<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis;

use df;
use df\core;
use df\axis;

class ConnectionConfig extends core\Config {
    
    const ID = 'DataConnections';
    const USE_ENVIRONMENT_ID_BY_DEFAULT = true;
    
    public function getDefaultValues() {
        return [
            'connections' => [
                'master' => [
                    'adapter' => 'Rdbms',
                    'dsn' => 'mysql://user:pass@localhost/database'
                ],
                'search' => [
                    'adapter' => 'Elastic'
                ]
            ],
            'units' => [
                'default' => 'master',
                '@search' => 'search'
            ]
        ];
    }
    
    public function getAdapterIdFor(IUnit $unit) {
        return $this->getSettingsFor($unit)->get('adapter');
    }
    
    public function getSettingsFor(IUnit $unit) {
        $connectionId = $this->getconnectionIdFor($unit);
        
        if(!isset($this->_values['connections'][$connectionId])) {
            throw new RuntimeException(
                'There are no connections for '.$unit->getUnitId().', with connection id '.$connectionId
            );
        }
        
        return new core\collection\Tree($this->_values['connections'][$connectionId]);
    }
    
    public function getConnectionIdFor(IUnit $unit) {
        $unitId = $unit->getUnitId();
        
        if(!isset($this->_values['units'][$unitId])) {
            $originalId = $unitId;
            
            $parts = explode(axis\IUnit::ID_SEPARATOR, $unitId);
            $unitId = array_shift($parts);
            
            if(!isset($this->_values['units'][$unitId])) {
                try {
                    $unitId = '@'.$unit->getUnitType();
                } catch(\Exception $e) {
                    $unitId = null;
                }
                
                if($unitId === null || !isset($this->_values['units'][$unitId])) {
                    $unitId = 'default';
                
                    if(!isset($this->_values['units'][$unitId])) {
                        throw new RuntimeException(
                            'There are no connections matching '.$originalId
                        );
                    }
                }
            }
        }
        
        return (string)$this->_values['units'][$unitId];
    }
}

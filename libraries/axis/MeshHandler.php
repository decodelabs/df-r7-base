<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis;

use df;
use df\core;
use df\axis;
use df\mesh;

class MeshHandler implements mesh\IEntityHandler {
    
    public function fetchEntity(mesh\IManager $manager, array $node) {
        if($node['type'] == 'Model') {
            $clusterId = null;

            if(!empty($node['location'])) {
                $clusterId = array_shift($node['location']);
            }

            return axis\Model::factory($node['id'], $clusterId);
        }


        if(empty($node['location'])) {
            switch($node['type']) {
                case 'Unit':
                    return axis\Model::loadUnitFromId($node['id']);
                    
                case 'Schema':
                    $unit = axis\Model::loadUnitFromId($node['id']);
                    
                    if(!$unit instanceof axis\ISchemaBasedStorageUnit) {
                        throw new axis\LogicException(
                            'Model unit '.$unit->getUnitName().' does not provide a schema'
                        );
                    }
                    
                    return $unit->getUnitSchema();
            }
        }
        
        $location = $node['location'];
        $clusterId = null;

        if(count($location) > 1) {
            $clusterId = array_shift($location);
        }

        $model = axis\Model::factory(array_shift($location), $clusterId);
        
        switch($node['type']) {
            case 'Unit':
                return $model->getUnit($node['id']);
                
            case 'Schema':
                $unit = $model->getUnit($node['id']);
                
                if(!$unit instanceof axis\ISchemaBasedStorageUnit) {
                    throw new axis\LogicException(
                        'Model unit '.$unit->getUnitName().' does not provide a schema'
                    );
                }
                
                return $unit->getUnitSchema();
                
            default:
                $unit = $model->getUnit($node['type']);
                
                if($node['id'] === null) {
                    return $unit;
                }
                
                return $unit->fetchByPrimary($node['id']);
        }
    }
}

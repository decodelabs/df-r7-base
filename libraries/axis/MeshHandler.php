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
        if(empty($node['location'])) {
            switch($node['type']) {
                case 'Model':
                    return axis\Model::factory($node['id'], $manager->getApplication());
                    
                case 'Unit':
                    return axis\Model::loadUnitFromId($node['id'], $manager->getApplication());
                    
                case 'Schema':
                    $unit = axis\Model::loadUnitFromId($node['id'], $manager->getApplication());
                    
                    if(!$unit instanceof axis\ISchemaBasedStorageUnit) {
                        throw new axis\LogicException(
                            'Model unit '.$unit->getUnitName().' does not provide a schema'
                        );
                    }
                    
                    return $unit->getUnitSchema();
            }
        }
        
        $location = $node['location'];
        $model = axis\Model::factory(array_shift($location), $manager->getApplication());
        
        if(!empty($location)) {
            $unit = $model->getUnit(array_shift($location));
            
            if($node['type'] == 'Unit') {
                return $unit;
            }
            
            if(!$unit instanceof mesh\entity\IParentEntity) {
                return null;
            }
            
            return $unit->fetchSubEntity($manager, $node);
        }
        
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

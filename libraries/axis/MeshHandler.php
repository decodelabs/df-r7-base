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
    
    public function fetchEntity(mesh\IManager $manager, mesh\entity\ILocatorNode $node) {
        if(!$node->hasLocation()) {
            switch($node->getType()) {
                case 'Model':
                    return axis\Model::factory($node->getId(), $manager->getApplication());
                    
                case 'Unit':
                    return axis\Model::loadUnitFromId($node->getId(), $manager->getApplication());
                    
                case 'Schema':
                    $unit = axis\Model::loadUnitFromId($node->getId(), $manager->getApplication());
                    
                    if(!$unit instanceof axis\ISchemaBasedStorageUnit) {
                        throw new axis\LogicException(
                            'Model unit '.$unit->getUnitName().' does not provide a schema'
                        );
                    }
                    
                    return $unit->getUnitSchema();
            }
        }
        
        $location = $node->getLocationArray();
        $model = axis\Model::factory(array_shift($location), $manager->getApplication());
        
        if(!empty($location)) {
            $unit = $model->getUnit(array_shift($location));
            
            if($node->getType() == 'Unit') {
                return $unit;
            }
            
            if(!$unit instanceof mesh\entity\IParentEntity) {
                return null;
            }
            
            return $unit->fetchSubEntity($manager, $node);
        }
        
        $id = $node->getId();
        
        switch($node->getType()) {
            case 'Unit':
                return $model->getUnit($id);
                
            case 'Schema':
                $unit = $model->getUnit($id);
                
                if(!$unit instanceof axis\ISchemaBasedStorageUnit) {
                    throw new axis\LogicException(
                        'Model unit '.$unit->getUnitName().' does not provide a schema'
                    );
                }
                
                return $unit->getUnitSchema();
                
            default:
                $unit = $model->getUnit($node->getType());
                
                if($id === null) {
                    return $unit;
                }
                
                return $unit->fetchByPrimary($id);
        }
    }
}

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

use DecodeLabs\Exceptional;

class MeshHandler implements mesh\IEntityHandler
{
    public function fetchEntity(mesh\IManager $manager, array $node)
    {
        if ($node['type'] == 'Model') {
            return axis\Model::factory($node['id']);
        }


        if (empty($node['location'])) {
            switch ($node['type']) {
                case 'Unit':
                    return axis\Model::loadUnitFromId($node['id']);

                case 'Schema':
                    $unit = axis\Model::loadUnitFromId($node['id']);

                    if (!$unit instanceof axis\ISchemaBasedStorageUnit) {
                        throw Exceptional::Logic(
                            'Model unit '.$unit->getUnitName().' does not provide a schema'
                        );
                    }

                    return $unit->getUnitSchema();
            }
        }

        $location = $node['location'];
        $model = axis\Model::factory(array_shift($location));
        $unit = $model->getUnit($node['type']);

        if ($node['id'] === null) {
            return $unit;
        }

        return $unit->fetchByPrimary($node['id']);
    }
}

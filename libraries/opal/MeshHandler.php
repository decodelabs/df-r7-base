<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal;

use df\mesh;
use df\opal;

class MeshHandler implements mesh\IEntityHandler
{
    public function fetchEntity(mesh\IManager $manager, array $node)
    {
        $location = implode('/', $node['location']);

        switch ($location) {
            case 'rdbms':
                return opal\rdbms\adapter\Base::factory($node['type'] . '://' . $node['id']);
        }
    }
}

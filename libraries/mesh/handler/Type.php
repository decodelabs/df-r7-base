<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\mesh\handler;

use df;
use df\core;
use df\mesh;

class Type implements mesh\IEntityHandler {
    
    public function fetchEntity(mesh\IManager $manager, array $node) {
        $parts = $node['location'];
        $parts[] = $node['type'];
        return new core\lang\TypeRef(implode('/', $parts));
    }
}
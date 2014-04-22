<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\policy;

use df;
use df\core;
use df\opal;
use df\mesh;

class Handler implements core\policy\IEntityHandler {
    
    public function fetchEntity(core\policy\IManager $manager, mesh\entity\ILocatorNode $node) {
        switch($node->getLocation()) {
            case 'rdbms':
                return opal\rdbms\adapter\Base::factory($node->getType().'://'.$node->getId());
        }
    }
}

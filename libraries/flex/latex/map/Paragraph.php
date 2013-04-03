<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex\latex\map;

use df;
use df\core;
use df\flex;
use df\iris;
    
class Paragraph extends iris\map\Node implements flex\latex\IParagraph, core\IDumpable {

    use flex\latex\TContainerNode;


// Dump
    public function getDumpProperties() {
        return $this->_collection;
    }
}
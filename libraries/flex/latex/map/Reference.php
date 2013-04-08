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
    
class Reference extends iris\map\Node implements flex\latex\IReference, core\IDumpable {

    use flex\latex\TReferable;

    protected $_type;

    public function setType($type) {
        $this->_type = $type;
        return $this;
    }

    public function getType() {
        return $this->_type;
    }

// Dump
    public function getDumpProperties() {
        return $this->id;
    }
}
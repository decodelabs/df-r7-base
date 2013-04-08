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
    
class Bibliography extends iris\map\Node implements flex\latex\IBibliography, core\IDumpable {

    use flex\latex\TContainerNode;

    protected $_digitLength = 2;

    public function setDigitLength($length) {
        $this->_digitLength = (int)$length;

        if($this->_digitLength < 0) {
            $this->_digitLength = 2;
        }

        return $this;
    }

    public function getDigitLength() {
        return $this->_digitLength;
    }

// Dump
    public function getDumpProperties() {
        return $this->_collection;
    }
}
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
    
class Macro extends iris\map\Node implements flex\latex\IMacro, core\IDumpable {

    public $name;

    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    public function getName() {
        return $this->name;
    }

// Dump
    public function getDumpProperties() {
        return $this->name;
    }
}
<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\svg\shape;

use df;
use df\core;
use df\neon;
    
class Circle implements neon\svg\ICircle, core\IDumpable {

    use neon\svg\TAttributeModule;
    use neon\svg\TAttributeModule_Shape;
    use neon\svg\TAttributeModule_Position;
    use neon\svg\TAttributeModule_Radius;

    public function __construct($radius, $x, $y=null) {
    	$this->setRadius($radius);
    	$this->setPosition($x, $y);
    }

// Dump
	public function getDumpProperties() {
		return $this->_attributes;
	}
}
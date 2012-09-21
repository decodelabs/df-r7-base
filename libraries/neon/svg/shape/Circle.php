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

    use TShape;    
    use TShape_Primitive;
    use TShape_RadiusAware;

    public function __construct($radius, $position=null, $yPosition=null) {
    	$this->setRadius($radius);
    	$this->setPosition($position, $yPosition);
    }

// Dump
	public function getDumpProperties() {
		return array_merge(
            [
    			'radius' => $this->_radius->toString(),
    			'position' => $this->_position->toString()
		    ],
            $this->_attributes
        );
	}
}
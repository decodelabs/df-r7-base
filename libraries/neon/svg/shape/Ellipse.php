<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\svg\shape;

use df;
use df\core;
use df\neon;
    
class Ellipse implements neon\svg\IEllipse, core\IDumpable {

    use neon\svg\TAttributeModule;
    use neon\svg\TAttributeModule_Shape;
    use neon\svg\TAttributeModule_Position;
    use neon\svg\TAttributeModule_2DRadius;

    public function __construct($xRadius, $yRadius, $position=null, $yPosition=null) {
    	$this->setXRadius($xRadius);
    	$this->setYRadius($yRadius);
    	$this->setPosition($position, $yPosition);
    }

// Dump
	public function getDumpProperties() {
		return $this->_attributes;
	}
}
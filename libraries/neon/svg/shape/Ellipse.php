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

	use TShape;
    use TShape_Primitive;
    use TShape_2DRadiusAware;

    public function __construct($xRadius, $yRadius, $position=null, $yPosition=null) {
    	$this->setXRadius($xRadius);
    	$this->setYRadius($yRadius);
    	$this->setPosition($position, $yPosition);
    }

// Dump
	public function getDumpProperties() {
		return array_merge(
            [
    			'radius' => $this->_xRadius->toString().' x '.$this->_yRadius->toString(),
				'position' => $this->_position->toString()
		    ],
            $this->_attributes
        );
	}
}
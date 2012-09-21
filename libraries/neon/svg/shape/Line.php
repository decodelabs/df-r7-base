<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\svg\shape;

use df;
use df\core;
use df\neon;
    
class Line implements neon\svg\ILine, core\IDumpable {

    use TShape;
    use TShape_PointData;

    const MIN_POINTS = 2;
    const MAX_POINTS = 2;

    public function __construct($points, $point2=null) {
    	if($point2 !== null) {
    		$points = [$points, $point2];
    	}

    	$this->setPoints($points);
    }

// Dump
    public function getDumpProperties() {
        return array_merge(
            [
                'from' => $this->_points[0]->toString(),
                'to' => $this->_points[1]->toString()
            ],
            $this->_attributes
        );
    }
}
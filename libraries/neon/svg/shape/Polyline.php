<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\svg\shape;

use df;
use df\core;
use df\neon;
    
class Polyline implements neon\svg\IPolyline {

    use TShape;
    use TShape_PointData;

    const MIN_POINTS = 3;
    const MAX_POINTS = null;

    public function __construct($points) {
    	$this->setPoints($points);
    }

// Dump
    public function getDumpProperties() {
    	$points = array();

    	foreach($this->_points as $point) {
    		$points[] = $point->toString();
    	}

        return array_merge(
            [
                'points' => $points
            ],
            $this->_attributes
        );
    }
}
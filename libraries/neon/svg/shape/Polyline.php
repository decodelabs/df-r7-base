<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\svg\shape;

use df;
use df\core;
use df\neon;
    
class Polyline implements neon\svg\IPolyline, core\IDumpable {

    use neon\svg\TAttributeModule;
    use neon\svg\TAttributeModule_Shape;
    use neon\svg\TAttributeModule_PointData;

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
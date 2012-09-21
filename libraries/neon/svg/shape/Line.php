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

    use neon\svg\TAttributeModule;
    use neon\svg\TAttributeModule_Shape;
    use neon\svg\TAttributeModule_PointData;

    const MIN_POINTS = 2;
    const MAX_POINTS = 2;

    public function __construct($points, $point2=null) {
    	if($point2 !== null) {
    		$points = [$points, $point2];
    	}

    	$this->setPoints($points);
    }

    protected function _onSetPoints() {
        $this->_setAttribute('x1', $this->_points[0]->getX());
        $this->_setAttribute('y1', $this->_points[0]->getY());
        $this->_setAttribute('x2', $this->_points[1]->getX());
        $this->_setAttribute('y2', $this->_points[1]->getY());
    }

// Dump
    public function getDumpProperties() {
        return $this->_attributes;
    }
}
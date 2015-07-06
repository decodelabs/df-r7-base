<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\vector\dxf\entity;

use df;
use df\core;
use df\neon;

class Solid implements neon\vector\dxf\ISolidEntity {
    
    use neon\vector\dxf\TEntity;
    use neon\vector\dxf\TDrawingEntity;

    protected $_points = [];

    public function __construct($point1, $point2=null, $point3=null, $point4=null) {
        $this->setPoints($point1, $point2, $point3, $point4);
    }

    public function getType() {
        return 'SOLID';
    }

    public function setPoints($point1, $point2=null, $point3=null, $point4=null) {
        if($point2 === null && is_array($point1)) {
            $points = $point1;
        } else {
            $points = func_get_args();
        }

        for($i = 0; $i < 4; $i++) {
            $this->_points[] = core\math\Vector::factory(array_shift($points), 3);
        }

        return $this;
    }

    public function getPoints() {
        return $this->_points;
    }

    

    public function toString() {
        $output = '';

        foreach($this->_points as $i => $point) {
            $output .= neon\vector\dxf\Document::_writePoint($point, $i);
        }

        $output .= $this->_writeDrawingString();
        return $this->_writeBaseString($output);
    }
}
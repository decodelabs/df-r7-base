<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\vector\dxf\entity;

use df;
use df\core;
use df\neon;

class Circle implements neon\vector\dxf\ICircleEntity {
    
    use neon\vector\dxf\TEntity;
    use neon\vector\dxf\TDrawingEntity;

    protected $_centerPoint;
    protected $_radius = 1;

    public function __construct($vector, $radius=null) {
        $this->setCenterPoint($vector);

        if($radius !== null) {
            $this->setRadius($radius);
        }
    }

    public function getType() {
        return 'CIRCLE';
    }

    public function setCenterPoint($vector) {
        $this->_centerPoint = core\math\Vector::factory($vector, 2);
        return $this;
    }

    public function getCenterPoint() {
        return $this->_centerPoint;
    }

    public function setRadius($radius) {
        $this->_radius = (float)$radius;
        return $this;
    }

    public function getRadius() {
        return $this->_radius;
    }

    public function toString() {
        $output = neon\vector\dxf\Document::_writePoint($this->_centerPoint, 0, [0, 0]);
        $output .= sprintf(" 40\n%F\n", $this->_radius);

        $output .= $this->_writeDrawingString();
        return $this->_writeBaseString($output);
    }
}
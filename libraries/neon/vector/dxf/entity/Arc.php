<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\vector\dxf\entity;

use df;
use df\core;
use df\neon;

class Arc implements neon\vector\dxf\IArcEntity {

    use neon\vector\dxf\TEntity;
    use neon\vector\dxf\TDrawingEntity;

    protected $_centerPoint;
    protected $_radius;
    protected $_startAngle = 0;
    protected $_endAngle = 90;

    public function __construct($vector, $radius=null, $startAngle=null, $endAngle=null) {
        $this->setCenterPoint($vector);

        if($radius !== null) {
            $this->setRadius($radius);
        }

        if($startAngle !== null) {
            $this->setStartAngle($startAngle);
        }

        if($endAngle !== null) {
            $this->setEndAngle($endAngle);
        }
    }

    public function getType() {
        return 'ARC';
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

    public function setStartAngle($angle) {
        $this->_startAngle = (float)$angle;
        return $this;
    }

    public function getStartAngle() {
        return $this->_startAngle;
    }

    public function setEndAngle($angle) {
        $this->_endAngle = (float)$angle;
        return $this;
    }

    public function getEndAngle() {
        return $this->_endAngle;
    }

    public function toString(): string {
        $output = neon\vector\dxf\Document::_writePoint($this->_centerPoint, 0, [0, 0]);
        $output .= sprintf(" 40\n%F\n", $this->_radius);
        $output .= sprintf(" 50\n%F\n", $this->_startAngle);
        $output .= sprintf(" 51\n%F\n", $this->_endAngle);

        $output .= $this->_writeDrawingString();
        return $this->_writeBaseString($output);
    }
}
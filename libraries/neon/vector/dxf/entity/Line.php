<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\vector\dxf\entity;

use df;
use df\core;
use df\neon;

class Line implements neon\vector\dxf\ILineEntity {

    use neon\vector\dxf\TEntity;
    use neon\vector\dxf\TDrawingEntity;

    protected $_startPoint;
    protected $_endPoint;

    public function __construct($startPoint, $endPoint) {
        $this->setStartPoint($startPoint);
        $this->setEndPoint($endPoint);
    }

    public function getType() {
        return 'LINE';
    }

    public function setStartPoint($vector) {
        $this->_startPoint = core\math\Vector::factory($vector, 3);
        return $this;
    }

    public function getStartPoint() {
        return $this->_startPoint;
    }

    public function setEndPoint($vector) {
        $this->_endPoint = core\math\Vector::factory($vector, 3);
        return $this;
    }

    public function getEndPoint() {
        return $this->_endPoint;
    }

    public function toString(): string {
        $output = '';
        $output .= neon\vector\dxf\Document::_writePoint($this->_startPoint, 0);
        $output .= neon\vector\dxf\Document::_writePoint($this->_endPoint, 1);

        $output .= $this->_writeDrawingString();
        return $this->_writeBaseString($output);
    }
}
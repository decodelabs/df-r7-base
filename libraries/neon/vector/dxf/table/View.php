<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\vector\dxf\table;

use df;
use df\core;
use df\neon;

class View implements neon\vector\dxf\IViewTable {

    use neon\vector\dxf\TTable;
    use neon\vector\dxf\TViewControlTable;

    protected $_width = 1;
    protected $_height = 1;


    public function __construct($name) {
        $this->setName($name);
        $this->setCenterPoint([0.5, 0.5]);
        $this->setTargetPoint([0, 0, 0]);
        $this->setTargetDirection([0, 0, 1]);
    }

    public function getType() {
        return 'VIEW';
    }

    public function setWidth($width) {
        $this->_width = (int)$width;
        return $this;
    }

    public function getWidth() {
        return $this->_width;
    }

    public function setHeight($height) {
        $this->_height = (int)$height;
        return $this;
    }

    public function getHeight() {
        return $this->_height;
    }


    public function toString(): string {
        $output = sprintf(
            " 40\n%F\n 41\n%F\n",
            $this->_height,
            $this->_width
        );

        $output .= neon\vector\dxf\Document::_writePoint($this->_centerPoint);
        $output .= neon\vector\dxf\Document::_writePoint($this->_targetDirection, 1);
        $output .= neon\vector\dxf\Document::_writePoint($this->_targetPoint, 2);

        $output .= sprintf(" 42\n%F\n", $this->_lensLength);
        $output .= sprintf(" 43\n%F\n", $this->_frontClippingPlane);
        $output .= sprintf(" 44\n%F\n", $this->_backClippingPlane);
        $output .= sprintf(" 51\n%F\n", $this->_twistAngle);
        $output .= sprintf(" 71\n%u\n", $this->_mode);

        return $this->_writeBaseString($output);
    }
}
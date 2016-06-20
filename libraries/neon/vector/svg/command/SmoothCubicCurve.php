<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\vector\svg\command;

use df;
use df\core;
use df\neon;

class SmoothCubicCurve extends Base implements neon\vector\svg\ISmoothCubicCurveCommand {

    protected $_controlX;
    protected $_controlY;
    protected $_x;
    protected $_y;

    public function __construct($controlX, $controlY, $x, $y) {
        $this->setControl($controlX, $controlY);
        $this->setPosition($x, $y);
    }

    public function setControl($x, $y) {
        return $this->setControlX($x)->setControlY($y);
    }

    public function setControlX($x) {
        $this->_controlX = core\unit\DisplaySize::factory($x, null, true);
        return $this;
    }

    public function getControlX() {
        return $this->_controlX;
    }

    public function setControlY($y) {
        $this->_controlY = core\unit\DisplaySize::factory($y, null, true);
        return $this;
    }

    public function getControlY() {
        return $this->_controlY;
    }

    public function setPosition($x, $y) {
        return $this->setX($x)->setY($y);
    }

    public function setX($x) {
        $this->_x = core\unit\DisplaySize::factory($x, null, true);
        return $this;
    }

    public function getX() {
        return $this->_x;
    }

    public function setY($y) {
        $this->_y = core\unit\DisplaySize::factory($y, null, true);
        return $this;
    }

    public function getY() {
        return $this->_y;
    }

    public function toString(): string {
        $output = $this->_isRelative ? 's' : 'S';
        $output .= $this->_controlX->toString().' ';
        $output .= $this->_controlY->toString().' ';
        $output .= $this->_x->toString().' ';
        $output .= $this->_y->toString();

        return $output;
    }
}
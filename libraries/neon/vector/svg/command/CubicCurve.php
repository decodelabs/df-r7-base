<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\vector\svg\command;

use df;
use df\core;
use df\neon;

class CubicCurve extends Base implements neon\vector\svg\ICubicCurveCommand {

    protected $_control1X;
    protected $_control1Y;
    protected $_control2X;
    protected $_control2Y;
    protected $_x;
    protected $_y;

    public function __construct($control1X, $control1Y, $control2X, $control2Y, $x, $y) {
        $this->setControl1($control1X, $control1Y);
        $this->setControl2($control2X, $control2Y);
        $this->setPosition($x, $y);
    }

    public function setControl1($x, $y) {
        return $this->setControl1X($x)->setControl1Y($y);
    }

    public function setControl1X($x) {
        $this->_control1X = core\unit\DisplaySize::factory($x, null, true);
        return $this;
    }

    public function getControl1X() {
        return $this->_control1X;
    }

    public function setControl1Y($y) {
        $this->_control1Y = core\unit\DisplaySize::factory($y, null, true);
        return $this;
    }

    public function getControl1Y() {
        return $this->_control1Y;
    }

    public function setControl2($x, $y) {
        return $this->setControl2X($x)->setControl2Y($y);
    }

    public function setControl2X($x) {
        $this->_control2X = core\unit\DisplaySize::factory($x, null, true);
        return $this;
    }

    public function getControl2X() {
        return $this->_control2X;
    }

    public function setControl2Y($y) {
        $this->_control2Y = core\unit\DisplaySize::factory($y, null, true);
        return $this;
    }

    public function getControl2Y() {
        return $this->_control2Y;
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
        $output = $this->_isRelative ? 'c' : 'C';
        $output .= $this->_control1X->toString().' ';
        $output .= $this->_control1Y->toString().' ';
        $output .= $this->_control2X->toString().' ';
        $output .= $this->_control2Y->toString().' ';
        $output .= $this->_x->toString().' ';
        $output .= $this->_y->toString();

        return $output;
    }
}
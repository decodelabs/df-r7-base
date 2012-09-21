<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\svg\command;

use df;
use df\core;
use df\neon;
    
class Arc extends Base implements neon\svg\IArcCommand {

    protected $_xRadius;
    protected $_yRadius;
    protected $_angle;
    protected $_largeArc = false;
    protected $_sweep = false;
    protected $_x;
    protected $_y;

    public function __construct($xRadius, $yRadius, $angle, $largeArc, $sweep, $x, $y) {
    	$this->setRadius($xRadius, $yRadius);
    	$this->setAngle($angle);
    	$this->isLargeArc((bool)$largeArc);
    	$this->isSweep((bool)$sweep);
    	$this->setPosition($x, $y);
    }

    public function setRadius($xRadius, $yRadius=null) {
    	if($yRadius === null) {
    		$yRadius = $xRadius;
    	}

    	return $this->setXRadius($xRadius)->setYRadius($yRadius);
    }

	public function setXRadius($radius) {
		$this->_xRadius = core\unit\DisplaySize::factory($radius, null, true);
		return $this;
	}

	public function getXRadius() {
		return $this->_xRadius;
	}

	public function setYRadius($radius) {
		$this->_yRadius = core\unit\DisplaySize::factory($radius, null, true);
		return $this;
	}

	public function getYRadius() {
		return $this->_yRadius;
	}


	public function setAngle($angle) {
		$this->_angle = core\unit\Angle::factory($angle);
		return $this;
	}

	public function getAngle() {
		return $this->_angle;
	}


    public function isLargeArc($flag=null) {
    	if($flag !== null) {
    		$this->_largeArc = (bool)$flag;
    		return $this;
    	}

    	return $this->_largeArc;
    }

	public function isSweep($flag=null) {
		if($flag !== null) {
			$this->_sweep = (bool)$flag;
			return $this;
		}

		return $this->_sweep;
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

	public function toString() {
		$output = $this->_isRelative ? 'a' : 'A';
		$output .= $this->_xRadius->toString().' ';
		$output .= $this->_yRadius->toString().' ';
		$output .= $this->_angle->getDegrees().' ';
		$output .= $this->_largeArc ? '1 ' : '0 ';
		$output .= $this->_sweep ? '1 ' : '0 ';
		$output .= $this->_x->toString().' ';
		$output .= $this->_y->toString();

		return $output;
	}
}
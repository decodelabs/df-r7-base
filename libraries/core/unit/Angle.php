<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\unit;

use df;
use df\core;
    
class Angle implements IAngle, core\IDumpable {

	use core\TStringProvider;

	private static $_units = ['deg', 'rad', 'grad', 'turn'];

	protected $_value;
	protected $_unit;

    public static function factory($value, $unit=null) {
    	if($value instanceof IAngle) {
    		return $value;
    	}

    	return new self($value, $unit);
    }

    public function __construct($value, $unit=null) {
    	$this->parse($value, $unit);
    }

    public function toString() {
    	return $this->_value.$this->_unit;
    }

    public function parse($value, $unit=null) {
    	if(preg_match('/^([0-9.\-+e]+)('.implode('|', self::$_units).')$/i', $value, $matches)) {
			$value = $matches[1];
			$unit = $matches[2];
    	}

    	$this->setValue($value);

    	if($unit !== null) {
    		$this->setUnit($unit);
    	}

    	return $this;
    }

    public function setValue($value) {
    	$this->_value = (float)$value;
    	return $this;
    }

	public function getValue() {
		return $this->_value;
	}

	public function setUnit($unit, $convertValue=true) {
		$unit = strtolower($unit);

		switch($unit) {
			case 'deg':
			case 'rad':
			case 'grad':
			case 'turn':
				break;

			default:
				$unit = 'deg';
				break;
		}

		if($convertValue && $this->_unit !== null) {
			$this->_value = $this->_convert($this->_value, $this->_unit, $unit);
		}

		$this->_unit = $unit;
		return $this;
	}

	public function getUnit() {
		return $this->_unit;
	}

	public function normalize() {
		$useMargin = false;

		switch($this->_unit) {
			case 'deg':
				$limit = 360;
				break;

			case 'rad':
				$limit = 360 / (180 / pi());
				$useDelta = true;
				break;

			case 'grad':
				$limit = 400;
				break;

			case 'turn':
				$limit = 1;
				break;
		}

		$upper = $limit;
		$lower = -$limit;

		if($useMargin) {
			$upper = $limit + 0.000005;
			$lower = -$limit - 0.000005;
		}

		while($this->_value > $upper) {
			$this->_value -= $limit;
		}

		while($this->_value < $lower) {
			$this->_value += $limit;
		}

		return $this;
	}

	public function setDegrees($degrees) {
		$this->setValue($degrees);
		$this->_unit = 'deg';
		return $this;
	}

	public function getDegrees() {
		return $this->_convert($this->_value, $this->_unit, 'deg');
	}

	public function setRadians($radians) {
		$this->setValue($radians);
		$this->_unit = 'rad';
		return $this;
	}

	public function getRadians() {
		return $this->_convert($this->_value, $this->_unit, 'rad');
	}

	public function setGradians($gradians) {
		$this->setValue($gradians);
		$this->_unit = 'grad';
		return $this;
	}

	public function getGradians() {
		return $this->_convert($this->_value, $this->_unit, 'grad');
	}

	public function setTurns($turns) {
		$this->setValue($turns);
		$this->_unit = 'turn';
		return $this;
	}

	public function getTurns() {
		return $this->_convert($this->_value, $this->_unit, 'turn');
	}

	protected function _convert($value, $inUnit, $outUnit) {
		if($inUnit == $outUnit) {
			return $value;
		}

		switch($inUnit) {
			case 'deg':
				$degrees = $value;
				break;

			case 'rad':
				$degrees = $value * (180 / pi());
				break;

			case 'grad':
				$degrees = ($value / 400) * 360;
				break;

			case 'turn':
				$degrees = $value * 360;
				break;
		}

		switch($outUnit) {
			case 'deg':
				$value = $degrees;
				break;

			case 'rad':
				$value = $degrees / (180 / pi());
				break;

			case 'grad':
				$value = ($degrees / 360) * 400;
				break;

			case 'turn':
				$value = $degrees / 360;
				break;
		}

		return $value;
	}

// Dump
	public function getDumpProperties() {
		return $this->toString();
	}
}
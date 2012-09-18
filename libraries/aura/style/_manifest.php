<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\style;

use df;
use df\core;
use df\aura;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}


// Interfaces
interface ISize extends core\IStringProvider {
	public function parse($value, $unit=null);
	public function setValue($value);
	public function getValue();
	public function setUnit($unit);
	public function getUnit();
}

interface IPosition extends core\IStringProvider {
	public function parse($position, $position2=null);
	public function setX($value);
	public function getX();
	public function setXAnchor($anchor);
	public function getXAnchor();
	public function setXOffset($offset);
	public function getXOffset();
	public function setY($value);
	public function getY();
	public function setYAnchor($anchor);
	public function getYAnchor();
	public function setYOffset($offset);
	public function getYOffset();
}

interface IAngle extends core\IStringProvider {
	public function parse($angle, $unit=null);
	public function setValue($value);
	public function getValue();
	public function setUnit($unit, $convertValue=true);
	public function getUnit();

	public function setDegrees($degrees);
	public function getDegrees();
	public function setRadians($radians);
	public function getRadians();
	public function setGradians($gradians);
	public function getGradians();
	public function setTurns($turns);
	public function getTurns();
}
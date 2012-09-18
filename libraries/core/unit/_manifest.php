<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\unit;

use df;
use df\core;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}
class LogicException extends \LogicException implements IException {}


// Interfaces
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

interface IDisplaySize extends core\IStringProvider {
	public function parse($value, $unit=null);
	public function setValue($value);
	public function getValue();
	public function setUnit($unit, $convertValue=true);
	public function getUnit();
	public function isRelative();
	public function isAbsolute();
	public function setDPI($dpi);
	public function getDPI();

	public function setPixels($px);
	public function getPixels();
	public function setInches($in);
	public function getInches();
	public function setMillimeters($mm);
	public function getMillimeters();
	public function setCentimeters($cm);
	public function getCentimeters();
	public function setPoints($pt);
	public function getPoints();
	public function setPica($pc);
	public function getPica();

	public function setPercentage($percent);
	public function setEms($ems);
	public function setExes($exes);
	public function setZeros($zeros);
	public function setRootElementFontSize($rem);
	public function setViewportWidth($vw);
	public function setViewportHeight($vh);
	public function setViewportMin($vmin);
	public function setViewportMax($vmax);
}

interface IDisplayPosition extends core\IStringProvider {
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

interface IFrequency extends core\IStringProvider {
	public function parse($value, $unit=null);
	public function setValue($value);
	public function getValue();
	public function setUnit($unit, $convertValue=true);
	public function getUnit();

	public function setHertz($hertz);
	public function getHertz();
	public function setKilohertz($kilohertz);
	public function getKilohertz();
	public function setMegahertz($megahertz);
	public function getMegahertz();
	public function setGigahertz($gigahertz);
	public function getGigahertz();
}
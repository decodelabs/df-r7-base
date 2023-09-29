<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\unit;

use DecodeLabs\Exceptional;

use df\core;

interface IUnit
{
}

interface ICssCompatibleUnit
{
    public function toCssString(): string;
}


interface ISingleValueUnit
{
    public function parse($angle, $unit = null, $allowPlainNumbers = false);
    public function setValue($value);
    public function getValue();
    public function setUnit($unit, $convertValue = true);
    public function getUnit();
}

trait TSingleValueUnit
{
    use core\TStringProvider;

    //const UNITS = [];

    public function __construct($value, $unit = null, $allowPlainNumbers = false)
    {
        $this->parse($value, $unit, $allowPlainNumbers);
    }

    public function parse($value, $unit = null, $allowPlainNumbers = false)
    {
        if (preg_match('/^([0-9.\-+e]+) *([^0-9.\-+]+)$/i', $value, $matches)) {
            $value = $matches[1];
            $unit = $matches[2];
        }

        $this->setValue($value);

        if ($this->_unit === null && $unit === null && !$allowPlainNumbers) {
            $unit = static::DEFAULT_UNIT;
        }

        if ($unit !== null) {
            $this->setUnit($unit, false);
        }

        return $this;
    }

    public function isEmpty(): bool
    {
        return $this->_value == 0;
    }

    public function setValue($value)
    {
        $this->_value = (float)$value;
        return $this;
    }

    public function getValue()
    {
        return $this->_value;
    }

    public function setUnit($unit, $convertValue = true)
    {
        $unit = strtolower((string)$unit);

        if (empty($unit)) {
            $unit = static::DEFAULT_UNIT;
        }

        if (!in_array($unit, self::UNITS)) {
            $found = false;

            if (strlen($unit) == 1) {
                foreach (self::UNITS as $test) {
                    if ($test[0] == $unit) {
                        $unit = $test;
                        $found = true;
                        break;
                    }
                }
            }

            if (!$found) {
                throw Exceptional::InvalidArgument(
                    $unit . ' is not a valid unit option'
                );
            }
        }

        if ($convertValue && $this->_unit !== null) {
            $this->_value = $this->_convert($this->_value, $this->_unit, $unit);
        }

        $this->_unit = $unit;
        return $this;
    }

    public function getUnit()
    {
        return $this->_unit;
    }

    public function toString(): string
    {
        return $this->_value . $this->_unit;
    }

    public function toCssString(): string
    {
        return $this->toString();
    }

    abstract protected function _convert($value, $inUnit, $outUnit);

    protected function _parseUnit($value, $unit)
    {
        $this->setValue($value);
        $this->setUnit($unit, false);
        return $this;
    }
}


interface IAngle extends IUnit, ICssCompatibleUnit, ISingleValueUnit, core\IStringProvider
{
    public function normalize();

    public function setDegrees($degrees);
    public function getDegrees();
    public function setRadians($radians);
    public function getRadians();
    public function setGradians($gradians);
    public function getGradians();
    public function setTurns($turns);
    public function getTurns();
}

interface IDisplaySize extends IUnit, ICssCompatibleUnit, ISingleValueUnit, core\IStringProvider
{
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

    public function extractAbsolute($length, $fontSize = null, $viewportWidth = null, $viewportHeight = null);
    public function extractAbsoluteFromLength($length);
    public function extractAbsoluteFromFontSize($size);
    public function extractAbsoluteFromViewport($width, $height);
}

interface IDisplayPosition extends IUnit, ICssCompatibleUnit, core\IStringProvider
{
    public function parse($position, $position2 = null);
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
    public function isRelative();
    public function isAbsolute();
    public function hasRelativeAnchor();
    public function hasRelativeXAnchor();
    public function hasRelativeYAnchor();
    public function convertRelativeAnchors($width = null, $height = null);
    public function extractAbsolute($width, $height, $compositeWidth = null, $compositeHeight = null);
}

interface IFileSize extends IUnit, ISingleValueUnit, core\IStringProvider
{
    public function setBits($bits);
    public function getBits();
    public function setBytes($bytes);
    public function getBytes();
    public function setKilobytes($kb);
    public function getKilobytes();
    public function setMegabytes($mb);
    public function getMegabytes();
    public function setGigabytes($gb);
    public function getGigabytes();
    public function setTerabytes($tb);
    public function getTerabytes();
    public function setPetabytes($pb);
    public function getPetabytes();
}

interface IFrequency extends IUnit, ICssCompatibleUnit, ISingleValueUnit, core\IStringProvider
{
    public function setHz($hz);
    public function getHz();
    public function setKhz($khz);
    public function getKhz();
    public function setMhz($khz);
    public function getMhz();
    public function setGhz($khz);
    public function getGhz();
    public function setBpm($bpm);
    public function getBpm();
}

interface IRatio extends IUnit, ICssCompatibleUnit, core\IStringProvider
{
    public function parse($value, $denominator = null);
    public function setFraction($numerator, $denominator);
    public function getNumerator();
    public function getDenominator();
    public function setFactor($factor);
    public function getFactor();
}

interface IResolution extends IUnit, ICssCompatibleUnit, ISingleValueUnit, core\IStringProvider
{
    public function setDpi($dpi);
    public function getDpi();
    public function setDpcm($dpcm);
    public function getDpcm();
    public function setDppx($dppx);
    public function getDppx();
}



## ENUMS
class Priority extends core\lang\Enum
{
    public const TRIVIAL = null;
    public const LOW = null;
    public const MEDIUM = null;
    public const HIGH = null;
    public const CRITICAL = null;
}

class Gender extends core\lang\Enum
{
    public const MALE = 'Male';
    public const FEMALE = 'Female';
}

<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\unit;

use DecodeLabs\Exceptional;
use DecodeLabs\Glitch\Dumpable;

class Angle implements IAngle, Dumpable
{
    use TSingleValueUnit;

    public const DEFAULT_UNIT = 'deg';
    public const UNITS = ['deg', 'rad', 'grad', 'turn'];

    protected $_value;
    protected $_unit;

    public static function factory($value, $unit = null, $allowPlainNumbers = false)
    {
        if ($value instanceof IAngle) {
            return $value;
        }

        return new self($value, $unit, $allowPlainNumbers);
    }

    public function toCssString(): string
    {
        return $this->getDegrees() . 'deg';
    }

    public function normalize()
    {
        switch ($this->_unit) {
            case null:
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

            default:
                throw Exceptional::InvalidArgument(
                    'Unsupport angle unit: ' . $this->_unit
                );
        }

        $upper = $limit;
        $lower = -$limit;

        while ($this->_value > $upper) {
            $this->_value -= $limit;
        }

        while ($this->_value < $lower) {
            $this->_value += $limit;
        }

        return $this;
    }

    public function setDegrees($degrees)
    {
        return $this->_parseUnit($degrees, 'deg');
    }

    public function getDegrees()
    {
        return $this->_convert($this->_value, $this->_unit, 'deg');
    }

    public function setRadians($radians)
    {
        return $this->_parseUnit($radians, 'rad');
    }

    public function getRadians()
    {
        return $this->_convert($this->_value, $this->_unit, 'rad');
    }

    public function setGradians($gradians)
    {
        return $this->_parseUnit($gradians, 'grad');
    }

    public function getGradians()
    {
        return $this->_convert($this->_value, $this->_unit, 'grad');
    }

    public function setTurns($turns)
    {
        return $this->_parseUnit($turns, 'turn');
    }

    public function getTurns()
    {
        return $this->_convert($this->_value, $this->_unit, 'turn');
    }

    protected function _convert($value, $inUnit, $outUnit)
    {
        if ($inUnit === null) {
            $inUnit = self::DEFAULT_UNIT;
        }

        if ($outUnit === null) {
            $outUnit = self::DEFAULT_UNIT;
        }

        if ($inUnit == $outUnit) {
            return $value;
        }

        switch ($inUnit) {
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

            default:
                throw Exceptional::InvalidArgument(
                    'Unsupport angle unit: ' . $inUnit
                );
        }

        switch ($outUnit) {
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

    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'text' => $this->toString();
    }
}

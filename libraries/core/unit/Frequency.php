<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\unit;

use DecodeLabs\Exceptional;
use DecodeLabs\Glitch\Dumpable;

class Frequency implements IFrequency, Dumpable
{
    use TSingleValueUnit;

    public const DEFAULT_UNIT = 'khz';
    public const UNITS = ['hz', 'khz', 'mhz', 'ghz', 'bpm'];

    protected $_value;
    protected $_unit;

    public static function factory($value, $unit = null, $allowPlainNumbers = false)
    {
        if ($value instanceof IFrequency) {
            return $value;
        }

        return new self($value, $unit, $allowPlainNumbers);
    }

    public function toCssString(): string
    {
        return $this->getKhz() . 'kHz';
    }

    public function setHz($hz)
    {
        return $this->_parseUnit($hz, 'hz');
    }

    public function getHz()
    {
        return $this->_convert($this->_value, $this->_unit, 'hz');
    }

    public function setKhz($khz)
    {
        return $this->_parseUnit($khz, 'khz');
    }

    public function getKhz()
    {
        return $this->_convert($this->_value, $this->_unit, 'khz');
    }

    public function setMhz($mhz)
    {
        return $this->_parseUnit($mhz, 'mhz');
    }

    public function getMhz()
    {
        return $this->_convert($this->_value, $this->_unit, 'mhz');
    }

    public function setGhz($ghz)
    {
        return $this->_parseUnit($ghz, 'ghz');
    }

    public function getGhz()
    {
        return $this->_convert($this->_value, $this->_unit, 'ghz');
    }

    public function setBpm($bpm)
    {
        return $this->_parseUnit($bpm, 'bpm');
    }

    public function getBpm()
    {
        return $this->_convert($this->_value, $this->_unit, 'bpm');
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

        if ($inUnit == 'bpm') {
            $value /= 60;
            $inUnit = 'hz';
        }


        switch ($inUnit) {
            case 'hz':
                $factor = 0;
                break;

            case 'khz':
                $factor = 1;
                break;

            case 'mhz':
                $factor = 2;
                break;

            case 'ghz':
                $factor = 3;
                break;

            default:
                throw Exceptional::InvalidArgument(
                    'Unsupported frequency unit: ' . $inUnit
                );
        }

        $bpm = false;

        switch ($outUnit) {
            case 'bpm':
                $factor -= 0;
                $bpm = true;
                break;

            case 'hz':
                $factor -= 0;
                break;

            case 'khz':
                $factor -= 1;
                break;

            case 'mhz':
                $factor -= 2;
                break;

            case 'ghz':
                $factor -= 3;
                break;
        }

        $output = $value *= pow(1000, $factor);

        if ($bpm) {
            $output *= 60;
        }

        return $output;
    }

    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'text' => $this->toString();
    }
}

<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\unit;

use df;
use df\core;
use df\aura;

use DecodeLabs\Glitch\Dumpable;
use DecodeLabs\Exceptional;

class DisplaySize implements IDisplaySize, Dumpable
{
    use TSingleValueUnit;

    const DEFAULT_FONT_SIZE = '16px';
    const DEFAULT_UNIT = 'px';
    const UNITS = ['%', 'in', 'cm', 'mm', 'em', 'ex', 'pt', 'pc', 'px', 'ch', 'rem', 'vh', 'vw', 'vmin', 'vmax'];

    protected $_value;
    protected $_unit;
    protected $_dpi = 96;

    public static function factory($value, $unit=null, $allowPlainNumbers=false)
    {
        if ($value instanceof IDisplaySize) {
            return $value;
        }

        return new self($value, $unit, $allowPlainNumbers);
    }

    public function isRelative()
    {
        return !$this->isAbsolute();
    }

    public function isAbsolute()
    {
        return $this->_isAbsolute($this->_unit);
    }

    protected function _isAbsolute($unit)
    {
        return in_array($unit, [null, 'px', 'in', 'mm', 'cm', 'pt', 'pc'], true);
    }

    public function setDPI($dpi)
    {
        $this->_dpi = (int)$dpi;
        return $this;
    }

    public function getDPI()
    {
        return $this->_dpi;
    }



    public function setPixels($px)
    {
        return $this->_parseUnit($px, 'px');
    }

    public function getPixels()
    {
        return $this->_convert($this->_value, $this->_unit, 'px');
    }

    public function setInches($in)
    {
        return $this->_parseUnit($in, 'in');
    }

    public function getInches()
    {
        return $this->_convert($this->_value, $this->_unit, 'in');
    }

    public function setMillimeters($mm)
    {
        return $this->_parseUnit($mm, 'mm');
    }

    public function getMillimeters()
    {
        return $this->_convert($this->_value, $this->_unit, 'mm');
    }

    public function setCentimeters($cm)
    {
        return $this->_parseUnit($cm, 'cm');
    }

    public function getCentimeters()
    {
        return $this->_convert($this->_value, $this->_unit, 'cm');
    }

    public function setPoints($pt)
    {
        return $this->_parseUnit($pt, 'pt');
    }

    public function getPoints()
    {
        return $this->_convert($this->_value, $this->_unit, 'pt');
    }

    public function setPica($pc)
    {
        return $this->_parseUnit($pc, 'pc');
    }

    public function getPica()
    {
        return $this->_convert($this->_value, $this->_unit, 'pc');
    }


    public function setPercentage($percent)
    {
        return $this->_parseUnit($percent, '%');
    }

    public function setEms($ems)
    {
        return $this->_parseUnit($ems, 'em');
    }

    public function setExes($exes)
    {
        return $this->_parseUnit($exes, 'ex');
    }

    public function setZeros($zeros)
    {
        return $this->_parseUnit($zeros, 'ch');
    }

    public function setRootElementFontSize($rem)
    {
        return $this->_parseUnit($rem, 'rem');
    }

    public function setViewportWidth($vw)
    {
        return $this->_parseUnit($vw, 'vw');
    }

    public function setViewportHeight($vh)
    {
        return $this->_parseUnit($vh, 'vh');
    }

    public function setViewportMin($vmin)
    {
        return $this->_parseUnit($vmin, 'vmin');
    }

    public function setViewportMax($vmax)
    {
        return $this->_parseUnit($vmax, 'vmax');
    }


    public function extractAbsolute($length, $fontSize=null, $viewportWidth=null, $viewportHeight=null)
    {
        if ($this->isAbsolute()) {
            return clone $this;
        }

        switch ($this->_unit) {
            case '%':
                if ($length === null) {
                    throw Exceptional::Runtime(
                        'No absolute length data has been given to convert to an absolute value from percentage'
                    );
                }

                return $this->extractAbsoluteFromLength($length);

            case 'em':
            case 'ex':
            case 'ch':
            case 'rem':
                if ($fontSize === null) {
                    $fontSize = self::DEFAULT_FONT_SIZE;
                }

                return $this->extractAbsoluteFromFontSize($fontSize);

            case 'vw':
            case 'vh':
            case 'vmin':
            case 'vmax':
                if ($viewportWidth === null && $viewportHeight === null) {
                    throw Exceptional::Runtime(
                        'No absolute viewport size data has been given to convert to an absolute value'
                    );
                }

                return $this->extractAbsoluteFromViewport($viewportWidth, $viewportHeight);
        }
    }

    public function extractAbsoluteFromLength($length)
    {
        $length = clone self::factory($length);

        if (!$length->isAbsolute()) {
            throw Exceptional::InvalidArgument(
                'Extraction length must be absolute'
            );
        }

        $length->_value = ($this->_value / 100) * $length->_value;
        return $length;
    }

    public function extractAbsoluteFromFontSize($size)
    {
        $size = clone self::factory($size);

        if (!$size->isAbsolute()) {
            throw Exceptional::InvalidArgument(
                'Extraction font size must be absolute'
            );
        }

        $factor = $this->_value;

        switch ($this->_unit) {
            case 'em':
            case 'ex':
            case 'ch':
                $factor /= 2;

                // no break
            case 'rem':
                $size->_value *= $factor;
                break;
        }

        return $size;
    }

    public function extractAbsoluteFromViewport($width, $height)
    {
        if ($width === null) {
            $width = $height;
        }

        if ($height === null) {
            $height = $width;
        }

        $width = clone self::factory($width);
        $height = clone self::factory($height);

        if (!$width->isAbsolute() || !$height->isAbsolute()) {
            throw Exceptional::InvalidArgument(
                'Extraction viewport size must be absolute'
            );
        }

        switch ($this->_unit) {
            case 'vw':
                $width->_value = ($width->_value / 100) * $this->_value;
                return $width;

            case 'vh':
                $height->_value = ($height->_value / 100) * $this->_value;
                return $height;

            case 'vmin':
                $output = $width->_value < $height->_value ? $width : $height;
                $output->_value = ($output->_value / 100) * $this->_value;
                return $output;

            case 'vmax':
                $output = $width->_value > $height->_value ? $width : $height;
                $output->_value = ($output->_value / 100) * $this->_value;
                return $output;
        }
    }


    protected function _convert($value, $inUnit, $outUnit)
    {
        if (!$this->_isAbsolute($inUnit)) {
            throw Exceptional::Logic(
                'Only absolute size values can be converted'
            );
        }

        if (!$this->_isAbsolute($outUnit)) {
            throw Exceptional::Logic(
                'Size values cannot be converted to relative units'
            );
        }

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
            case 'px':
                $px = $value;
                break;

            case 'in':
                $px = $value * $this->_dpi;
                break;

            case 'mm':
                $px = ($value / 25.4) * $this->_dpi;
                break;

            case 'cm':
                $px = ($value / 2.54) * $this->_dpi;
                break;

            case 'pt':
                $px = ($value / 72) * $this->_dpi;
                break;

            case 'pc':
                $px = ($value / 6) * $this->_dpi;
                break;

            default:
                throw Exceptional::InvalidArgument(
                    'Unsupported display size unit: '.$inUnit
                );
        }

        switch ($outUnit) {
            case 'px':
                $value = $px;
                break;

            case 'in':
                $value = $px / $this->_dpi;
                break;

            case 'mm':
                $value = ($px / $this->_dpi) * 25.4;
                break;

            case 'cm':
                $value = ($px / $this->_dpi) * 2.54;
                break;

            case 'pt':
                $value = ($px / $this->_dpi) * 72;
                break;

            case 'pc':
                $value = ($px / $this->_dpi) * 6;
                break;
        }

        return $value;
    }

    /**
     * Inspect for Glitch
     */
    public function glitchDump(): iterable
    {
        yield 'text' => $this->toString();
    }
}

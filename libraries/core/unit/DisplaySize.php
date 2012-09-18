<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\unit;

use df;
use df\core;
use df\aura;
    
class DisplaySize implements IDisplaySize, core\IDumpable {

	use core\TStringProvider;

	private static $_units = ['%', 'in', 'cm', 'mm', 'em', 'ex', 'pt', 'pc', 'px', 'ch', 'rem', 'vh', 'vw', 'vmin', 'vmax'];

    protected $_value;
    protected $_unit = 'px';
    protected $_dpi = 96;

    public static function factory($value, $unit=null) {
    	if($value instanceof IDisplaySize) {
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

    	if(!in_array($unit, self::$_units)) {
    		throw new InvalidArgumentException(
    			$unit.' is not a valid style size unit'
			);
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

    public function isRelative() {
        return !$this->isAbsolute();
    }

    public function isAbsolute() {
        return $this->_isAbsolute($this->_unit);
    }

    protected function _isAbsolute($unit) {
        return in_array($unit, ['px', 'in', 'mm', 'cm', 'pt', 'pc']);
    }

    public function setDPI($dpi) {
        $this->_dpi = (int)$dpi;
        return $this;
    }

    public function getDPI() {
        return $this->_dpi;
    }



    public function setPixels($px) {
        $this->setValue($px);
        $this->_unit = 'px';
        return $this;
    }

    public function getPixels() {
        return $this->_convert($this->_value, $this->_unit, 'px');
    }

    public function setInches($in) {
        $this->setValue($in);
        $this->_unit = 'in';
        return $this;
    }

    public function getInches() {
        return $this->_convert($this->_value, $this->_unit, 'in');
    }

    public function setMillimeters($mm) {
        $this->setValue($mm);
        $this->_unit = 'mm';
        return $this;
    }

    public function getMillimeters() {
        return $this->_convert($this->_value, $this->_unit, 'mm');
    }

    public function setCentimeters($cm) {
        $this->setValue($cm);
        $this->_unit = 'cm';
        return $this;
    }

    public function getCentimeters() {
        return $this->_convert($this->_value, $this->_unit, 'cm');
    }

    public function setPoints($pt) {
        $this->setValue($pt);
        $this->_unit = 'pt';
        return $this;
    }

    public function getPoints() {
        return $this->_convert($this->_value, $this->_unit, 'pt');
    }

    public function setPica($pc) {
        $this->setValue($pc);
        $this->_unit = 'pc';
        return $this;
    }

    public function getPica() {
        return $this->_convert($this->_value, $this->_unit, 'pc');
    }


    public function setPercentage($percent) {
        $this->setValue($percent);
        $this->_unit = '%';
        return $this;
    }

    public function setEms($ems) {
        $this->setValue($ems);
        $this->_unit = 'em';
        return $this;
    }

    public function setExes($exes) {
        $this->setValue($exes);
        $this->_unit = 'ex';
        return $this;
    }

    public function setZeros($zeros) {
        $this->setValue($zeros);
        $this->_unit = 'ch';
        return $this;
    }

    public function setRootElementFontSize($rem) {
        $this->setValue($rem);
        $this->_unit = 'rem';
        return $this;
    }

    public function setViewportWidth($vw) {
        $this->setValue($vw);
        $this->_unit = 'vw';
        return $this;
    }

    public function setViewportHeight($vh) {
        $this->setValue($vh);
        $this->_unit = 'vh';
        return $this;
    }

    public function setViewportMin($vmin) {
        $this->setValue($vmin);
        $this->_unit = 'vmin';
        return $this;
    }

    public function setViewportMax($vmax) {
        $this->setValue($vmax);
        $this->_unit = $vmax;
        return $this;
    }


    protected function _convert($value, $inUnit, $outUnit) {
        if(!$this->_isAbsolute($inUnit)) {
            throw new LogicException(
                'Only absolute size values can be converted'
            );
        }

        if(!$this->_isAbsolute($outUnit)) {
            throw new LogicException(
                'Size values cannot be converted to relative units'
            );
        }

        if($inUnit == $outUnit) {
            return $value;
        }

        switch($inUnit) {
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
                $pc = ($value / 6) * $this->_dpi;
                break;
        }

        switch($outUnit) {
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

// Dump
    public function getDumpProperties() {
    	return $this->toString();
    }
}
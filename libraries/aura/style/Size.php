<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\style;

use df;
use df\core;
use df\aura;
    
class Size implements ISize, core\IDumpable {

	use core\TStringProvider;

	private static $_units = ['%', 'in', 'cm', 'mm', 'em', 'ex', 'pt', 'pc', 'px'];

    protected $_value;
    protected $_unit = 'px';

    public static function factory($value, $unit=null) {
    	if($value instanceof ISize) {
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

    public function setUnit($unit) {
    	$unit = strtolower($unit);

    	if(!in_array($unit, self::$_units)) {
    		throw new InvalidArgumentException(
    			$unit.' is not a valid style size unit'
			);
    	}

    	$this->_unit = $unit;
    	return $this;
    }

    public function getUnit() {
    	return $this->_unit;
    }


// Dump
    public function getDumpProperties() {
    	return $this->toString();
    }
}
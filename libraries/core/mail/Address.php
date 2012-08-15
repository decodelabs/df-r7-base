<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\mail;

use df;
use df\core;
    
class Address implements IAddress {

	use core\TStringProvider;

    protected $_name;
    protected $_address;
    protected $_isValid = null;

    public static function factory($address, $name=null) {
        if($address === null) {
            return null;
        } else if($address instanceof IAddress) {
    		return $address;
    	} else if($name !== null) {
    		return new self($address, $name);
    	} else if(is_string($address)) {
    		return self::fromString($address);
    	} else {
    		throw new InvalidArgumentException(
    			'Invalid email address'
			);
    	}
    }

    public static function fromString($string) {
    	$parts = explode('<', $string, 2);

    	$address = rtrim(trim(array_pop($parts)), '>');
		$name = trim(array_shift($parts));

		if(empty($name)) {
			$name = null;
		}

		return new self($address, $name);
    }

    public function __construct($address, $name=null) {
    	$this->setAddress($address);
    	$this->setName($name);
    }


    public function setAddress($address) {
    	$address = strtolower($address);
        $address = str_replace(array(' at ', ' dot '), array('@', '.'), $address);
        $address = filter_var($address, \FILTER_SANITIZE_EMAIL);

        $this->_address = $address;
        return $this;
    }

    public function getAddress() {
    	return $this->_address;
    }

    public function setName($name) {
    	$this->_name = $name;
    	return $this;
    }

    public function getName() {
    	return $this->_name;
    }


    public function isValid() {
    	if($this->_isValid === null) {
    		$this->_isValid = (bool)filter_var($this->_address, \FILTER_VALIDATE_EMAIL);
    	}

    	return (bool)$this->_isValid;
    }
    
    public function toString() {
    	$output = $this->_address;

    	if(!empty($this->_name)) {
    		$output = $this->_name.' <'.$output.'>';
    	}

    	return $output;
    }
}
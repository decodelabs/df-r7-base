<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\uri;

use df;
use df\core;

class TelephoneUrl implements ITelephoneUrl, core\IDumpable {

    use core\TStringProvider;

    protected $_number;

    public static function factory($url) {
        if($url instanceof ITelephoneUrl) {
            return $url;
        }

        $class = get_called_class();
        return new $class($url);
    }

    public function __construct($url=null) {
        if($url !== null) {
            $this->import($url);
        }
    }

    public function import($url='') {
        if($url !== null) {
            $this->reset();
        }

        if($url == '' || $url === null) {
            return $this;
        }

        if($url instanceof self) {
            $this->_number = $url->_number;
            return $this;
        }

        if(strtolower(substr($url, 0, 4)) == 'tel:') {
            $url = ltrim(substr($url, 4), '/');
        }

        $this->setNumber($url);
        return $this;
    }

    public function reset() {
        $this->_number = null;
        return $this;
    }


// Scheme
    public function getScheme() {
        return 'tel';
    }


// Number
    public function setNumber($number) {
        $this->_number = (string)$number;
        return $this;
    }

    public function getNumber() {
        return $this->_number;
    }

    public function getCanonicalNumber() {
        return preg_replace('/[^0-9\#\+]/', '', $this->_number);
    }

// String
    public function toString(): string {
        return 'tel:'.$this->getCanonicalNumber();
    }

    public function toReadableString() {
        return 'tel:'.$this->_number;
    }

// Dump
    public function getDumpProperties() {
        return $this->toReadableString();
    }
}

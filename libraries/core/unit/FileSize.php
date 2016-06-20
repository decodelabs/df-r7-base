<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\unit;

use df;
use df\core;

class FileSize implements IFileSize, core\IDumpable {

    use TSingleValueUnit;

    const DEFAULT_UNIT = 'b';
    const UNITS = ['bit', 'b', 'kb', 'mb', 'gb', 'tb', 'pb'];

    protected $_value;
    protected $_unit;

    public static function factory($value, $unit=null, $allowPlainNumbers=false) {
        if($value instanceof IFileSize) {
            return $value;
        }

        return new self($value, $unit, $allowPlainNumbers);
    }

    public function setBits($bits) {
        return $this->_parseUnit($bits, 'bit');
    }

    public function getBits() {
        return $this->_convert($this->_value, $this->_unit, 'bit');
    }

    public function setBytes($bytes) {
        return $this->_parseUnit($bytes, 'b');
    }

    public function getBytes() {
        return $this->_convert($this->_value, $this->_unit, 'b');
    }

    public function setKilobytes($kb) {
        return $this->_parseUnit($kb, 'kb');
    }

    public function getKilobytes() {
        return $this->_convert($this->_value, $this->_unit, 'kb');
    }

    public function setMegabytes($mb) {
        return $this->_parseUnit($mb, 'mb');
    }

    public function getMegabytes() {
        return $this->_convert($this->_value, $this->_unit, 'mb');
    }

    public function setGigabytes($gb) {
        return $this->_parseUnit($gb, 'gb');
    }

    public function getGigabytes() {
        return $this->_convert($this->_value, $this->_unit, 'gb');
    }

    public function setTerabytes($tb) {
        return $this->_parseUnit($tb, 'tb');
    }

    public function getTerabytes() {
        return $this->_convert($this->_value, $this->_unit, 'tb');
    }

    public function setPetabytes($pb) {
        return $this->_parseUnit($pb, 'pb');
    }

    public function getPetabytes() {
        return $this->_convert($this->_value, $this->_unit, 'pb');
    }


    protected function _convert($value, $inUnit, $outUnit) {
        if($inUnit === null) {
            $inUnit = self::DEFAULT_UNIT;
        }

        if($outUnit === null) {
            $outUnit = self::DEFAULT_UNIT;
        }

        if($inUnit == $outUnit) {
            return $value;
        }

        if($inUnit == 'bit') {
            $value /= 8;
            $inUnit = 'b';
        }

        switch($inUnit) {
            case 'b':
                $factor = 0;
                break;

            case 'kb':
                $factor = 1;
                break;

            case 'mb':
                $factor = 2;
                break;

            case 'gb':
                $factor = 3;
                break;

            case 'tb':
                $factor = 4;
                break;

            case 'pb':
                $factor = 5;
                break;
        }

        $bit = false;

        switch($outUnit) {
            case 'bit':
                $factor -= 0;
                $bit = true;
                break;

            case 'b':
                $factor -= 0;
                break;

            case 'kb':
                $factor -= 1;
                break;

            case 'mb':
                $factor -= 2;
                break;

            case 'gb':
                $factor -= 3;
                break;

            case 'tb':
                $factor -= 4;
                break;

            case 'pb':
                $factor -= 5;
                break;
        }

        $output = $value *= pow(1024, $factor);

        if($bit) {
            $output *= 8;
        }

        return $output;
    }

    public function toString(): string {
        $unit = $this->_unit;
        $value = $this->_value;

        if($unit == 'bit') {
            if($value <= 128) {
                return $value.'bit';
            }

            $unit = 'b';
            $value /= 8;
        }

        $key = array_search($unit, self::UNITS);

        while($value > 1024 && isset(self::UNITS[$key + 1])) {
            $unit = self::UNITS[++$key];
            $value /= 1024;
        }

        return $value.$unit;
    }

// Dump
    public function getDumpProperties() {
        return $this->toString();
    }
}
<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\raster\driver;

use df;
use df\core;
use df\neon;

abstract class Base implements neon\raster\IDriver {

    const READ_FORMATS = [];
    const WRITE_FORMATS = [];

    protected $_width;
    protected $_height;

    protected $_outputFormat = 'PNG32';
    protected $_pointer;

    public static function canRead($format) {
        return in_array($format, static::READ_FORMATS);
    }

    public static function canWrite($format) {
        return in_array($format, static::WRITE_FORMATS);
    }


    public function __construct($outputFormat=null) {
        if($outputFormat !== null) {
            $this->setOutputFormat($outputFormat);
        }
    }

    public function spawnInstance() {
        $class = get_class($this);
        return new $class($this->_outputFormat);
    }

    public function getName() {
        $parts = explode('\\', get_class($this));
        return array_pop($parts);
    }

    public function getWidth() {
        return $this->_width;
    }

    public function getHeight() {
        return $this->_height;
    }


    public function setOutputFormat($format) {
        $this->_outputFormat = $format;
        return $this;
    }

    public function getOutputFormat() {
        return $this->_outputFormat;
    }
}
<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\raster;

use df;
use df\core;
use df\neon;

class Transformation implements ITransformation {

    use core\TStringProvider;

    const KEYS = [
        'resize' => 'rs',
        'crop' => 'cr',
        'cropZoom' => 'cz',
        'frame' => 'fr',
        'rotate' => 'rt',
        'mirror' => 'mr',
        'flip' => 'fl',

        'brightness' => 'br',
        'contrast' => 'ct',
        'greyscale' => 'gs',
        'colorize' => 'cl',
        'invert' => 'iv',
        'detectEdges' => 'de',
        'emboss' => 'eb',
        'blur' => 'bl',
        'gaussianBlur' => 'gb',
        'removeMean' => 'rm',
        'smooth' => 'sm'
    ];

    const RESCALABLE = [
        'resize', 'cropZoom'
    ];

    protected $_image;
    protected $_transformations = [];

    public static function factory($transformation) {
        if($transformation instanceof ITransformation) {
            return $transformation;
        }

        return new self($transformation);
    }

    public function __construct(...$args) {
        foreach($args as $arg) {
            if($arg instanceof IImage) {
                $this->setImage($arg);
            } else if(is_string($arg)) {
                $this->_parseString($arg);
            }
        }
    }

    protected function _parseString($string) {
        preg_match_all('/\[([^]]+)\]/', $string, $matches);

        if(!isset($matches[1])) {
            return false;
        }

        $keys = array_flip(self::KEYS);

        foreach($matches[1] as $match) {
            $parts = explode(':', $match, 2);
            $key = strtolower(array_shift($parts));
            $argString = array_shift($parts);

            if(!isset($keys[$key])) {
                continue;
            }

            $method = $keys[$key];
            $args = [];

            if(strlen($argString)) {
                $args = explode('|', $argString);
            }

            $this->_addTransformation($method, $args);
        }
    }

    public function toString(): string {
        $output = '';

        foreach($this->_transformations as $callback) {
            $output .= '['.self::KEYS[$callback[0]];

            if(count($args = $callback[1])) {
                $output .= ':'.implode('|', $args);
            }

            $output .= ']';
        }

        return $output;
    }

    public function setImage(IImage $image) {
        $this->_image = $image;
        return $this;
    }

    public function getImage() {
        return $this->_image;
    }

    public function rescale($scale) {
        foreach($this->_transformations as $key => $set) {
            if(in_array($set[0], self::RESCALABLE)) {
                if(isset($set[1][0])) {
                    $set[1][0] *= $scale;
                }

                if(isset($set[1][1])) {
                    $set[1][1] *= $scale;
                }

                $this->_transformations[$key] = $set;
            }
        }

        return $this;
    }

    public function apply() {
        if(!$this->_image) {
            throw new RuntimeException(
                'No image has been set for transformation'
            );
        }

        foreach($this->_transformations as $callback) {
            if(method_exists($this->_image, $callback[0])) {
                $this->_image->{$callback[0]}(...$callback[1]);
            }
        }

        return $this->_image;
    }


// Manipulations
    public function resize($width, $height=null, $mode=IDimension::FIT) {
        return $this->_addTransformation('resize', [$width, $height, $mode]);
    }

    public function crop($x, $y, $width, $height) {
        return $this->_addTransformation('crop', [$x, $y, $width, $height]);
    }

    public function cropZoom($width, $height) {
        return $this->_addTransformation('cropZoom', [$width, $height]);
    }

    public function frame($width, $height=null, $color=null) {
        return $this->_addTransformation('frame', [$width, $height, $color]);
    }

    public function rotate($angle, $background=null) {
        return $this->_addTransformation('rotate', [$angle, $background]);
    }

    public function mirror() {
        return $this->_addTransformation('mirror');
    }

    public function flip() {
        return $this->_addTransformation('flip');
    }


// Filters
    public function brightness($brightness) {
        return $this->_addTransformation('brightness', [$brightness]);
    }

    public function contrast($contrast) {
        return $this->_addTransformation('contrast', [$contrast]);
    }

    public function greyscale() {
        return $this->_addTransformation('greyscale');
    }

    public function colorize($color, $alpha=100) {
        return $this->_addTransformation('colorize', [$color, $alpha]);
    }

    public function invert() {
        return $this->_addTransformation('invert');
    }

    public function detectEdges() {
        return $this->_addTransformation('detectEdges');
    }

    public function emboss() {
        return $this->_addTransformation('emboss');
    }

    public function blur() {
        return $this->_addTransformation('blur');
    }

    public function gaussianBlur() {
        return $this->_addTransformation('gaussianBlur');
    }

    public function removeMean() {
        return $this->_addTransformation('removeMean');
    }

    public function smooth($amount=50) {
        return $this->_addTransformation('smooth', [$smooth]);
    }


// Helpers
    protected function _addTransformation($method, array $args=[]) {
        $this->_transformations[] = [$method, $args];
        return $this;
    }
}
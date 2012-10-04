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

    protected static $_keys = array(
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
    );

    protected $_image;
    protected $_transformations = array();

    public function __construct() {
        foreach(func_get_args() as $arg) {
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
        
        $keys = array_flip(self::$_keys);
        
        foreach($matches[1] as $match) {
            $parts = explode(':', $match, 2);
            $key = strtolower(array_shift($parts));
            $argString = array_shift($parts);
            
            if(!isset($keys[$key])) {
                continue;    
            }
            
            $method = $keys[$key];
            $args = array();
            
            if(strlen($argString)) {
                $args = explode('|', $argString);    
            }
            
            $this->_addTransformation($method, $args);
        }
    }

    public function toString() {
        $output = '';
        
        foreach($this->_transformations as $callback) {
            $output .= '['.self::$_keys[$callback[0]];
            
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

    public function apply() {
        if(!$this->_image) {
            throw new RuntimeException(
                'No image has been set for transformation'
            );
        }

        foreach($this->_transformations as $callback) {
            if(method_exists($this->_image, $callback[0])) {
                call_user_func_array([$this->_image, $callback[0]], $callback[1]);
            }
        }

        return $this->_image;
    }


// Manipulations
    public function resize($width, $height, $mode=IDimension::FIT) {
        return $this->_addTransformation('resize', func_get_args());
    }

    public function crop($x, $y, $width, $height) {
        return $this->_addTransformation('crop', func_get_args());
    }

    public function cropZoom($width, $height) {
        return $this->_addTransformation('cropZoom', func_get_args());
    }

    public function frame($width, $height, $color=null) {
        return $this->_addTransformation('frame', func_get_args());
    }

    public function rotate($angle, $background=null) {
        return $this->_addTransformation('rotate', func_get_args());
    }

    public function mirror() {
        return $this->_addTransformation('mirror', func_get_args());
    }

    public function flip() {
        return $this->_addTransformation('flip', func_get_args());
    }


// Filters
    public function brightness($brightness) {
        return $this->_addTransformation('brightness', func_get_args());
    }

    public function contrast($contrast) {
        return $this->_addTransformation('contrast', func_get_args());
    }

    public function greyscale() {
        return $this->_addTransformation('greyscale', func_get_args());
    }

    public function colorize($color, $alpha=100) {
        return $this->_addTransformation('colorize', func_get_args());
    }

    public function invert() {
        return $this->_addTransformation('invert', func_get_args());
    }

    public function detectEdges() {
        return $this->_addTransformation('detectEdges', func_get_args());
    }

    public function emboss() {
        return $this->_addTransformation('emboss', func_get_args());
    }

    public function blur() {
        return $this->_addTransformation('blur', func_get_args());
    }

    public function gaussianBlur() {
        return $this->_addTransformation('gaussianBlur', func_get_args());
    }

    public function removeMean() {
        return $this->_addTransformation('removeMean', func_get_args());
    }

    public function smooth($amount=50) {
        return $this->_addTransformation('smooth', func_get_args());
    }


// Helpers
    protected function _addTransformation($method, array $args) {
        $this->_transformations[] = [$method, $args];    
        return $this;
    }
}
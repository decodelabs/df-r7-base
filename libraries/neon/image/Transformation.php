<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\image;

use df;
use df\core;
use df\neon;
    
class Transformation implements neon\IImageTransformation {

	use core\TStringProvider;

    protected static $_keys = array(
        'resize' => 'rs',
        'cropZoom' => 'cz',
        'frame' => 'fr',
        'watermark' => 'wm',
        'rotate' => 'rt',
        'crop' => 'cr',
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

    public function __construct($var) {
        $args = func_get_args();
        
        foreach($args as $arg) {
            if($arg instanceof neon\IImage) {
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


    public function setImage(neon\IImageProcessor $image) {
    	$this->_image = $image;
    	return $this;
    }

    public function getImage() {
    	return $this->_image;
    }

    public function apply() {
    	if(!$this->_image) {
    		throw new neon\RuntimeException(
    			'No image has been set for queue to transform'
			);
    	}

    	foreach($this->_transformations as $callback) {
    		call_user_func_array([$this->_image, $callback[0]], $callback[1]);
    	}

    	return $this->_image;
    }


// Processors
    public function resize($width, $height, $mode=neon\IImageProcessor::FIT) {
        $this->_addTransformation('resize', func_get_args());
        return $this;
    }

    public function cropZoom($width, $height) {
        $this->_addTransformation('cropZoom', func_get_args());
        return $this;
    }

    public function frame($width, $height, $color=null) {
        $this->_addTransformation('frame', func_get_args());
        return $this;
    }

    public function watermark($image, $position=neon\IImageProcessor::BOTTOM_RIGHT, $scaleFactor=1.0) {
        $this->_addTransformation('watermark', func_get_args());
        return $this;
    }

    public function rotate($angle, $background=null) {
        $this->_addTransformation('rotate', func_get_args());
        return $this;
    }

    public function crop($x, $y, $width, $height) {
        $this->_addTransformation('crop', func_get_args());
        return $this;
    }

    public function mirror() {
        $this->_addTransformation('mirror', func_get_args());
        return $this;
    }

    public function flip() {
        $this->_addTransformation('flip', func_get_args());
        return $this;
    }

    public function brightness($brightness) {
        $this->_addTransformation('brightness', func_get_args());
        return $this;
    }

    public function contrast($contrast) {
        $this->_addTransformation('contrast', func_get_args());
        return $this;
    }

    public function greyscale() {
        $this->_addTransformation('greyscale', func_get_args());
        return $this;
    }

    public function colorize($color, $alpha=100) {
        $this->_addTransformation('colorize', func_get_args());
        return $this;
    }

    public function invert() {
        $this->_addTransformation('invert', func_get_args());
        return $this;
    }

    public function detectEdges() {
        $this->_addTransformation('detectEdges', func_get_args());
        return $this;
    }

    public function emboss() {
        $this->_addTransformation('emboss', func_get_args());
        return $this;
    }

    public function blur() {
        $this->_addTransformation('blur', func_get_args());
        return $this;
    }

    public function gaussianBlur() {
        $this->_addTransformation('gaussianBlur', func_get_args());
        return $this;
    }

    public function removeMean() {
        $this->_addTransformation('removeMean', func_get_args());
        return $this;
    }

    public function smooth($amount=50) {
        $this->_addTransformation('smooth', func_get_args());
        return $this;
    }
    
    protected function _addTransformation($method, array $args) {
        $this->_transformations[] = [$method, $args];    
    }
}
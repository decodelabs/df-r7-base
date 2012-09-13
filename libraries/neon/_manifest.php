<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon;

use df;
use df\core;
use df\neon;
    
// Exceptions
interface IException {}

class RuntimeException extends \RuntimeException implements IException {}
class LogicException extends \LogicException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}


// Interfaces
interface IColor {

	const RGB = 'rgb';
    const HSL = 'hsl';
    const HSV = 'hsv';

    public function toHexString($allowShort=false);
    public function setHexPrefix($prefix);
    public function getHexPrefix();
    public function toCssString($allowRGBA=true);

// RGB
    public function setRgba($r, $g, $b, $a);
    public function setRgb($r, $g, $b);
    
// RGB Red
    public function setRed($r);
    public function getRed();
    
// RGB Green
    public function setGreen($g);
    public function getGreen();
    
// RGB Blue
    public function setBlue($b);
    public function getBlue();
    
// HSL
    public function setHsla($h, $s, $l, $a);
    public function setHsl($h, $s, $l);
    
// HSL Hue
    public function setHslHue($h);
    public function getHslHue();
    
// HSL Saturation
    public function setHslSaturation($s);
    public function getHslSaturation();
    
// HSL Lightness
    public function setHslLightness($l);
    public function getHslLightness();
    
// HSV
    public function setHsva($h, $s, $v, $a);
    public function setHsv($h, $s, $v);
    
// HSV Hue
    public function setHsvHue($h);
    public function getHsvHue();
    
// HSV Saturation
    public function setHsvSaturation($s);
    public function getHsvSaturation();
    
// HSV Value
    public function getHsvValue();
    public function setHsvValue($l);
    
// Alpha
    public function setAlpha($alpha);
    public function getAlpha();
    
// Modification
    public function add($color);
    public function subtract($color);
    
// Affect HSL
    public function affectHsl($h, $s, $l, $a=null);
    public function affectHslHue($h);
    public function affectHslSaturation($s);
    public function affectHslLightness($l);
    
// Affect HSV
    public function affectHsv($h, $s, $v, $a=null);
    public function affectHsvHue($h);
    public function affectHsvSaturation($s);
    public function affectHsvValue($v);
    public function affectAlpha($a);
    
// Tones
    public function affectContrast($amount);
    public function toMidtone($amount=1);
    public function contrastAgainst($color, $amount=0.5);
}


interface IImageProcessor {

    const PROPORTIONAL = 'p';
    const STRETCH = 's';
    const FIT = 'f';

    const TILE = 'tile';
    const TOP_LEFT = 'tl';
    const TOP_CENTER = 'tc';
    const TOP_RIGHT = 'tr';
    const MIDDLE_LEFT = 'ml';
    const MIDDLE_CENTER = 'mc';
    const MIDDLE_RIGHT = 'mr';
    const BOTTOM_LEFT = 'bl';
    const BOTTOM_CENTER = 'bc';
    const BOTTOM_RIGHT = 'br';

    public function resize($width, $height, $mode=IImage::FIT);
    public function cropZoom($width, $height);
    public function frame($width, $height, $color=null);
    public function watermark($image, $position=IImage::BOTTOM_RIGHT, $scaleFactor=1.0);
    public function rotate($angle, $background=null);
    public function crop($x, $y, $width, $height);
    public function mirror();
    public function flip();
    public function brightness($brightness);
    public function contrast($contrast);
    public function greyscale();
    public function colorize($color, $alpha=100);
    public function invert();
    public function detectEdges();
    public function emboss();
    public function blur();
    public function gaussianBlur();
    public function removeMean();
    public function smooth($amount=50);
}

interface IImageDrawingProcessor extends IImageProcessor {
    public function rectangleFill($x, $y, $width, $height, $color);
    public function gradientFill($orientation, $x, $y, $width, $height, array $colors);
}

interface IImage extends IImageProcessor {

    public static function isLoadable();
    public static function newCanvas($width, $height, $color=null);

    public function setSourcePath($sourcePath);
    public function getSourcePath();
    public function setTargetPath($targetPath);
    public function getTargetPath();
    public function isOpen();

    public function canRead($type, $extension);
    public function canWrite($type, $extension);
    public function getContentType();
    public function convertTo($type);

    public function transform($str=null);
    public function save($quality=100);
    public function toString($quality=100);
    public function copy(IImage $image, $destX, $destY);
}


interface IImageTransformation extends core\IStringProvider, IImageProcessor {

    public function setImage(IImageProcessor $image);
    public function getImage();

    public function apply();
}
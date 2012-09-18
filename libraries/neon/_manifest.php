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


interface IColorStop extends core\IStringProvider {

// Color
    public function setColor($color);
    public function getColor();

// Size
    public function setSize($size);
    public function getSize();
}
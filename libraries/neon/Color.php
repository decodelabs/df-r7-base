<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon;

use df;
use df\core;
use df\neon;

class Color implements IColor, core\IDumpable {

    protected $_a;
    protected $_b;
    protected $_c;
    protected $_alpha = 1.0;
    protected $_mode = null;
    protected $_hexPrefix = '#';

    public static function random($saturation=null, $lightness=null) {
        if($saturation === null) {
            $saturation = rand(1, 9) / 10;
        }

        if($lightness === null) {
            $lightness = rand(3, 8) / 10;
        }

        return new self(rand(0, 359), $saturation, $lightness, null, IColor::HSL);
    }

    public static function factory($color) {
        if($color instanceof self) {
            return $color;
        }

        if(is_string($color)) {
            return self::fromString($color);
        }

        if(is_array($color)) {
            return new self(
                array_shift($color),
                array_shift($color),
                array_shift($color),
                array_shift($color),
                IColor::RGB
            );
        }

        return new self(0, 0, 0);
    }

    public static function fromString($str) {
        if(!strlen($str)) {
            $str = 'black';
        }

        if(isset(self::$_colorNames[strtolower($str)])) {
            return self::fromName($str);
        }

        if(preg_match('@^(rgb|hsl|hsv)(a?)\((.*)\)@i', $str, $matches)) {
            $mode = $matches[1];
            $hasAlpha = $matches[2] == 'a';
            $args = explode(',', trim($matches[3]));

            $a = trim(array_shift($args));
            $b = trim(array_shift($args));
            $c = trim(array_shift($args));
            $alpha = $hasAlpha ? trim(array_shift($args)) : 1;

            switch($mode) {
                case IColor::RGB:
                    if(substr($a, -1) == '%') {
                        $a = trim($a, '%') / 100;
                    } else {
                        $a /= 255;
                    }

                    if(substr($b, -1) == '%') {
                        $b = trim($b, '%') / 100;
                    } else {
                        $b /= 255;
                    }

                    if(substr($c, -1) == '%') {
                        $c = trim($c, '%') / 100;
                    } else {
                        $c /= 255;
                    }

                    break;

                case IColor::HSL:
                case IColor::HSV:
                    $b = trim($b, '%') / 100;
                    $c = trim($c, '%') / 100;
                    break;
            }


            if(substr($alpha, -1) == '%') {
                $alpha = trim($alpha, '%') / 100;
            }

            return new self($a, $b, $c, $alpha, $mode);
        }

        return self::fromHex($str);
    }

    public static function fromName($name) {
        $name = strtolower($name);

        if(isset(self::$_colorNames[$name])) {
            return new self(
                self::$_colorNames[$name][0] / 255,
                self::$_colorNames[$name][1] / 255,
                self::$_colorNames[$name][2] / 255,
                isset(self::$_colorNames[$name][3]) ?
                    self::$_colorNames[$name][3] : 1
            );
        }

        throw new InvalidArgumentException('Color name '.$name.' is not recognized');
    }

    public static function isName($name) {
        return isset(self::$_colorNames[strtolower($name)]);
    }

    public static function fromHex($hex) {
        $hex = trim($hex);

        if(substr($hex, 0, 2) == '0x') {
            $hex = substr($hex, 2);
        } else {
            $hex = ltrim($hex, '#');
        }

        if(strlen($hex) == 6) {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        } else if(strlen($hex) == 3) {
            $r = substr($hex, 0, 1);
            $r = hexdec($r.$r);
            $g = substr($hex, 1, 1);
            $g = hexdec($g.$g);
            $b = substr($hex, 2, 1);
            $b = hexdec($b.$b);
        } else {
            throw new InvalidArgumentException('Invalid color '.$hex);
        }

        return new self($r / 255, $g / 255, $b / 255);
    }

    public function __construct($a, $b, $c, $alpha=null, $mode=null) {
        switch($mode) {
            case IColor::RGB:
                $this->setRgba($a, $b, $c, $alpha);
                break;

            case IColor::HSL:
                $this->setHsla($a, $b, $c, $alpha);
                break;

            case IColor::HSV:
                $this->setHsva($a, $b, $c, $alpha);
                break;

            default:
                $this->setRgba($a, $b, $c, $alpha);
                break;
        }
    }

    public function __get($member) {
        switch(strtolower($member)) {
            case 'red': return $this->getRed();
            case 'green': return $this->getGreen();
            case 'blue': return $this->getBlue();
            case 'alpha': return $this->getAlpha();

            case 'hue':
            case 'hslhue': return $this->getHslHue();
            case 'saturation':
            case 'hslsaturation': return $this->getHslSaturation();
            case 'lightness':
            case 'hsllightness': return $this->getHslLightness();

            case 'hsvhue': return $this->getHsvHue();
            case 'hsvsaturation': return $this->getHsvSaturation();
            case 'value':
            case 'hsvvalue': return $this->getHsvValue();
        }
    }

    // Convert
    public function setMode($mode) {
        if($mode == $this->_mode) {
            return $this;
        }

        switch($mode) {
            case IColor::RGB:
                $this->_toRgb();
                break;

            case IColor::HSL:
                $this->_toHsl();
                break;

            case IColor::HSV:
                $this->_toHsv();
                break;

            default: return $this;
        }

        return $this;
    }

// To RGB
    protected function _toRgb() {
        if($this->_mode == IColor::HSL) {
            $this->_hslToRgb();
        } else {
            $this->_hsvToRgb();
        }
    }

    protected function _hslToRgb() {
        $h = $this->_a / 360;
        $s = $this->_b;
        $l = $this->_c;

        $m2 = ($l <= 0.5) ? $l * ($s + 1) : $l + $s - $l * $s;
        $m1 = $l * 2 - $m2;

        $this->_mode = IColor::RGB;
        $this->setRed(self::_hslHueToRgb($m1, $m2, $h + 0.33333));
        $this->setGreen(self::_hslHueToRgb($m1, $m2, $h));
        $this->setBlue(self::_hslHueToRgb($m1, $m2, $h - 0.33333));
    }

    protected static function _hslHueToRgb($m1, $m2, $h) {
        $h = ($h < 0) ? $h + 1 : (($h > 1) ? $h - 1 : $h);

        if($h * 6 < 1) {
            return $m1 + ($m2 - $m1) * $h * 6;
        }

        if($h * 2 < 1) {
            return $m2;
        }

        if($h * 3 < 2) {
            return $m1 + ($m2 - $m1) * (0.66666 - $h) * 6;
        }

        return $m1;
    }

    protected function _hsvToRgb() {
        core\stub('HSV to RGB is not yet supported');
    }

// To HSL
    protected function _toHsl() {
        if($this->_mode == IColor::HSV) {
            $this->_hsvToRgb();
        }

        $r = $this->_a;
        $g = $this->_b;
        $b = $this->_c;

        $min = min($r, min($g, $b));
        $max = max($r, max($g, $b));
        $delta = $max - $min;
        $l = ($min + $max) / 2;
        $s = 0;

        if($l > 0 && $l < 1) {
            $s = $delta / ($l < 0.5 ? (2 * $l) : (2 - 2 * $l));
        }

        $h = 0;
        if($delta > 0) {
            if($max == $r && $max != $g) $h += ($g - $b) / $delta;
            if($max == $g && $max != $b) $h += (2 + ($b - $r) / $delta);
            if($max == $b && $max != $r) $h += (4 + ($r - $g) / $delta);
            $h /= 6;
        }

        $this->_mode = IColor::HSL;
        $this->setHslHue($h * 360);
        $this->setHslSaturation($s);
        $this->setHslLightness($l);
    }

// To HSV
    protected function _toHsv() {
        if($this->_mode == IColor::HSL) {
            $this->_hslToRgb();
        }

        $r = $this->_a * 255;
        $g = $this->_b * 255;
        $b = $this->_c * 255;

        $minVal = min($r, $g, $b);
        $maxVal = max($r, $g, $b);
        $delta  = $maxVal - $minVal;

        $v = $maxVal / 255;

        if ($delta == 0) {
            $h = 0;
            $s = 0;
        } else {
            $s = $delta / $maxVal;
            $del_R = ((($maxVal - $r) / 6) + ($delta / 2)) / $delta;
            $del_G = ((($maxVal - $g) / 6) + ($delta / 2)) / $delta;
            $del_B = ((($maxVal - $b) / 6) + ($delta / 2)) / $delta;

            if ($r == $maxVal){
                $h = $del_B - $del_G;
            } else if ($g == $maxVal) {
                $h = (1 / 3) + $del_R - $del_B;
            } else if ($b == $maxVal) {
                $h = (2 / 3) + $del_G - $del_R;
            }

            if ($h < 0){
                $h++;
            }
            if ($h > 1) {
                $h--;
            }
        }

        $this->setHsvHue($h * 360);
        $this->setHsvSaturation($s);
        $this->setHsvValue($v);
    }





// Export
    public function toHexString($allowShort=false) {
        if($this->_mode != IColor::RGB) {
            $this->setMode(IColor::RGB);
        }

        $r = dechex($this->_a * 255);
        $g = dechex($this->_b * 255);
        $b = dechex($this->_c * 255);

        if(strlen($r) == 1) $r = '0'.$r;
        if(strlen($g) == 1) $g = '0'.$g;
        if(strlen($b) == 1) $b = '0'.$b;

        if($allowShort
        && $r{0} == $r{1}
        && $g{0} == $g{1}
        && $b{0} == $b{1}) {
            $r = $r{0};
            $g = $g{0};
            $b = $b{0};
        }

        return $this->_hexPrefix.$r.$g.$b;
    }

    public function setHexPrefix($prefix) {
        $this->_hexPrefix = $prefix;
        return $this;
    }

    public function getHexPrefix() {
        return $this->_hexPrefix;
    }

    public function toCssString($allowRGBA=true) {
        $this->setMode(IColor::RGB);

        if($allowRGBA && $this->_alpha < 1) {
            return 'rgba('.
                        round($this->_a * 255).', '.
                        round($this->_b * 255).', '.
                        round($this->_c * 255).', '.
                        $this->_alpha.
                    ')';
        }

        return $this->toHexString(true);
    }

    public function __toString() {
        try {
            return $this->toCssString();
        } catch(\Exception $e) {
            return '';
        } catch(\Error $e) {
            return '';
        }
    }




// RGB
    public function setRgba($r, $g, $b, $a) {
        $this->_mode = IColor::RGB;

        $this->setRed($r);
        $this->setGreen($g);
        $this->setBlue($b);
        $this->setAlpha($a);

        return $this;
    }

    public function setRgb($r, $g, $b) {
        return $this->setRgba($r, $g, $b, 1.0);
    }

// RGB Red
    public function setRed($r) {
        if($this->_mode != IColor::RGB) {
            $this->setMode(IColor::RGB);
        }

        $this->_a = (float)$r;

        //if($this->_a < 0) $this->_a = 0;
        //if($this->_a > 1.0) $this->_a = 1.0;

        return $this;
    }

    public function getRed() {
        if($this->_mode != IColor::RGB) {
            $this->setMode(IColor::RGB);
        }

        return $this->_a;
    }

// RGB Green
    public function setGreen($g) {
        if($this->_mode != IColor::RGB) {
            $this->setMode(IColor::RGB);
        }

        $this->_b = (float)$g;

        //if($this->_b < 0) $this->_b = 0;
        //if($this->_b > 1.0) $this->_b = 1.0;

        return $this;
    }

    public function getGreen() {
        if($this->_mode != IColor::RGB) {
            $this->setMode(IColor::RGB);
        }

        return $this->_b;
    }

// RGB Blue
    public function setBlue($b) {
        if($this->_mode != IColor::RGB) {
            $this->setMode(IColor::RGB);
        }

        $this->_c = (float)$b;

        //if($this->_c < 0) $this->_c = 0;
        //if($this->_c > 1.0) $this->_c = 1.0;

        return $this;
    }

    public function getBlue() {
        if($this->_mode != IColor::RGB) {
            $this->setMode(IColor::RGB);
        }

        return $this->_c;
    }


// HSL
    public function setHsla($h, $s, $l, $a) {
        $this->_mode = IColor::HSL;

        $this->setHslHue($h);
        $this->setHslSaturation($s);
        $this->setHslLightness($l);
        $this->setAlpha($a);

        return $this;
    }

    public function setHsl($h, $s, $l) {
        return $this->setHsla($h, $s, $l, 1.0);
    }

// HSL Hue
    public function setHslHue($h) {
        if($this->_mode != IColor::HSL) {
            $this->setMode(IColor::HSL);
        }

        $this->_a = (float)$h;

        while($this->_a < 0) {
            $this->_a += 360;
        }

        while($this->_a > 359) {
            $this->_a -= 360;
        }

        return $this;
    }

    public function getHslHue() {
        if($this->_mode != IColor::HSL) {
            $this->setMode(IColor::HSL);
        }

        return $this->_a;
    }

// HSL Saturation
    public function setHslSaturation($s) {
        if($this->_mode != IColor::HSL) {
            $this->setMode(IColor::HSL);
        }

        $this->_b = (float)$s;

        if($this->_b < 0) $this->_b = 0;
        if($this->_b > 1.0) $this->_b = 1.0;

        return $this;
    }

    public function getHslSaturation() {
        if($this->_mode != IColor::HSL) {
            $this->setMode(IColor::HSL);
        }

        return $this->_b;
    }

// HSL Lightness
    public function setHslLightness($l) {
        if($this->_mode != IColor::HSL) {
            $this->setMode(IColor::HSL);
        }

        $this->_c = (float)$l;

        if($this->_c < 0) $this->_c = 0;
        if($this->_c > 1.0) $this->_c = 1.0;

        return $this;
    }

    public function getHslLightness() {
        if($this->_mode != IColor::HSL) {
            $this->setMode(IColor::HSL);
        }

        return $this->_c;
    }


// HSV
    public function setHsva($h, $s, $v, $a) {
        $this->_mode = IColor::HSV;

        $this->setHsvHue($h);
        $this->setHsvSaturation($s);
        $this->setHsvValue($v);
        $this->setAlpha($alpha);

        return $this;
    }

    public function setHsv($h, $s, $v) {
        return $this->setHsva($h, $s, $v, 1.0);
    }

// HSV Hue
    public function setHsvHue($h) {
        if($this->_mode != IColor::HSV) {
            $this->setMode(IColor::HSV);
        }

        $this->_a = (float)$h;

        while($this->_a < 0) {
            $this->_a += 360;
        }

        while($this->_a > 359) {
            $this->_a -= 360;
        }

        return $this;
    }

    public function getHsvHue() {
        if($this->_mode != IColor::HSV) {
            $this->setMode(IColor::HSV);
        }

        return $this->_a;
    }

// HSV Saturation
    public function setHsvSaturation($s) {
        if($this->_mode != IColor::HSV) {
            $this->setMode(IColor::HSV);
        }

        $this->_b = (float)$g;

        if($this->_b < 0) $this->_b = 0;
        if($this->_b > 1.0) $this->_b = 1.0;

        return $this;
    }

    public function getHsvSaturation() {
        if($this->_mode != IColor::HSV) {
            $this->setMode(IColor::HSV);
        }

        return $this->_b;
    }

// HSV Value
    public function getHsvValue() {
        if($this->_mode != IColor::HSV) {
            $this->setMode(IColor::HSV);
        }

        return $this->_c;
    }

    public function setHsvValue($l) {
        if($this->_mode != IColor::HSV) {
            $this->setMode(IColor::HSV);
        }

        $this->_b = (float)$g;

        if($this->_b < 0) $this->_b = 0;
        if($this->_b > 1.0) $this->_b = 1.0;

        return $this;
    }


// Alpha
    public function setAlpha($alpha) {
        if($alpha === null || $alpha === false) {
            $alpha = 1.0;
        }

        $this->_alpha = (float)$alpha;

        if($this->_alpha < 0) {
            $this->_alpha = 0;
        }

        if($this->_alpha > 1.0) {
            $this->_alpha = 1.0;
        }

        return $this;
    }

    public function getAlpha() {
        return $this->_alpha;
    }


// Modification
    public function add($color) {
        $this->setMode(IColor::RGB);
        $color = self::factory($color)
            ->setMode(IColor::RGB);

        $this->setRed($this->_a + $color->_a);
        $this->setGreen($this->_b + $color->_b);
        $this->setBlue($this->_c + $color->_c);

        return $this;
    }

    public function subtract($color) {
        $this->setMode(IColor::RGB);
        $color = self::factory($color)
            ->setMode(IColor::RGB);

        $this->setRed($this->_a - $color->_a);
        $this->setGreen($this->_b - $color->_b);
        $this->setBlue($this->_c - $color->_c);

        return $this;
    }

// Affect HSL
    public function affectHsl($h, $s, $l, $a=null) {
        $this->setMode(IColor::HSL);

        $this->setHslHue($this->_a + (float)$h);
        $this->setHslSaturation($this->_b + (float)$s);
        $this->setHslLightness($this->_c + (float)$l);

        if($a !== null) {
            $this->affectAlpha($a);
        }

        return $this;
    }

    public function affectHslHue($h) {
        $this->setMode(IColor::HSL);
        $this->setHslHue($this->_a + (float)$h);

        return $this;
    }

    public function affectHslSaturation($s) {
        $this->setMode(IColor::HSL);
        $this->setHslSaturation($this->_b + (float)$s);

        return $this;
    }

    public function affectHslLightness($l) {
        $this->setMode(IColor::HSL);
        $this->setHslLightness($this->_c + (float)$l);

        return $this;
    }

// Affect HSV
    public function affectHsv($h, $s, $v, $a=null) {
        $this->setMode(IColor::HSV);

        $this->setHsvHue($this->_a + (float)$h);
        $this->setHsvSaturation($this->_b + (float)$s);
        $this->setHsvValue($this->_c + (float)$v);

        if($a !== null) {
            $this->affectAlpha($a);
        }

        return $this;
    }

    public function affectHsvHue($h) {
        $this->setMode(IColor::HSV);
        $this->setHsvHue($this->_a + (float)$h);

        return $this;
    }

    public function affectHsvSaturation($s) {
        $this->setMode(IColor::HSV);
        $this->setHsvSaturation($this->_b + (float)$s);

        return $this;
    }

    public function affectHsvValue($v) {
        $this->setMode(IColor::HSV);
        $this->setHsvValue($this->_c + (float)$v);

        return $this;
    }


    public function affectAlpha($a) {
        return $this->setAlpha($this->_alpha + (float)$a);
    }



// Tones
    public function affectContrast($amount) {
        $this->setMode(IColor::HSL);

        if($amount > 1) {
            $amount = 1;
        } else if($amount < -1) {
            $amount = -1;
        }

        $ratio = $this->_c - 0.5;
        return $this->setHslLightness(($ratio * $amount) + 0.5);
    }

    public function toMidtone($amount=1) {
        $this->setMode(IColor::HSL);
        $delta = $this->_c - 0.5;

        return $this->setHslLightness($this->_c - ($delta * $amount));
    }

    public function contrastAgainst($color, $amount=0.5) {
        $this->setMode(IColor::RGB);
        $color = self::factory($color)
            ->setMode(IColor::RGB);

        $delta1 = $this->_c - 0.5;
        $delta2 = $color->_c - 0.5;

        if($delta2 < 0 && $delta1 < $delta2 + $amount) {
            $delta1 = $delta2 + $amount;
        } else if($delta2 > 0 && $delta1 > $delta2 - $amount) {
            $delta1 = $delta2 - $amount;
        }

        return $this->setHslLightness($delta1 + 0.5);
    }

    public function getTextContrastColor() {
        $this->setMode(IColor::HSL);

        if($this->_c > 0.8) {
            return self::factory('black');
        } else {
            return self::factory('white');
        }
    }




// Preset colors
    protected static $_colorNames = [
        'aliceblue'             => [240, 248, 255],
        'antiquewhite'          => [250, 235, 215],
        'aqua'                  => [0,   255, 255],
        'aquamarine'            => [127, 255, 212],
        'azure'                 => [240, 255, 255],
        'beige'                 => [245, 245, 220],
        'bisque'                => [255, 228, 196],
        'black'                 => [0,   0,   0],
        'blanchedalmond'        => [255, 235, 205],
        'blue'                  => [0,   0,   255],
        'blueviolet'            => [138, 43,  226],
        'brown'                 => [165, 42,  42],
        'burlywood'             => [222, 184, 135],
        'cadetblue'             => [95,  158, 160],
        'chartreuse'            => [127, 255, 0],
        'chocolate'             => [210, 105, 30],
        'coral'                 => [255, 127, 80],
        'cornflowerblue'        => [100, 149, 237],
        'cornsilk'              => [255, 248, 220],
        'crimson'               => [220, 20,  60],
        'cyan'                  => [0,   255, 255],
        'darkblue'              => [0,   0,   13],
        'darkcyan'              => [0,   139, 139],
        'darkgoldenrod'         => [184, 134, 11],
        'darkgray'              => [169, 169, 169],
        'darkgreen'             => [0,   100, 0],
        'darkkhaki'             => [189, 183, 107],
        'darkmagenta'           => [139, 0,   139],
        'darkolivegreen'        => [85,  107, 47],
        'darkorange'            => [255, 140, 0],
        'darkorchid'            => [153, 50,  204],
        'darkred'               => [139, 0,   0],
        'darksalmon'            => [233, 150, 122],
        'darkseagreen'          => [143, 188, 143],
        'darkslateblue'         => [72,  61,  139],
        'darkslategray'         => [47,  79,  79],
        'darkturquoise'         => [0,   206, 209],
        'darkviolet'            => [148, 0,   211],
        'deeppink'              => [255, 20,  147],
        'deepskyblue'           => [0,   191, 255],
        'dimgray'               => [105, 105, 105],
        'dodgerblue'            => [30,  144, 255],
        'firebrick'             => [178, 34,  34],
        'floralwhite'           => [255, 250, 240],
        'forestgreen'           => [34,  139, 34],
        'fuchsia'               => [255, 0,   255],
        'gainsboro'             => [220, 220, 220],
        'ghostwhite'            => [248, 248, 255],
        'gold'                  => [255, 215, 0],
        'goldenrod'             => [218, 165, 32],
        'gray'                  => [128, 128, 128],
        'green'                 => [0,   128, 0],
        'greenyellow'           => [173, 255, 47],
        'honeydew'              => [240, 255, 240],
        'hotpink'               => [255, 105, 180],
        'indianred'             => [205, 92,  92],
        'indigo'                => [75,  0,   130],
        'ivory'                 => [255, 255, 240],
        'khaki'                 => [240, 230, 140],
        'lavender'              => [230, 230, 250],
        'lavenderblush'         => [255, 240, 245],
        'lawngreen'             => [124, 252,  0],
        'lemonchiffon'          => [255, 250, 205],
        'lightblue'             => [173, 216, 230],
        'lightcoral'            => [240, 128, 128],
        'lightcyan'             => [224, 255, 255],
        'lightgoldenrodyellow'  => [250, 250, 210],
        'lightgreen'            => [144, 238, 144],
        'lightgrey'             => [211, 211, 211],
        'lightpink'             => [255, 182, 193],
        'lightsalmon'           => [255, 160, 122],
        'lightseagreen'         => [32, 178, 170],
        'lightskyblue'          => [135, 206, 250],
        'lightslategray'        => [119, 136, 153],
        'lightsteelblue'        => [176, 196, 222],
        'lightyellow'           => [255, 255, 224],
        'lime'                  => [0,   255, 0],
        'limegreen'             => [50,  205, 50],
        'linen'                 => [250, 240, 230],
        'magenta'               => [255, 0,   255],
        'maroon'                => [128, 0,   0],
        'mediumaquamarine'      => [102, 205, 170],
        'mediumblue'            => [0,   0,   205],
        'mediumorchid'          => [186, 85,  211],
        'mediumpurple'          => [147, 112, 219],
        'mediumseagreen'        => [60,  179, 113],
        'mediumslateblue'       => [123, 104, 238],
        'mediumspringgreen'     => [0,   250, 154],
        'mediumturquoise'       => [72,  209, 204],
        'mediumvioletred'       => [199, 21,  133],
        'midnightblue'          => [25,  25,  112],
        'mintcream'             => [245, 255, 250],
        'mistyrose'             => [255, 228, 225],
        'moccasin'              => [255, 228, 181],
        'navajowhite'           => [255, 222, 173],
        'navy'                  => [0,   0,   128],
        'oldlace'               => [253, 245, 230],
        'olive'                 => [128, 128, 0],
        'olivedrab'             => [107, 142, 35],
        'orange'                => [255, 165, 0],
        'orangered'             => [255, 69,  0],
        'orchid'                => [218, 112, 214],
        'palegoldenrod'         => [238, 232, 170],
        'palegreen'             => [152, 251, 152],
        'paleturquoise'         => [175, 238, 238],
        'palevioletred'         => [219, 112, 147],
        'papayawhip'            => [255, 239, 213],
        'peachpuff'             => [255, 218, 185],
        'peru'                  => [205, 133, 63],
        'pink'                  => [255, 192, 203],
        'plum'                  => [221, 160, 221],
        'powderblue'            => [176, 224, 230],
        'purple'                => [128, 0,   128],
        'red'                   => [255, 0,   0],
        'rosybrown'             => [188, 143, 143],
        'royalblue'             => [65,  105, 225],
        'saddlebrown'           => [139, 69,  19],
        'salmon'                => [250, 128, 114],
        'sandybrown'            => [244, 164, 96],
        'seagreen'              => [46,  139, 87],
        'seashell'              => [255, 245, 238],
        'sienna'                => [160, 82,  45],
        'silver'                => [192, 192, 192],
        'skyblue'               => [135, 206, 235],
        'slateblue'             => [106, 90,  205],
        'slategray'             => [112, 128, 144],
        'snow'                  => [255, 250, 250],
        'springgreen'           => [0,   255, 127],
        'steelblue'             => [70,  130, 180],
        'tan'                   => [210, 180, 140],
        'teal'                  => [0,   128, 128],
        'thistle'               => [216, 191, 216],
        'tomato'                => [255, 99,  71],
        'turquoise'             => [64,  224, 208],
        'violet'                => [238, 130, 238],
        'wheat'                 => [245, 222, 179],
        'white'                 => [255, 255, 255],
        'whitesmoke'            => [245, 245, 245],
        'yellow'                => [255, 255, 0],
        'yellowgreen'           => [154, 205, 50],
        'transparent'           => [0,   0,   0,   0]
    ];


// Dump
    public function getDumpProperties() {
        return $this->toCssString();
    }
}

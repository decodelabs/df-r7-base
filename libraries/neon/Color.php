<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon;

use df;
use df\core;
use df\neon;

use DecodeLabs\Glitch;
use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class Color implements IColor, Inspectable
{
    protected $_a;
    protected $_b;
    protected $_c;
    protected $_alpha = 1.0;
    protected $_mode = null;


    // Factories
    public static function random(float $saturation=null, float $lightness=null): IColor
    {
        if ($saturation === null) {
            $saturation = rand(1, 9) / 10;
        }

        if ($lightness === null) {
            $lightness = rand(3, 8) / 10;
        }

        return new self(rand(0, 359), $saturation, $lightness, null, IColor::HSL);
    }

    public static function factory($color): IColor
    {
        if ($color instanceof self) {
            return $color;
        }

        if (is_string($color)) {
            return self::fromString($color);
        }

        if (is_array($color)) {
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

    public static function fromString(string $str): IColor
    {
        if (!strlen($str)) {
            $str = 'black';
        }

        if (isset(self::NAMES[strtolower($str)])) {
            return self::fromName($str);
        }

        if (preg_match('@^(rgb|hsl|hsv)(a?)\((.*)\)@i', $str, $matches)) {
            $mode = $matches[1];
            $hasAlpha = $matches[2] == 'a';
            $args = explode(',', trim($matches[3]));

            $a = trim((string)array_shift($args));
            $b = trim((string)array_shift($args));
            $c = trim((string)array_shift($args));
            $alpha = $hasAlpha ? trim((string)array_shift($args)) : '1';

            switch ($mode) {
                case IColor::RGB:
                    if (substr($a, -1) == '%') {
                        $a = trim($a, '%') / 100;
                    } else {
                        $a /= 255;
                    }

                    if (substr($b, -1) == '%') {
                        $b = trim($b, '%') / 100;
                    } else {
                        $b /= 255;
                    }

                    if (substr($c, -1) == '%') {
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


            if (substr($alpha, -1) == '%') {
                $alpha = trim($alpha, '%') / 100;
            }

            return new self($a, $b, $c, $alpha, $mode);
        }

        return self::fromHex($str);
    }

    public static function fromName(string $name): IColor
    {
        $name = strtolower($name);

        if (isset(self::NAMES[$name])) {
            return new self(
                self::NAMES[$name][0] / 255,
                self::NAMES[$name][1] / 255,
                self::NAMES[$name][2] / 255,
                self::NAMES[$name][3] ?? 1
            );
        }

        throw Glitch::{'EInvalidArgument,EColor'}('Color name '.$name.' is not recognized');
    }

    public static function isValidName(string $name): bool
    {
        return isset(self::NAMES[strtolower($name)]);
    }

    public static function fromHex(string $hex): IColor
    {
        $hex = trim($hex);

        if (substr($hex, 0, 2) == '0x') {
            $hex = substr($hex, 2);
        } else {
            $hex = ltrim($hex, '#');
        }

        if (strlen($hex) == 6) {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        } elseif (strlen($hex) == 3) {
            $r = substr($hex, 0, 1);
            $r = hexdec($r.$r);
            $g = substr($hex, 1, 1);
            $g = hexdec($g.$g);
            $b = substr($hex, 2, 1);
            $b = hexdec($b.$b);
        } else {
            throw Glitch::{'EInvalidArgument,EColor'}('Invalid color '.$hex);
        }

        return new self($r / 255, $g / 255, $b / 255);
    }



    // Construct
    public function __construct($a, $b, $c, $alpha=null, $mode=null)
    {
        switch ($mode) {
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

    public function __get($member)
    {
        switch (strtolower($member)) {
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
    public function setMode(string $mode)
    {
        if ($mode == $this->_mode) {
            return $this;
        }

        switch ($mode) {
            case IColor::RGB:
                $this->_toRgb();
                break;

            case IColor::HSL:
                $this->_toHsl();
                break;

            case IColor::HSV:
                $this->_toHsv();
                break;

            default:
                throw Glitch::EInvalidArgument([
                    'message' => 'Invalid color mode',
                    'data' => $mode
                ]);
        }

        return $this;
    }



    // To RGB
    protected function _toRgb()
    {
        if ($this->_mode == IColor::HSL) {
            $this->_hslToRgb();
        } else {
            $this->_hsvToRgb();
        }
    }

    protected function _hslToRgb()
    {
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

    protected static function _hslHueToRgb($m1, $m2, $h)
    {
        $h = ($h < 0) ? $h + 1 : (($h > 1) ? $h - 1 : $h);

        if ($h * 6 < 1) {
            return $m1 + ($m2 - $m1) * $h * 6;
        }

        if ($h * 2 < 1) {
            return $m2;
        }

        if ($h * 3 < 2) {
            return $m1 + ($m2 - $m1) * (0.66666 - $h) * 6;
        }

        return $m1;
    }

    protected function _hsvToRgb()
    {
        Glitch::incomplete('HSV to RGB is not yet supported');
    }

    // To HSL
    protected function _toHsl()
    {
        if ($this->_mode == IColor::HSV) {
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

        if ($l > 0 && $l < 1) {
            $s = $delta / ($l < 0.5 ? (2 * $l) : (2 - 2 * $l));
        }

        $h = 0;
        if ($delta > 0) {
            if ($max == $r && $max != $g) {
                $h += ($g - $b) / $delta;
            }
            if ($max == $g && $max != $b) {
                $h += (2 + ($b - $r) / $delta);
            }
            if ($max == $b && $max != $r) {
                $h += (4 + ($r - $g) / $delta);
            }
            $h /= 6;
        }

        $this->_mode = IColor::HSL;
        $this->setHslHue($h * 360);
        $this->setHslSaturation($s);
        $this->setHslLightness($l);
    }

    // To HSV
    protected function _toHsv()
    {
        if ($this->_mode == IColor::HSL) {
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

            if ($r == $maxVal) {
                $h = $del_B - $del_G;
            } elseif ($g == $maxVal) {
                $h = (1 / 3) + $del_R - $del_B;
            } elseif ($b == $maxVal) {
                $h = (2 / 3) + $del_G - $del_R;
            } else {
                $h = 0;
            }

            if ($h < 0) {
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
    public function toHexString(bool $allowShort=false): string
    {
        if ($this->_mode != IColor::RGB) {
            $this->setMode(IColor::RGB);
        }

        $r = dechex($this->_a * 255);
        $g = dechex($this->_b * 255);
        $b = dechex($this->_c * 255);

        if (strlen($r) == 1) {
            $r = '0'.$r;
        }
        if (strlen($g) == 1) {
            $g = '0'.$g;
        }
        if (strlen($b) == 1) {
            $b = '0'.$b;
        }

        if ($allowShort
        && $r[0] == $r[1]
        && $g[0] == $g[1]
        && $b[0] == $b[1]) {
            $r = $r[0];
            $g = $g[0];
            $b = $b[0];
        }

        return '#'.$r.$g.$b;
    }

    public function toCssString(): string
    {
        $this->setMode(IColor::RGB);

        if ($this->_alpha < 1) {
            return 'rgba('.
                round($this->_a * 255).', '.
                round($this->_b * 255).', '.
                round($this->_c * 255).', '.
                $this->_alpha.
            ')';
        }

        return $this->toHexString(false);
    }

    public function __toString(): string
    {
        try {
            return $this->toCssString();
        } catch (\Throwable $e) {
            return '';
        }
    }




    // RGB
    public function setRgba(float $r, float $g, float $b, float $a=null)
    {
        $this->_mode = IColor::RGB;

        $this->setRed($r);
        $this->setGreen($g);
        $this->setBlue($b);
        $this->setAlpha($a);

        return $this;
    }

    public function setRgb(float $r, float $g, float $b)
    {
        return $this->setRgba($r, $g, $b, 1.0);
    }

    // RGB Red
    public function setRed(float $r)
    {
        if ($this->_mode != IColor::RGB) {
            $this->setMode(IColor::RGB);
        }

        $this->_a = core\math\Util::clampFloat($r, 0, 1);
        return $this;
    }

    public function getRed(): float
    {
        if ($this->_mode != IColor::RGB) {
            $this->setMode(IColor::RGB);
        }

        return (float)$this->_a;
    }

    // RGB Green
    public function setGreen(float $g)
    {
        if ($this->_mode != IColor::RGB) {
            $this->setMode(IColor::RGB);
        }

        $this->_b = core\math\Util::clampFloat($g, 0, 1);
        return $this;
    }

    public function getGreen(): float
    {
        if ($this->_mode != IColor::RGB) {
            $this->setMode(IColor::RGB);
        }

        return (float)$this->_b;
    }

    // RGB Blue
    public function setBlue(float $b)
    {
        if ($this->_mode != IColor::RGB) {
            $this->setMode(IColor::RGB);
        }

        $this->_c = core\math\Util::clampFloat($b, 0, 1);
        return $this;
    }

    public function getBlue(): float
    {
        if ($this->_mode != IColor::RGB) {
            $this->setMode(IColor::RGB);
        }

        return (float)$this->_c;
    }


    // HSL
    public function setHsla(float $h, float $s, float $l, float $a=null)
    {
        $this->_mode = IColor::HSL;

        $this->setHslHue($h);
        $this->setHslSaturation($s);
        $this->setHslLightness($l);
        $this->setAlpha($a);

        return $this;
    }

    public function setHsl(float $h, float $s, float $l)
    {
        return $this->setHsla($h, $s, $l, 1.0);
    }

    // HSL Hue
    public function setHslHue(float $h)
    {
        if ($this->_mode != IColor::HSL) {
            $this->setMode(IColor::HSL);
        }

        $this->_a = core\math\Util::clampDegrees($h);
        return $this;
    }

    public function getHslHue(): float
    {
        if ($this->_mode != IColor::HSL) {
            $this->setMode(IColor::HSL);
        }

        return (float)$this->_a;
    }

    // HSL Saturation
    public function setHslSaturation(float $s)
    {
        if ($this->_mode != IColor::HSL) {
            $this->setMode(IColor::HSL);
        }

        $this->_b = core\math\Util::clampFloat($s, 0, 1);
        return $this;
    }

    public function getHslSaturation(): float
    {
        if ($this->_mode != IColor::HSL) {
            $this->setMode(IColor::HSL);
        }

        return (float)$this->_b;
    }

    // HSL Lightness
    public function setHslLightness(float $l)
    {
        if ($this->_mode != IColor::HSL) {
            $this->setMode(IColor::HSL);
        }

        $this->_c = core\math\Util::clampFloat($l, 0, 1);
        return $this;
    }

    public function getHslLightness(): float
    {
        if ($this->_mode != IColor::HSL) {
            $this->setMode(IColor::HSL);
        }

        return (float)$this->_c;
    }


    // HSV
    public function setHsva(float $h, float $s, float $v, float $a=null)
    {
        $this->_mode = IColor::HSV;

        $this->setHsvHue($h);
        $this->setHsvSaturation($s);
        $this->setHsvValue($v);
        $this->setAlpha($a);

        return $this;
    }

    public function setHsv(float $h, float $s, float $v)
    {
        return $this->setHsva($h, $s, $v, 1.0);
    }


    // HSV Hue
    public function setHsvHue(float $h)
    {
        if ($this->_mode != IColor::HSV) {
            $this->setMode(IColor::HSV);
        }

        $this->_a = core\math\Util::clampDegrees($h);
        return $this;
    }

    public function getHsvHue(): float
    {
        if ($this->_mode != IColor::HSV) {
            $this->setMode(IColor::HSV);
        }

        return (float)$this->_a;
    }

    // HSV Saturation
    public function setHsvSaturation(float $s)
    {
        if ($this->_mode != IColor::HSV) {
            $this->setMode(IColor::HSV);
        }

        $this->_b = core\math\Util::clampFloat($s, 0, 1);
        return $this;
    }

    public function getHsvSaturation(): float
    {
        if ($this->_mode != IColor::HSV) {
            $this->setMode(IColor::HSV);
        }

        return (float)$this->_b;
    }

    // HSV Value
    public function setHsvValue(float $l)
    {
        if ($this->_mode != IColor::HSV) {
            $this->setMode(IColor::HSV);
        }

        $this->_b = core\math\Util::clampFloat($l, 0, 1);
        return $this;
    }

    public function getHsvValue(): float
    {
        if ($this->_mode != IColor::HSV) {
            $this->setMode(IColor::HSV);
        }

        return (float)$this->_c;
    }



    // Alpha
    public function setAlpha(?float $alpha)
    {
        if ($alpha === null) {
            $alpha = 1.0;
        }

        $this->_alpha = core\math\Util::clampFloat($alpha, 0, 1);
        return $this;
    }

    public function getAlpha(): float
    {
        return $this->_alpha;
    }


    // Modification
    public function add($color)
    {
        $this->setMode(IColor::RGB);
        $color = self::factory($color)
            ->setMode(IColor::RGB);

        $this->setRed($this->_a + $color->_a);
        $this->setGreen($this->_b + $color->_b);
        $this->setBlue($this->_c + $color->_c);

        return $this;
    }

    public function subtract($color)
    {
        $this->setMode(IColor::RGB);
        $color = self::factory($color)
            ->setMode(IColor::RGB);

        $this->setRed($this->_a - $color->_a);
        $this->setGreen($this->_b - $color->_b);
        $this->setBlue($this->_c - $color->_c);

        return $this;
    }


    // Affect HSL
    public function affectHsl(float $h, float $s, float $l, float $a=null)
    {
        $this->setMode(IColor::HSL);

        $this->setHslHue($this->_a + $h);
        $this->setHslSaturation($this->_b + $s);
        $this->setHslLightness($this->_c + $l);

        if ($a !== null) {
            $this->affectAlpha($a);
        }

        return $this;
    }

    public function affectHslHue(float $h)
    {
        $this->setMode(IColor::HSL);
        $this->setHslHue($this->_a + $h);

        return $this;
    }

    public function affectHslSaturation(float $s)
    {
        $this->setMode(IColor::HSL);
        $this->setHslSaturation($this->_b + $s);

        return $this;
    }

    public function affectHslLightness(float $l)
    {
        $this->setMode(IColor::HSL);
        $this->setHslLightness($this->_c + $l);

        return $this;
    }

    // Affect HSV
    public function affectHsv(float $h, float $s, float $v, float $a=null)
    {
        $this->setMode(IColor::HSV);

        $this->setHsvHue($this->_a + $h);
        $this->setHsvSaturation($this->_b + $s);
        $this->setHsvValue($this->_c + $v);

        if ($a !== null) {
            $this->affectAlpha($a);
        }

        return $this;
    }

    public function affectHsvHue(float $h)
    {
        $this->setMode(IColor::HSV);
        $this->setHsvHue($this->_a + $h);

        return $this;
    }

    public function affectHsvSaturation(float $s)
    {
        $this->setMode(IColor::HSV);
        $this->setHsvSaturation($this->_b + $s);

        return $this;
    }

    public function affectHsvValue(float $v)
    {
        $this->setMode(IColor::HSV);
        $this->setHsvValue($this->_c + $v);

        return $this;
    }


    public function affectAlpha(float $a)
    {
        return $this->setAlpha($this->_alpha + $a);
    }



    // Tones
    public function affectContrast(float $amount)
    {
        $this->setMode(IColor::HSL);
        $amount = core\math\Util::clampFloat($amount, -1, 1);
        $ratio = $this->_c - 0.5;

        return $this->setHslLightness(($ratio * $amount) + 0.5);
    }

    public function toMidtone(float $amount=1.0)
    {
        $this->setMode(IColor::HSL);
        $amount = core\math\Util::clampFloat($amount, 0, 1);
        $delta = $this->_c - 0.5;

        return $this->setHslLightness($this->_c - ($delta * $amount));
    }

    public function contrastAgainst($color, float $amount=0.5)
    {
        $this->setMode(IColor::RGB);
        $color = self::factory($color)->setMode(IColor::RGB);

        $amount = core\math\Util::clampFloat($amount, 0, 1);
        $delta1 = $this->_c - 0.5;
        $delta2 = $color->_c - 0.5;

        if ($delta2 < 0 && $delta1 < $delta2 + $amount) {
            $delta1 = $delta2 + $amount;
        } elseif ($delta2 > 0 && $delta1 > $delta2 - $amount) {
            $delta1 = $delta2 - $amount;
        }

        return $this->setHslLightness($delta1 + 0.5);
    }

    public function getTextContrastColor(): IColor
    {
        $this->setMode(IColor::HSL);

        if ($this->_c > 0.8) {
            return self::factory('black');
        } else {
            return self::factory('white');
        }
    }




    // Preset colors
    const NAMES = [
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

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity->setDefinition($this->toCssString());
    }
}

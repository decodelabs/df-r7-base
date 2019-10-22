<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon;

use df;
use df\core;
use df\neon;

// Interfaces
interface IColor extends core\unit\ICssCompatibleUnit
{
    const RGB = 'rgb';
    const HSL = 'hsl';
    const HSV = 'hsv';

    public static function random(float $saturation=null, float $lightness=null): IColor;
    public static function factory($color): IColor;
    public static function fromString(string $str): IColor;
    public static function fromName(string $name): IColor;
    public static function isValidName(string $name): bool;
    public static function fromHex(string $hex): IColor;

    public function toHexString(bool $allowShort=false): string;
    public function setMode(string $mode);

    // RGB
    public function setRgba(float $r, float $g, float $b, float $a=null);
    public function setRgb(float $r, float $g, float $b);

    public function setRed(float $r);
    public function getRed(): float;

    public function setGreen(float $g);
    public function getGreen(): float;

    public function setBlue(float $b);
    public function getBlue(): float;


    // HSL
    public function setHsla(float $h, float $s, float $l, float $a=null);
    public function setHsl(float $h, float $s, float $l);

    public function setHslHue(float $h);
    public function getHslHue(): float;

    public function setHslSaturation(float $s);
    public function getHslSaturation(): float;

    public function setHslLightness(float $l);
    public function getHslLightness(): float;


    // HSV
    public function setHsva(float $h, float $s, float $v, float $a=null);
    public function setHsv(float $h, float $s, float $v);

    public function setHsvHue(float $h);
    public function getHsvHue(): float;

    public function setHsvSaturation(float $s);
    public function getHsvSaturation(): float;

    public function setHsvValue(float $l);
    public function getHsvValue(): float;


    // Alpha
    public function setAlpha(?float $alpha);
    public function getAlpha(): float;

    // Modification
    public function add($color);
    public function subtract($color);

    // Affect HSL
    public function affectHsl(float $h, float $s, float $l, float $a=null);
    public function affectHslHue(float $h);
    public function affectHslSaturation(float $s);
    public function affectHslLightness(float $l);

    // Affect HSV
    public function affectHsv(float $h, float $s, float $v, float $a=null);
    public function affectHsvHue(float $h);
    public function affectHsvSaturation(float $s);
    public function affectHsvValue(float $v);
    public function affectAlpha(float $a);

    // Tones
    public function affectContrast(float $amount);
    public function toMidtone(float $amount=1.0);
    public function contrastAgainst($color, float $amount=0.5);
    public function getTextContrastColor(): IColor;

    public function __toString(): string;
}


interface IColorStop extends core\IStringProvider
{
    public static function factory($colorStop): IColorStop;

    // Color
    public function setColor($color);
    public function getColor(): IColor;

    // Size
    public function setSize($size);
    public function getSize(): ?core\unit\IDisplaySize;
}

<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\raster;

use df;
use df\core;
use df\neon;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class FormatException extends RuntimeException {}
class InvalidAgumentException extends \InvalidArgumentException implements IException {}


// Interfaces
interface IDimension {
    const WIDTH = 'w';
    const HEIGHT = 'h';

    const PROPORTIONAL = 'p';
    const STRETCH = 's';
    const FIT = 'f';
}

interface IPosition {
    const TILE = 'tile';

    const TOP = 't';
    const BOTTOM = 'b';
    const LEFT = 'l';
    const RIGHT = 'r';
    const CENTER = 'c';
}

interface IImageManipulationController {
    public function resize($width, $height=null, $mode=IDimension::PROPORTIONAL);
    public function crop($x, $y, $width, $height);
    public function cropZoom($width, $height);
    public function frame($width, $height=null, $color=null);
    public function rotate($angle, $background=null);
    public function mirror();
    public function flip();
}

interface IImageCompositeController {
    public function composite(IImage $image, $x=IPosition::CENTER, $y=IPosition::CENTER);
    public function watermark(IImage $image, $x=IPosition::RIGHT, $y=IPosition::BOTTOM, $scaleFactor=1.0);
    public function textWatermark($text, $fontSize, $color, $x=IPosition::RIGHT, $y=IPosition::BOTTOM);
}

interface IImageFilterController {
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

interface IImageDrawingController {
    public function rectangleFill($x, $y, $width, $height, $color);
    public function gradientFill($orientation, $x, $y, $width, $height, array $colors);
}

interface IImage extends IImageManipulationController, IImageFilterController {

    public function getDriver();

    public function getWidth();
    public function getHeight();

    public function transform($string=null);

    public function setOutputFormat($format);
    public function getOutputFormat();

    public function setSavePath($savePath);
    public function getSavePath();

    public function saveTo($savePath, $quality=100);
    public function save($quality=100);
    public function toString($quality=100): string;
}


interface IDriver {

    public static function isLoadable(): bool;
    public static function canRead($format);
    public static function canWrite($format);

    public function spawnInstance();
    public function getName();

    public function loadFile($file);
    public function loadString($string);
    public function loadCanvas($width, $height, neon\IColor $color=null);

    public function getWidth();
    public function getHeight();

    public function setOutputFormat($format);
    public function getOutputFormat();

    public function saveTo($savePath, $quality);
    public function toString($quality): string;
}

interface IImageManipulationDriver extends IDriver {
    public function resize($width, $height);
    public function crop($x, $y, $width, $height);
    public function composite(IDriver $image, $x, $y);
    public function rotate(core\unit\IAngle $angle, neon\IColor $background=null);
}

interface IImageFilterDriver extends IDriver {
    public function brightness($brightness);
    public function contrast($contrast);
    public function greyscale();
    public function colorize(neon\IColor $color, $alpha);
    public function invert();
    public function detectEdges();
    public function emboss();
    public function blur();
    public function gaussianBlur();
    public function removeMean();
    public function smooth($amount);
}



interface ITransformation extends IImageManipulationController, IImageFilterController, core\IStringProvider {
    public function setImage(IImage $image);
    public function getImage();

    public function rescale($scale);

    public function apply();
}



interface IIcoGenerator {
    public function addImage($file, int ...$sizes);
    public function save($file);
    public function generate();
}

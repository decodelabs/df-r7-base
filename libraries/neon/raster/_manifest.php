<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\neon\raster;

use DecodeLabs\Atlas\File;

use DecodeLabs\Spectrum\Color;
use df\core;

// Interfaces
interface IDimension
{
    public const WIDTH = 'w';
    public const HEIGHT = 'h';

    public const PROPORTIONAL = 'p';
    public const STRETCH = 's';
    public const FIT = 'f';
}

interface IPosition
{
    public const TILE = 'tile';

    public const TOP = 't';
    public const BOTTOM = 'b';
    public const LEFT = 'l';
    public const RIGHT = 'r';
    public const CENTER = 'c';
}

interface IImageManipulationController
{
    public function resize(?int $width, int $height = null, string $mode = null);
    public function crop(int $x, int $y, int $width, int $height);
    public function cropZoom(?int $width, int $height = null);
    public function frame(?int $width, int $height = null, $color = null);
    public function rotate($angle, $background = null);
    public function mirror();
    public function flip();
}

interface IImageCompositeController
{
    public function composite(IImage $image, $x = IPosition::CENTER, $y = IPosition::CENTER);
    public function watermark(IImage $image, $x = IPosition::RIGHT, $y = IPosition::BOTTOM, $scaleFactor = 1.0);
    public function textWatermark(string $text, int $fontSize, $color, $x = IPosition::RIGHT, $y = IPosition::BOTTOM);
}

interface IImageFilterController
{
    public function brightness($brightness);
    public function contrast($contrast);
    public function greyscale();
    public function colorize($color, $alpha = null);
    public function invert();
    public function detectEdges();
    public function emboss();
    public function blur();
    public function gaussianBlur();
    public function removeMean();
    public function smooth($amount = null);
}

interface IImageDrawingController
{
    public function rectangleFill($x, $y, $width, $height, $color);
    public function gradientFill($orientation, $x, $y, $width, $height, array $colors);
}

interface IImage extends IImageManipulationController, IImageFilterController
{
    public function getDriver();

    public function getWidth();
    public function getHeight();

    public function transform($string = null);

    public function setOutputFormat($format);
    public function getOutputFormat();

    public function setSavePath($savePath);
    public function getSavePath();

    public function saveTo($savePath, $quality = 100);
    public function save($quality = 100);
    public function toString($quality = 100): string;
}


interface IDriver
{
    public static function isLoadable(): bool;
    public static function canRead($format);
    public static function canWrite($format);

    public function spawnInstance();
    public function getPointer();
    public function getName(): string;

    public function loadFile($file);
    public function loadString($string);
    public function loadCanvas($width, $height, Color $color = null);

    public function getWidth();
    public function getHeight();

    public function setOutputFormat($format);
    public function getOutputFormat();

    public function saveTo($savePath, $quality);
    public function toString($quality): string;
}

interface IImageManipulationDriver extends IDriver
{
    public function resize(int $width, int $height);
    public function crop(int $x, int $y, int $width, int $height);
    public function composite(IDriver $image, $x, $y);
    public function rotate(core\unit\IAngle $angle, Color $background = null);
}

interface IImageFilterDriver extends IDriver
{
    public function brightness(float $brightness);
    public function contrast(float $contrast);
    public function greyscale();
    public function colorize(Color $color, float $alpha);
    public function invert();
    public function detectEdges();
    public function emboss();
    public function blur();
    public function gaussianBlur();
    public function removeMean();
    public function smooth(float $amount);
}



interface ITransformation extends IImageManipulationController, IImageFilterController, core\IStringProvider
{
    public function setImage(?IImage $image);
    public function getImage(): ?IImage;
    public function isAlphaRequired(): bool;

    public function rescale(float $scale);

    public function apply(): IImage;
}



interface IIcoGenerator
{
    public function addImage($file, int ...$sizes);
    public function save($file): File;
    public function generate(): string;
}



interface IDescriptor
{
    public function getSourceLocation(): string;
    public function isSourceLocal(): bool;

    public function applyTransformation($transformation, core\time\IDate $modificationDate = null);
    public function shouldOptimizeTransformation(bool $flag = null);
    public function getTransformation(): ?ITransformation;
    public function toIcon(int ...$sizes);

    public function getLocation(): string;
    public function isLocal(): bool;

    public function setFileName(?string $fileName);
    public function getFileName(): string;
    public function shouldIncludeTransformationInFileName(bool $flag = null);

    public function getContentType(): string;
}

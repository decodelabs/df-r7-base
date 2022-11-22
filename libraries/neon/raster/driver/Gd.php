<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\neon\raster\driver;

use DecodeLabs\Exceptional;
use DecodeLabs\Spectrum\Color;

use df\core;
use df\neon;

class Gd extends Base implements neon\raster\IImageManipulationDriver, neon\raster\IImageFilterDriver
{
    public const READ_FORMATS = [
        'GIF', 'JPEG', 'PNG', 'PNG8', 'PNG24', 'PNG32', 'WBMP', 'XBM', 'XPM'
    ];

    public const WRITE_FORMATS = [
        'GIF', 'JPEG', 'PNG', 'PNG8', 'PNG24', 'PNG32', 'WBMP'
    ];

    public function __destruct()
    {
        if ($this->_pointer) {
            imageDestroy($this->_pointer);
        }
    }

    public static function isLoadable(): bool
    {
        return extension_loaded('gd');
    }


    public function loadFile($file)
    {
        /** @phpstan-ignore-next-line */
        ini_set('memory_limit', -1);

        if ($i = getimagesize($file)) {
            $this->_width = $i[0];
            $this->_height = $i[1];
        }

        try {
            switch (@$i[2]) {
                case 1: // gif
                    $this->_pointer = imageCreateFromGif($file);
                    $this->_outputFormat = 'GIF';
                    break;

                case 2: // jpg
                    $this->_pointer = imageCreateFromJpeg($file);
                    $this->_outputFormat = 'JPEG';
                    break;

                case 3: // png
                    $this->_pointer = imageCreateFromPng($file);
                    $this->_outputFormat = 'PNG';
                    break;

                case 15: // wbmp
                    $this->_pointer = imageCreateFromWbmp($file);
                    $this->_outputFormat = 'WBMP';
                    break;

                case 16: // xbm
                    $this->_pointer = imageCreateFromXbm($file);
                    $this->_outputFormat = 'PNG';
                    break;

                default:
                    if (false === ($data = file_get_contents($file))) {
                        throw Exceptional::Runtime(
                            'Unable to load file contents',
                            null,
                            $file
                        );
                    }

                    $this->_pointer = imageCreateFromString($data);
                    $this->_outputFormat = 'PNG';
                    break;
            }
        } catch (\Throwable $e) {
            throw Exceptional::{'../Format'}(
                $e->getMessage()
            );
        }

        if (!$this->_pointer) {
            throw Exceptional::{'../Io,../Unreadable'}(
                'Unable to load raster image ' . $file
            );
        }

        return $this;
    }

    public function loadString($string)
    {
        if ($i = getImageSizeFromString($string)) {
            $this->_width = $i[0];
            $this->_height = $i[1];
        }

        $this->_pointer = imageCreateFromString($string);

        if (!$this->_pointer) {
            throw Exceptional::{'../Io,../Unreadable'}(
                'Unable to load raster image from string'
            );
        }

        return $this;
    }

    public function loadCanvas($width, $height, Color $color = null)
    {
        if ($color === null) {
            $color = new Color(0, 0, 0, 0);
        }

        if (false === ($pointer = imageCreateTrueColor($width, $height))) {
            throw Exceptional::Runtime(
                'Unable to create true color image'
            );
        }

        $this->_pointer = $pointer;
        $this->_width = $width;
        $this->_height = $height;

        imageAlphaBlending($this->_pointer, false);
        imageSaveAlpha($this->_pointer, true);

        $color->setMode('rgb');

        imageFill(
            $this->_pointer,
            1,
            1,
            imageColorAllocateAlpha(
                $this->_pointer,
                (int)($color->getRed() * 255),
                (int)($color->getGreen() * 255),
                (int)($color->getBlue() * 255),
                (int)(127 - ($color->getAlpha() * 127))
            )
        );

        return $this;
    }


    public function saveTo($savePath, $quality)
    {
        $string = $this->toString($quality);
        file_put_contents($savePath, $string);

        return $this;
    }

    public function toString($quality): string
    {
        ob_start();

        try {
            switch ($this->_outputFormat) {
                case 'GIF':
                    imageTrueColorToPalette($this->_pointer, true, 256);
                    imageGif($this->_pointer);
                    break;

                case 'JPEG':
                    imageJpeg($this->_pointer, null, $quality);
                    break;

                case 'WBMP':
                    imageWbmp($this->_pointer);
                    break;

                default:
                case 'PNG':
                    //imageAlphaBlending($this->_pointer, false);
                    imageSaveAlpha($this->_pointer, true);
                    imagePng($this->_pointer);
                    break;
            }
        } catch (\Throwable $e) {
            ob_clean();
            throw $e;
        }

        return (string)ob_get_clean();
    }



    // Manipulations
    public function resize(int $width, int $height)
    {
        if (false === ($img = imageCreateTrueColor($width, $height))) {
            throw Exceptional::Runtime(
                'Unable to create true color image'
            );
        }

        $background = imageColorAllocateAlpha($img, 255, 255, 255, 127);
        imageColorTransparent($img, $background);

        imageAlphaBlending($img, false);
        imageSaveAlpha($img, true);
        imageFilledRectangle($img, 0, 0, $width, $height, $background);

        imageCopyResampled($img, $this->_pointer, 0, 0, 0, 0, $width, $height, $this->_width, $this->_height);
        imageDestroy($this->_pointer);

        $this->_pointer = $img;
        $this->_width = $width;
        $this->_height = $height;

        return $this;
    }

    public function crop(int $x, int $y, int $width, int $height)
    {
        if (false === ($img = imageCreateTrueColor($width, $height))) {
            throw Exceptional::Runtime(
                'Unable to create true color image'
            );
        }

        $background = imageColorAllocateAlpha($img, 255, 255, 255, 127);
        imageColorTransparent($img, $background);

        imageAlphaBlending($img, false);
        imageSaveAlpha($img, true);
        imageFilledRectangle($img, 0, 0, $width, $height, $background);

        imageCopy($img, $this->_pointer, 0, 0, $x, $y, $width, $height);
        imageDestroy($this->_pointer);

        $this->_pointer = $img;
        $this->_width = $width;
        $this->_height = $height;

        return $this;
    }

    public function composite(neon\raster\IDriver $image, $x, $y)
    {
        imageAlphaBlending($this->_pointer, true);
        imageAlphaBlending($image->getPointer(), true);

        imageCopy(
            $this->_pointer,
            $image->getPointer(),
            $x,
            $y,
            0,
            0,
            $image->getWidth(),
            $image->getHeight()
        );

        imageAlphaBlending($this->_pointer, false);
        imageAlphaBlending($image->getPointer(), false);

        return $this;
    }

    public function rotate(core\unit\IAngle $angle, Color $background = null)
    {
        if ($background === null) {
            $background = imageColorAllocateAlpha($this->_pointer, 0, 0, 0, 127);
        } else {
            $background = imageColorAllocate(
                $this->_pointer,
                (int)($background->getRed() * 255),
                (int)($background->getGreen() * 255),
                (int)($background->getBlue() * 255)
            );
        }

        if (false === ($pointer = imageRotate($this->_pointer, $angle->getDegrees() * -1, $background))) {
            throw Exceptional::Runtime(
                'Unable to rotate image'
            );
        }

        imageDestroy($this->_pointer);
        $this->_pointer = $pointer;
        $this->_width = imageSX($this->_pointer);
        $this->_height = imageSY($this->_pointer);

        return $this;
    }

    public function mirror()
    {
        if (false === ($tmp = imageCreateTrueColor($this->_width, $this->_height))) {
            throw Exceptional::Runtime(
                'Unable to create true color image'
            );
        }

        imageAlphaBlending($tmp, true);

        for ($x = 0; $x < $this->_width; $x++) {
            imageCopy(
                $tmp,
                $this->_pointer,
                $x,
                0,
                (int)($this->_width - $x - 1),
                0,
                1,
                $this->_height
            );
        }

        imageAlphaBlending($tmp, false);
        imageDestroy($this->_pointer);

        $this->_pointer = $tmp;
        return $this;
    }

    public function flip()
    {
        if (false === ($tmp = imageCreateTrueColor($this->_width, $this->_height))) {
            throw Exceptional::Runtime(
                'Unable to create true color image'
            );
        }

        imageAlphaBlending($tmp, true);

        for ($y = 0; $y < $this->_height; $y++) {
            imageCopy(
                $tmp,
                $this->_pointer,
                0,
                $y,
                0,
                (int)($this->_height - $y - 1),
                $this->_width,
                1
            );
        }

        imageAlphaBlending($tmp, false);
        imageDestroy($this->_pointer);

        $this->_pointer = $tmp;
        return $this;
    }


    // Filters
    public function brightness(float $brightness)
    {
        imageAlphaBlending($this->_pointer, false);
        imageSaveAlpha($this->_pointer, true);

        if (function_exists('imagefilter')) {
            imagefilter($this->_pointer, \IMG_FILTER_BRIGHTNESS, (int)$brightness);
        }

        return $this;
    }

    public function contrast(float $contrast)
    {
        imageAlphaBlending($this->_pointer, false);
        imageSaveAlpha($this->_pointer, true);

        if (function_exists('imagefilter')) {
            imagefilter($this->_pointer, \IMG_FILTER_CONTRAST, (int)$contrast * -1);
        }

        return $this;
    }

    public function greyscale()
    {
        imageAlphaBlending($this->_pointer, false);
        imageSaveAlpha($this->_pointer, true);

        if (function_exists('imagefilter')) {
            imagefilter($this->_pointer, \IMG_FILTER_GRAYSCALE);
        } else {
            imageCopyMergeGray(
                $this->_pointer,
                $this->_pointer,
                0,
                0,
                0,
                0,
                $this->_width,
                $this->_height,
                0
            );
        }

        return $this;
    }

    public function colorize(Color $color, float $alpha)
    {
        imageAlphaBlending($this->_pointer, false);
        imageSaveAlpha($this->_pointer, true);

        if (function_exists('imagefilter')) {
            imagefilter(
                $this->_pointer,
                \IMG_FILTER_COLORIZE,
                (int)($color->getRed() * 255),
                (int)($color->getGreen() * 255),
                (int)($color->getBlue() * 255),
                (int)($alpha / 100 * -127)
            );
        }

        return $this;
    }

    public function invert()
    {
        imageAlphaBlending($this->_pointer, false);
        imageSaveAlpha($this->_pointer, true);

        if (function_exists('imagefilter')) {
            imagefilter($this->_pointer, \IMG_FILTER_NEGATE);
        }

        return $this;
    }

    public function detectEdges()
    {
        imageAlphaBlending($this->_pointer, false);
        imageSaveAlpha($this->_pointer, true);

        if (function_exists('imagefilter')) {
            imagefilter($this->_pointer, \IMG_FILTER_EDGEDETECT);
        }

        return $this;
    }

    public function emboss()
    {
        imageAlphaBlending($this->_pointer, false);
        imageSaveAlpha($this->_pointer, true);

        if (function_exists('imagefilter')) {
            imagefilter($this->_pointer, \IMG_FILTER_EMBOSS);
        }

        return $this;
    }

    public function blur()
    {
        imageAlphaBlending($this->_pointer, false);
        imageSaveAlpha($this->_pointer, true);

        if (function_exists('imagefilter')) {
            imagefilter($this->_pointer, \IMG_FILTER_SELECTIVE_BLUR);
        }

        return $this;
    }

    public function gaussianBlur()
    {
        imageAlphaBlending($this->_pointer, false);
        imageSaveAlpha($this->_pointer, true);

        if (function_exists('imagefilter')) {
            imagefilter($this->_pointer, \IMG_FILTER_GAUSSIAN_BLUR);
        }

        return $this;
    }

    public function removeMean()
    {
        imageAlphaBlending($this->_pointer, false);
        imageSaveAlpha($this->_pointer, true);

        if (function_exists('imagefilter')) {
            imagefilter($this->_pointer, \IMG_FILTER_MEAN_REMOVAL);
        }

        return $this;
    }

    public function smooth(float $amount)
    {
        imageAlphaBlending($this->_pointer, false);
        imageSaveAlpha($this->_pointer, true);

        if (function_exists('imagefilter')) {
            imagefilter($this->_pointer, \IMG_FILTER_SMOOTH, (int)$amount);
        }

        return $this;
    }




    // Drawing
    /*
    public function rectangleFill($x, $y, $width, $height, $color, $alpha=1) {
        $color = Color::create($color);

        imageFilledRectangle(
            $this->_pointer,
            (int)$x, (int)$y,
            (int)($x + $width), (int)($y + $height),
            imageColorAllocateAlpha(
                $this->_pointer,
                (int)$color->getRed(),
                (int)$color->getGreen(),
                (int)$color->getBlue(),
                (int)(127 - ($alpha * 127))
            )
        );

        return $this;
    }

    public function gradientFill($orientation, $x, $y, $width, $height, array $colors) {
        $x = $this->_normalizePixelSize($x);
        $y = $this->_normalizePixelSize($y);
        $width = $this->_normalizePixelSize($width);
        $height = $this->_normalizePixelSize($height);

        $flip = false;
        $rotate = false;

        switch(strtolower($orientation)) {
            case 'right':
                $flip = true;
            case 'left':
                $totalRows = $width;
                $rotate = true;
                break;

            case 'bottom':
                $flip = true;
            case 'top':
            default:
                $totalRows = $height;
                break;
        }

        $cols = $rotate ? $height : $width;
        $rangeList = [];

        $colors = $this->_normalizeGradientColorSet($colors, $totalRows);

        foreach($colors as $color) {
            $range = floor($totalRows * $color[1]);

            if($flip) {
                $range = $totalRows - $range;
            }

            while(isset($rangeList["$range"])) {
                $range += 0.0001;
            }

            $rangeList["$range"] = $color[0];
        }

        ksort($rangeList);

        $startColor = $rangeList[0];
        unset($rangeList[0]);
        $start = 0;


        foreach($rangeList as $end => $endColor) {
            $test[] = [
                'start' => $start,
                'end' => $end,
                'startColor' => $startColor,
                'endColor' => $endColor
            ];

            $rows = $end - $start;

            $stepColor = new Color(
                ($endColor->getRed() - $startColor->getRed()) / $rows,
                ($endColor->getGreen() - $startColor->getGreen()) / $rows,
                ($endColor->getBlue() - $startColor->getBlue()) / $rows
            );


            $activeColor = clone $startColor;
            $alphaStep = ((127 * $endColor->getAlpha()) - (127 * $startColor->getAlpha())) / $rows;
            $activeAlpha = $startColor->getAlpha() * 127;


            for($j = 0; $j < $rows; $j++) {
                $activeColor->add($stepColor);
                $activeAlpha += $alphaStep;

                $colorAllocate = imageColorAllocateAlpha(
                    $this->_pointer,
                    (int)round($activeColor->getRed() * 255),
                    (int)round($activeColor->getGreen() * 255),
                    (int)round($activeColor->getBlue() * 255),
                    (int)(127 - $activeAlpha)
                );

                for($k = 0; $k < $cols; $k++) {
                    $currentX = $x + ($rotate ? $j : $k);
                    $currentY = $y + ($rotate ? $k : $j);

                    imageSetPixel(
                        $this->_pointer,
                        $currentX, $currentY,
                        $colorAllocate
                    );
                }
            }

            if($rotate) {
                $x += $rows;
            } else {
                $y += $rows;
            }

            $start = $end;
            $startColor = $endColor;
        }

        return $this;
    }
     */
}

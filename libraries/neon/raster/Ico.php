<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\raster;

use df;
use df\core;
use df\neon;

use DecodeLabs\Glitch;
use DecodeLabs\Atlas;
use DecodeLabs\Atlas\File;

class Ico implements IIcoGenerator
{
    protected $_images = [];

    public function __construct($file=null, int ...$sizes)
    {
        if ($file !== null) {
            $this->addImage($file, ...$sizes);
        }
    }

    public function addImage($file, int ...$sizes)
    {
        $file = Atlas::$fs->file($file);

        if (!$file->exists()) {
            throw Glitch::ENotFound(
                'Source file '.$file->getPath().' does not exist'
            );
        }

        if (!getImageSize($file->getPath())) {
            throw Glitch::{'EUnexpectedValue,EUnreadable'}(
                'Unable to read image data from '.$file->getPath()
            );
        }

        $image = imageCreateFromString($file->getContents());
        $sourceWidth = imagesx($image);
        $sourceHeight = imagesy($image);

        if (empty($sizes)) {
            $sizes = [max($sourceWidth, $sourceHeight)];
        }

        foreach ($sizes as $size) {
            $newImage = imageCreateTrueColor($size, $size);
            imageColorTransparent($newImage, imageColorAllocateAlpha($newImage, 0, 0, 0, 127));
            imageAlphaBlending($newImage, false);
            imageSaveAlpha($newImage, true);

            if (false === imageCopyResampled($newImage, $image, 0, 0, 0, 0, $size, $size, $sourceWidth, $sourceHeight)) {
                continue;
            }

            $this->_images[] = new Ico_Image($newImage);
        }

        return $this;
    }

    public function save($file): File
    {
        return Atlas::$fs->createFile($file, $this->generate());
    }

    public function generate(): string
    {
        if (empty($this->_images)) {
            throw Glitch::ERuntime(
                'No images have been added to ICO generator'
            );
        }

        $data = pack('vvv', 0, 1, count($this->_images));
        $pixels = '';
        $dirEntrySize = 16;
        $offset = 6 + ($dirEntrySize * count($this->_images));

        foreach ($this->_images as $image) {
            $data .= $image->packData($offset);
            $pixels .= $image->data;
            $offset += $image->size;
        }

        return $data.$pixels;
    }
}


class Ico_Image
{
    public $width;
    public $height;
    public $colors = 0;
    public $bpp = 32;
    public $size = 0;
    public $data;

    public function __construct($image)
    {
        $this->width = imagesx($image);
        $this->height = imagesy($image);

        $pixels = [];
        $opacityData = [];
        $currentOpacity = 0;

        for ($y = $this->height - 1; $y >= 0; $y--) {
            for ($x = 0; $x < $this->width; $x++) {
                $color = imageColorAt($image, $x, $y);

                $alpha = ($color & 0x7F000000) >> 24;
                $alpha = (1 - ($alpha / 127)) * 255;

                $color &= 0xFFFFFF;
                $color |= 0xFF000000 & ($alpha << 24);

                $pixels[] = $color;

                $opacity = ($alpha <= 127) ? 1 : 0;
                $currentOpacity = ($currentOpacity << 1) | $opacity;

                if ((($x + 1) % 32) == 0) {
                    $opacityData[] = $currentOpacity;
                    $currentOpacity = 0;
                }
            }

            if (($x % 32) > 0) {
                while (($x++ % 32) > 0) {
                    $currentOpacity = $currentOpacity << 1;
                }

                $opacityData[] = $currentOpacity;
                $currentOpacity = 0;
            }
        }

        $headerSize = 40;
        $colorMaskSize = $this->width * $this->height * 4;
        $opacityMaskSize = (ceil($this->width / 32) * 4) * $this->height;

        $this->data = pack('VVVvvVVVVVV', 40, $this->width, ($this->height * 2), 1, 32, 0, 0, 0, 0, 0, 0);

        foreach ($pixels as $color) {
            $this->data .= pack('V', $color);
        }

        foreach ($opacityData as $opacity) {
            $this->data .= pack('N', $opacity);
        }

        $this->size = $headerSize + $colorMaskSize + $opacityMaskSize;
    }

    public function packData($offset)
    {
        return pack('CCCCvvVV', $this->width, $this->height, $this->colors, 0, 1, $this->bpp, $this->size, $offset);
    }
}

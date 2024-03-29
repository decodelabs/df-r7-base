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

class ImageMagick extends Base implements neon\raster\IImageManipulationDriver, neon\raster\IImageFilterDriver
{
    public const READ_FORMATS = [
        'AAI', 'ART', 'ARW', 'AVI', 'AVS', 'BMP', 'BMP2', 'BMP3', 'CALS', 'CGM', 'CIN', 'CMYK', 'CMYKA',
        'CR2', 'CRW', 'CUR', 'CUT', 'DCM', 'DCR', 'DCX', 'DIB', 'DJVU', 'DNG', 'DOT', 'DPX', 'EMF',
        'EPDF', 'EPI', 'EPS', 'EPS2', 'EPS3', 'EPSF', 'EPSI', 'EPT', 'EXR', 'FAX', 'FIG', 'FITS', 'FPX',
        'GIF', 'GPLT', 'GRAY', 'HDR', 'HPGL', 'HRZ', 'HTML', 'ICO', 'INLINE', 'JBIG', 'JNG', 'JP2', 'JPC',
        'JPEG', 'MAN', 'MAT', 'MIFF', 'MONO', 'MNG', 'M2V', 'MPEG', 'MPC', 'MPR', 'MRW', 'MSL', 'MTV',
        'MVG', 'NEF', 'ORF', 'OTB', 'P7', 'PALM', 'CLIPBOARD', 'PBM', 'PCD', 'PCDS', 'PCX', 'PDB', 'PDF',
        'PEF', 'PFA', 'PFB', 'PFM', 'PFG', 'PICON', 'PICT', 'PIX', 'PNG', 'PNG8', 'PNG24', 'PNG32',
        'PNM', 'PPM', 'PS', 'PS2', 'PS3', 'PSB', 'PSD', 'PTIF', 'PWP', 'RAD', 'RAF', 'RGB', 'RGBA', 'RLA',
        'RLE', 'SCT', 'SFW', 'SGI', 'SID', 'MrSID', 'SUN', 'SVG', 'TGA', 'TIFF', 'TIM', 'TTF', 'TXT',
        'UYVY', 'VICAR', 'VIFF', 'WBMP', 'WEBP', 'WMF', 'WPG', 'X', 'XBM', 'XCF', 'XPM', 'XWD', 'X3F',
        'YCbCr', 'YCbCrA', 'YUV'
    ];

    public const WRITE_FORMATS = [
        'AAI', 'AVS', 'BMP', 'BMP2', 'BMP3', 'CIN', 'CMYK', 'CMYKA', 'DCX', 'DIB', 'DPX', 'EPDF', 'EPI',
        'EPS', 'EPSF', 'EPSI', 'EPT', 'EXR', 'FAX', 'FITS', 'FPX', 'GIF', 'GRAY', 'HDR', 'HRZ', 'HTML',
        'INFO', 'JBIG', 'JNG', 'JP2', 'JPC', 'JPEG', 'MIFF', 'MONO', 'MNG', 'M2V', 'MPEG', 'MPC', 'MPR',
        'MSL', 'MTV', 'MVG', 'OTB', 'P7', 'PALM', 'PAM', 'CLIPBOARD', 'PBM', 'PCD', 'PCDS', 'PCL', 'PCX',
        'PDB', 'PDF', 'PFM', 'PGM', 'PICON', 'PICT', 'PNG', 'PNG8', 'PNG24', 'PNG32', 'PNM', 'PPM', 'PS',
        'PS2', 'PS3', 'PSB', 'PSD', 'PTIF', 'RGB', 'RGBA', 'SGI', 'SHTML', 'SUN', 'SVG', 'TGA', 'TIFF',
        'TXT', 'UYVY', 'VICAR', 'VIFF', 'WBMP', 'WEBP', 'X', 'XBM', 'XPM', 'XWD', 'YCbCr', 'YCbCrA', 'YUV'
    ];

    public static function isLoadable(): bool
    {
        return extension_loaded('imagick');
    }

    public function loadFile($file)
    {
        try {
            $this->_pointer = new \Imagick();
            $this->_pointer->readImage($file);
        } catch (\ImagickException $e) {
            throw Exceptional::{'../Format'}(
                $e->getMessage()
            );
        }

        $this->_width = $this->_pointer->getImageWidth();
        $this->_height = $this->_pointer->getImageHeight();
        $this->_outputFormat = $this->_pointer->getImageFormat();

        return $this;
    }

    public function loadString($string)
    {
        try {
            $this->_pointer = new \Imagick();
            $this->_pointer->readImageBlob($string);
        } catch (\ImagickException $e) {
            throw Exceptional::{'../Format'}(
                $e->getMessage()
            );
        }

        $this->_width = $this->_pointer->getImageWidth();
        $this->_height = $this->_pointer->getImageHeight();
        $this->_outputFormat = $this->_pointer->getImageFormat();

        return $this;
    }

    public function loadCanvas($width, $height, Color $color = null)
    {
        if ($color === null) {
            $color = new Color(0, 0, 0, 0);
        }

        $color->setMode('rgb');

        $this->_pointer = new \Imagick();
        $this->_pointer->newImage($width, $height, new \ImagickPixel($color->toCssString()));
        $this->_width = $width;
        $this->_height = $height;

        return $this;
    }


    public function saveTo($savePath, $quality)
    {
        $this->_pointer->setImageFormat($this->_outputFormat);
        $this->_pointer->setCompressionQuality($quality);

        try {
            $this->_pointer->writeImage($savePath);
        } catch (\Throwable $e) {
            throw Exceptional::{'../Format,../Unwritable'}(
                $e->getMessage()
            );
        }

        return $this;
    }

    public function toString($quality): string
    {
        $this->_pointer->setImageFormat($this->_outputFormat);
        $this->_pointer->setCompressionQuality($quality);

        return $this->_pointer->getImageBlob();
    }


    // Manipulations
    public function resize(int $width, int $height)
    {
        $this->_pointer->resizeImage($width, $height, \Imagick::FILTER_LANCZOS, 1);
        $this->_width = $this->_pointer->getImageWidth();
        $this->_height = $this->_pointer->getImageHeight();

        return $this;
    }

    public function crop(int $x, int $y, int $width, int $height)
    {
        $this->_pointer->cropImage($width, $height, $x, $y);
        $this->_width = $width;
        $this->_height = $height;
        return $this;
    }

    public function composite(neon\raster\IDriver $image, $x, $y)
    {
        $this->_pointer->compositeImage(
            $image->getPointer(),
            \Imagick::COMPOSITE_DEFAULT,
            $x,
            $y
        );

        return $this;
    }

    public function rotate(core\unit\IAngle $angle, Color $background = null)
    {
        if ($background === null) {
            $background = new \ImagickPixel('none');
        } else {
            $background = new \ImagickPixel($background->toCssString());
        }

        $this->_pointer->rotateImage($background, $angle->getDegrees());
        return $this;
    }

    public function mirror()
    {
        $this->_pointer->flopImage();
        return $this;
    }

    public function flip()
    {
        $this->_pointer->flipImage();
        return $this;
    }


    // Filters
    public function brightness(float $brightness)
    {
        $this->_pointer->modulateImage($brightness + 100, 100, 100);
        return $this;
    }

    public function contrast(float $contrast)
    {
        $contrast /= 10;

        while ($contrast < 0) {
            $this->_pointer->contrastImage(0);
            $contrast++;
        }

        while ($contrast > 0) {
            $this->_pointer->contrastImage(1);
            $contrast--;
        }

        return $this;
    }

    public function greyscale()
    {
        $this->_pointer->modulateImage(100, 0, 100);
        return $this;
    }

    public function colorize(Color $color, float $alpha)
    {
        $this->_pointer->colorizeImage($color->toCssString(), $alpha / 100, true);
        return $this;
    }

    public function invert()
    {
        $this->_pointer->negateImage(false);
        return $this;
    }

    public function detectEdges()
    {
        $this->_pointer->edgeImage(0);
        return $this;
    }

    public function emboss()
    {
        $this->_pointer->embossImage(0, 1);
        return $this;
    }

    public function blur()
    {
        $this->_pointer->blurImage(0, 1);
        return $this;
    }

    public function gaussianBlur()
    {
        $this->_pointer->gaussianBlurImage(0, 1);
        return $this;
    }

    public function removeMean()
    {
        $this->_pointer->unsharpMaskImage(0, 1, 8, 0.005);
        return $this;
    }

    public function smooth(float $amount)
    {
        $this->_pointer->blurImage(0, $amount / 100);
        return $this;
    }
}

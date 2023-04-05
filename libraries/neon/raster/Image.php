<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\neon\raster;

use DecodeLabs\Exceptional;

use DecodeLabs\Glitch;
use DecodeLabs\Spectrum\Color;
use df\core;

class Image implements IImage
{
    private static $_driverClass;

    protected $_savePath;
    protected $_driver;

    // Drivers
    public static function getDriverList()
    {
        return [
            'Gd',
            'ImageMagick'
        ];
    }

    public static function setDefaultDriver($driver)
    {
        if ($driver instanceof IDriver) {
            self::$_driverClass = get_class($driver);
            return true;
        }

        $class = 'df\\neon\\raster\\driver\\' . $driver;

        if (class_exists($class)) {
            if (!$class::isLoadable()) {
                throw Exceptional::Unsupported(
                    'Raster image driver ' . $driver . ' is not loadable'
                );
            }

            self::$_driverClass = $class;
            return true;
        }

        throw Exceptional::NotFound(
            $driver . ' is not a valid raster image driver'
        );
    }

    public static function getDefaultDriverClass()
    {
        if (!self::$_driverClass) {
            foreach (self::getDriverList() as $driver) {
                try {
                    self::setDefaultDriver($driver);
                } catch (UnsupportedException $e) {
                    continue;
                }

                break;
            }

            /** @phpstan-ignore-next-line */
            if (!self::$_driverClass) {
                throw Exceptional::ComponentUnavailable(
                    'There are no available raster image drivers'
                );
            }
        }

        return self::$_driverClass;
    }


    // Loading
    public static function loadFile($file)
    {
        $class = self::getDefaultDriverClass();

        if (!is_readable($file)) {
            throw Exceptional::{'Io,Unreadable'}(
                'Raster image ' . $file . ' is not readable'
            );
        }

        return new self((new $class())->loadFile($file));
    }

    public static function loadBase64String($string)
    {
        return self::loadString(base64_decode($string));
    }

    public static function loadString($string)
    {
        $class = self::getDefaultDriverClass();

        return new self((new $class())->loadString($string));
    }

    public static function newCanvas($width, $height, $color = null)
    {
        $class = self::getDefaultDriverClass();
        $output = new self(new $class());

        $output->_driver->loadCanvas(
            $output->_normalizePixelSize($width),
            $output->_normalizePixelSize($height),
            $output->_normalizeColor($color)
        );

        return $output;
    }


    protected function __construct(IDriver $driver)
    {
        $this->_driver = $driver;
    }


    // Driver
    public function getDriver()
    {
        return $this->_driver;
    }



    // Dimensions
    public function getWidth()
    {
        return $this->_driver->getWidth();
    }

    public function getHeight()
    {
        return $this->_driver->getHeight();
    }


    // Transformation
    public function transform($string = null)
    {
        return Transformation::factory($string)
            ->setImage($this);
    }


    // Output format
    public function setOutputFormat($format)
    {
        if (!self::isFormatValid($format)) {
            throw Exceptional::{'InvalidArgument,Format'}(
                $format . ' is not a valid output raster image format'
            );
        }

        if (!$this->_driver->canWrite($format)) {
            throw Exceptional::Format(
                $this->_driver->getName() . ' image driver cannot write ' . $format . ' format files'
            );
        }

        $this->_driver->setOutputFormat($format);
        return $this;
    }

    public function getOutputFormat()
    {
        return $this->_driver->getOutputFormat();
    }

    // Save
    public function setSavePath($savePath)
    {
        if (!is_writable($savePath)) {
            throw Exceptional::{'Runtime,Unwritable'}(
                'Raster image save path is not writable'
            );
        }

        $this->_savePath = $savePath;
        $this->setOutputFormat(self::getFormatFromPath($savePath));

        return $this;
    }

    public function getSavePath()
    {
        return $this->_savePath;
    }

    public function saveTo($savePath, $quality = 100)
    {
        $this->setSavePath($savePath);
        return $this->save($quality);
    }

    public function save($quality = 100)
    {
        if (!$this->_savePath) {
            throw Exceptional::Setup(
                'Raster image save path has not been set'
            );
        }

        if (!$this->_driver->saveTo($this->_savePath, $this->_normalizePercentage($quality))) {
            throw Exceptional::Runtime(
                'Raster image could not be saved'
            );
        }

        return $this;
    }

    public function toString($quality = 100): string
    {
        return $this->_driver->toString($this->_normalizePercentage($quality));
    }



    // Manipulations
    public function resize(?int $width, int $height = null, string $mode = null)
    {
        $this->_checkDriverForManipulations();
        $this->_normalizeRelativeDimensions($width, $height, $currentWidth, $currentHeight);

        if (!$width && !$height) {
            throw Exceptional::InvalidArgument(
                'Invalid proportions specified for resize'
            );
        }

        $mode = $this->_normalizeResizeMode($mode, IDimension::FIT);

        switch ($mode) {
            case IDimension::STRETCH:
                $newWidth = $width;
                $newHeight = $height;
                break;

            case IDimension::FIT:
                if ($currentHeight <= $height && $currentWidth <= $width) {
                    return $this;
                }

                // no break
            case IDimension::PROPORTIONAL:
                $newWidth = $width;
                $newHeight = round($currentHeight * $width / $currentWidth);

                if ($newHeight - $height > 1) {
                    $newHeight = $height;
                    $newWidth = round($currentWidth * $height / $currentHeight);
                }

                break;

            default:
                throw Exceptional::InvalidArgument(
                    'Unsupported resize mode: ' . $mode
                );
        }

        $this->_driver->resize($newWidth, $newHeight);
        return $this;
    }

    public function crop(int $x, int $y, int $width, int $height)
    {
        $this->_checkDriverForManipulations();

        list($x, $y) = $this->_normalizePosition($x, $y);
        $this->_normalizeRelativeDimensions($width, $height, $currentWidth, $currentHeight);

        if ($width < 0) {
            $width = $currentWidth - ($x + abs($width));
        }

        if ($height < 0) {
            $height = $currentHeight - ($x + abs($height));
        }

        $this->_driver->crop($x, $y, $width, $height);
        return $this;
    }

    public function cropZoom(?int $width, int $height = null)
    {
        $this->_checkDriverForManipulations();

        $this->_normalizeRelativeDimensions($width, $height, $currentWidth, $currentHeight, false);

        $widthFactor = $width / $currentWidth;
        $heightFactor = $height / $currentHeight;

        if ($widthFactor >= $heightFactor) {
            $this->resize($width, null, IDimension::PROPORTIONAL);

            $x = 0;
            $y = round(($this->_driver->getHeight() - $height) / 2);
        } else {
            $this->resize(null, $height, IDimension::PROPORTIONAL);

            $x = round(($this->_driver->getWidth() - $width) / 2);
            $y = 0;
        }

        return $this->crop((int)$x, (int)$y, $width, $height);
    }

    public function frame(?int $width, int $height = null, $color = null)
    {
        $this->_checkDriverForManipulations();

        $this->_normalizeRelativeDimensions($width, $height, $currentWidth, $currentHeight);
        $color = $this->_normalizeColor($color);

        $this->resize($width, $height, IDimension::FIT);

        $this->_driver = $this->_driver
            ->spawnInstance()
            ->loadCanvas($width, $height, $color)
            ->composite(
                $this->_driver,
                round(($width - $this->_driver->getWidth()) / 2),
                round(($height - $this->_driver->getHeight()) / 2)
            );

        return $this;
    }

    public function rotate($angle, $background = null)
    {
        $this->_checkDriverForManipulations();

        $angle = $this->_normalizeAngle($angle);
        $background = $this->_normalizeColor($background);

        if ($angle) {
            $this->_driver->rotate($angle, $background);
        }

        return $this;
    }

    public function mirror()
    {
        $this->_checkDriverForManipulations();
        $this->_driver->mirror();

        return $this;
    }

    public function flip()
    {
        $this->_checkDriverForManipulations();
        $this->_driver->flip();

        return $this;
    }



    // Filters
    public function brightness($brightness)
    {
        $this->_checkDriverForFilters();

        $brightness = $this->_normalizePercentage($brightness, true, true);
        $this->_driver->brightness($brightness);

        return $this;
    }

    public function contrast($contrast)
    {
        $this->_checkDriverForFilters();

        $contrast = $this->_normalizePercentage($contrast, true, true);
        $this->_driver->contrast($contrast);

        return $this;
    }

    public function greyscale()
    {
        $this->_checkDriverForFilters();
        $this->_driver->greyscale();

        return $this;
    }

    public function colorize($color, $alpha = null)
    {
        $this->_checkDriverForFilters();

        $color = $this->_normalizeColor($color);
        $alpha = $this->_normalizePercentage($alpha ?? 100);
        $this->_driver->colorize($color, $alpha);

        return $this;
    }

    public function invert()
    {
        $this->_checkDriverForFilters();
        $this->_driver->invert();

        return $this;
    }

    public function detectEdges()
    {
        $this->_checkDriverForFilters();
        $this->_driver->detectEdges();

        return $this;
    }

    public function emboss()
    {
        $this->_checkDriverForFilters();
        $this->_driver->emboss();

        return $this;
    }

    public function blur()
    {
        $this->_checkDriverForFilters();
        $this->_driver->blur();

        return $this;
    }

    public function gaussianBlur()
    {
        $this->_checkDriverForFilters();
        $this->_driver->gaussianBlur();

        return $this;
    }

    public function removeMean()
    {
        $this->_checkDriverForFilters();
        $this->_driver->removeMean();

        return $this;
    }

    public function smooth($amount = null)
    {
        $this->_checkDriverForFilters();

        $amount = $this->_normalizePercentage($amount ?? 50);
        $this->_driver->smooth($amount);

        return $this;
    }


    // Driver
    protected function _checkDriverForManipulations()
    {
        if (!$this->_driver instanceof IImageManipulationDriver) {
            throw Exceptional::Unsupported(
                'Raster image driver ' . $this->_driver->getName() . ' does not support manipulations'
            );
        }
    }

    protected function _checkDriverForFilters()
    {
        if (!$this->_driver instanceof IImageFilterDriver) {
            throw Exceptional::Unsupported(
                'Raster image driver ' . $this->_driver->getName() . ' does not support filters'
            );
        }
    }


    // Normalizers
    protected function _normalizePixelSize($size, $dimension = null)
    {
        $size = core\unit\DisplaySize::factory($size);

        if (!$size->isAbsolute()) {
            $vpWidth = $this->getWidth();
            $vpHeight = $this->getHeight();
            $length = null;

            switch ($dimension) {
                case IDimension::WIDTH:
                    $length = $vpWidth;
                    break;

                case IDimension::HEIGHT:
                    $length = $vpHeight;
                    break;

                default:
                    $length = max($vpWidth, $vpHeight);
                    break;
            }

            $size = $size->extractAbsolute($length, null, $vpWidth, $vpHeight);
        }

        return $size->getPixels();
    }

    protected function _normalizeRelativeDimensions(&$width, &$height, &$currentWidth = null, &$currentHeight = null, bool $proportional = true)
    {
        $width = $this->_normalizePixelSize($width, IDimension::WIDTH);
        $height = $this->_normalizePixelSize($height, IDimension::HEIGHT);

        if ($currentWidth === null) {
            $currentWidth = $this->getWidth();
        }

        if ($currentHeight === null) {
            $currentHeight = $this->getHeight();
        }

        if (!$width && !$height) {
            $width = $currentWidth;
        }

        if (!$width || !$height) {
            if (!$width) {
                if ($proportional) {
                    $width = floor($currentWidth * $height / $currentHeight);
                } else {
                    $width = $height;
                }
            }

            if (!$height) {
                if ($proportional) {
                    $height = floor($currentHeight * $width / $currentWidth);
                } else {
                    $height = $width;
                }
            }
        }
    }

    protected function _normalizePosition($x, $y, $compositeWidth = null, $compositeHeight = null)
    {
        $position = core\unit\DisplayPosition::factory($x, $y)->extractAbsolute(
            $this->getWidth(),
            $this->getHeight(),
            $compositeWidth,
            $compositeHeight
        );

        return [$position->getXOffset()->getPixels(), $position->getYOffset()->getPixels()];
    }

    protected function _normalizePercentage($percent, $ignoreLowBound = false, $ignoreHighBound = false)
    {
        if (empty($percent)) {
            return null;
        }

        $percent = (float)trim($percent, '%');

        if (!$ignoreLowBound && $percent < 0) {
            $percent = 0;
        }

        if (!$ignoreHighBound && $percent > 100) {
            $percent = 100;
        }

        return $percent;
    }

    protected function _normalizeAngle($angle)
    {
        return core\unit\Angle::factory($angle)->normalize();
    }

    protected function _normalizeDimension($dimension)
    {
        switch ($dimension) {
            case IDimension::HEIGHT:
                return IDimension::HEIGHT;

            case IDimension::WIDTH:
            default:
                return IDimension::WIDTH;
        }
    }

    protected function _normalizeResizeMode(?string $mode, string $default = null)
    {
        switch ($mode) {
            case IDimension::STRETCH:
            case IDimension::FIT:
            case IDimension::PROPORTIONAL:
                return $mode;

            default:
                if ($default !== null) {
                    return $this->_normalizeResizeMode($default);
                }

                return IDimension::PROPORTIONAL;
        }
    }

    protected function _normalizeColor($color, $default = null)
    {
        if (empty($color)) {
            if ($default === null) {
                return null;
            }

            $color = $default;
        }

        return Color::create($color);
    }




    // Formats
    public const FORMATS = [
        'AAI' => 'AAI Dune image',
        'ART' => 'PFS: 1st Publisher Format originally used on the Macintosh (MacPaint?) and later used for PFS: 1st Publisher clip art.',
        'ARW' => 'Sony Digital Camera Alpha Raw Image Format',
        'AVI' => 'Microsoft Audio/Visual Interleaved',
        'AVS' => 'AVS X image',
        'BMP' => 'Microsoft Windows bitmap version 4',
        'BMP3' => 'Microsoft Windows bitmap    version 3',
        'BMP2' => 'Microsoft Windows bitmap version 2',
        'CALS' => 'Continuous Acquisition and Life-cycle Support Type 1 image',
        'CGM' => 'Computer Graphics Metafile',
        'CIN' => 'Kodak Cineon Image Format',
        'CMYK' => 'Raw cyan, magenta, yellow, and black samples',
        'CMYKA' => 'Raw cyan, magenta, yellow, black, and alpha samples',
        'CR2' => 'Canon Digital Camera Raw Image Format',
        'CRW' => 'Canon Digital Camera Raw Image Format',
        'CUR' => 'Microsoft Cursor Icon',
        'CUT' => 'DR Halo',
        'DCM' => 'Digital Imaging and Communications in Medicine (DICOM) image',
        'DCR' => 'Kodak Digital Camera Raw Image File',
        'DCX' => 'ZSoft IBM PC multi-page Paintbrush image',
        'DIB' => 'Microsoft Windows Device Independent Bitmap',
        'DJVU' => 'AT&T Labs Deja Vu format',
        'DNG' => 'Digital Negative',
        'DOT' => 'Graph Visualization',
        'DPX' => 'SMPTE Digital Moving Picture Exchange 2.0 (SMPTE 268M-2003)',
        'EMF' => 'Microsoft Enhanced Metafile (32-bit)',
        'EPDF' => 'Encapsulated Portable Document Format',
        'EPI' => 'Adobe Encapsulated PostScript Interchange format',
        'EPS' => 'Adobe Encapsulated PostScript',
        'EPS2' => 'Adobe Level II Encapsulated PostScript',
        'EPS3' => 'Adobe Level III Encapsulated PostScript',
        'EPSF' => 'Adobe Encapsulated PostScript',
        'EPSI' => 'Adobe Encapsulated PostScript Interchange format',
        'EPT' => 'Adobe Encapsulated PostScript Interchange format with TIFF preview',
        'EXR' => 'High dynamic-range (HDR) file format developed by Industrial Light & Magic',
        'FAX' => 'Group 3 TIFF',
        'FIG' => 'FIG graphics format',
        'FITS' => 'Flexible Image Transport System',
        'FPX' => 'FlashPix Format',
        'GIF' => 'CompuServe Graphics Interchange Format',
        'GPLT' => 'Gnuplot plot files',
        'GRAY' => 'Raw gray samples',
        'HDR' => 'Radiance RGBE image format',
        'HPGL' => 'HP-GL plotter language',
        'HRZ' => 'Slow Scane TeleVision',
        'HTML' => 'Hypertext Markup Language with a client-side image map',
        'ICO' => 'Microsoft icon',
        'INFO' => 'Format and characteristics of the image',
        'INLINE' => 'Base64-encoded inline image',
        'JBIG' => 'Joint Bi-level Image experts Group file interchange format',
        'JNG' => 'Multiple-image Network Graphics',
        'JP2' => 'JPEG-2000 JP2 File Format Syntax',
        'JPC' => 'JPEG-2000 Code Stream Syntax',
        'JPEG' => 'Joint Photographic Experts Group JFIF format',
        'MAN' => 'Unix reference manual pages',
        'MAT' => 'MATLAB image format',
        'MIFF' => 'Magick image file format',
        'MONO' => 'Bi-level bitmap in least-significant-byte first order',
        'MNG' => 'Multiple-image Network Graphics',
        'M2V' => 'Motion Picture Experts Group file interchange format (version 2)',
        'MPEG' => 'Motion Picture Experts Group file interchange format (version 1)',
        'MPC' => 'Magick Persistent Cache image file format',
        'MPR' => 'Magick Persistent Registry',
        'MRW' => 'Sony (Minolta) Raw Image File',
        'MSL' => 'Magick Scripting Language    MSL',
        'MTV' => 'MTV Raytracing image format',
        'MVG' => 'Magick Vector Graphics',
        'NEF' => 'Nikon Digital SLR Camera Raw Image File',
        'ORF' => 'Olympus Digital Camera Raw Image File',
        'OTB' => 'On-the-air Bitmap',
        'P7' => 'Xv\'s Visual Schnauzer thumbnail format',
        'PALM' => 'Palm pixmap',
        'PAM' => 'Common 2-dimensional bitmap format',
        'CLIPBOARD' => 'Windows Clipboard',
        'PBM' => 'Portable bitmap format (black and white)',
        'PCD' => 'Photo CD',
        'PCDS' => 'Photo CD with sRGB color',
        'PCL' => 'HP Page Control Language',
        'PCX' => 'ZSoft IBM PC Paintbrush file',
        'PDB' => 'Palm Database ImageViewer Format',
        'PDF' => 'Portable Document Format',
        'PEF' => 'Pentax Electronic File',
        'PFA' => 'Postscript Type 1 font (ASCII)',
        'PFB' => 'Postscript Type 1 font (binary)',
        'PFM' => 'Portable float map format',
        'PGM' => 'Portable graymap format',
        'PICON' => 'Personal Icon',
        'PICT' => 'Apple Macintosh QuickDraw/PICT file',
        'PIX' => 'Alias/Wavefront RLE image format',
        'PNG' => 'Portable Network Graphics',
        'PNG8' => 'Portable Network Graphics',
        'PNG24' => 'Portable Network Graphics',
        'PNG32' => 'Portable Network Graphics',
        'PNM' => 'Portable anymap',
        'PPM' => 'Portable pixmap format (color)',
        'PS' => 'Adobe PostScript file',
        'PS2' => 'Adobe Level II PostScript file',
        'PS3' => 'Adobe Level III PostScript file',
        'PSB' => 'Adobe Large Document Format',
        'PSD' => 'Adobe Photoshop bitmap file',
        'PTIF' => 'Pyramid encoded TIFF',
        'PWP' => 'Seattle File Works multi-image file',
        'RAD' => 'Radiance image file',
        'RAF' => 'Fuji CCD-RAW Graphic File',
        'RGB' => 'Raw red, green, and blue samples',
        'RGBA' => 'Raw red, green, blue, and alpha samples',
        'RLA' => 'Alias/Wavefront image file',
        'RLE' => 'Utah Run length encoded image file',
        'SCT' => 'Scitex Continuous Tone Picture',
        'SFW' => 'Seattle File Works image',
        'SGI' => 'Irix RGB image',
        'SHTML' => 'Hypertext Markup Language client-side image map',
        'SID' => 'Multiresolution seamless image',
        'MrSID' => 'Multiresolution seamless image',
        'SUN' => 'SUN Rasterfile',
        'SVG' => 'Scalable Vector Graphics',
        'TGA' => 'Truevision Targa image',
        'TIFF' => 'Tagged Image File Format',
        'TIM' => 'PSX TIM file',
        'TTF' => 'TrueType font file',
        'TXT' => 'Raw text file',
        'UIL' => 'X-Motif UIL table',
        'UYVY' => 'Interleaved YUV raw image',
        'VICAR' => 'VICAR rasterfile format',
        'VIFF' => 'Khoros Visualization Image File Format',
        'WBMP' => 'Wireless bitmap',
        'WEBP' => 'Weppy image format',
        'WMF' => 'Windows Metafile',
        'WPG' => 'Word Perfect Graphics File',
        'X' => 'Display or import an image to or from an X11 server',
        'XBM' => 'X Windows system bitmap',
        'XCF' => 'GIMP image',
        'XPM' => 'X Windows system pixmap',
        'XWD' => 'X Windows system window dump',
        'X3F' => 'Sigma Camera RAW Picture File',
        'YCbCr' => 'Raw Y, Cb, and Cr samples',
        'YCbCrA' => 'Raw Y, Cb, Cr, and alpha samples',
        'YUV' => 'CCIR 601 4:1:1'
    ];


    public const EXTENSIONS = [
        'srf' => 'ARW',
        'sr2' => 'ARW',
        'cal' => 'CALS',
        'dcl' => 'CALS',
        'ras' => 'CALS',
        'htm' => 'HTML'
    ];


    public static function getFormatFromPath($path)
    {
        $p = pathinfo($path);

        if (isset($p['extension'])) {
            return self::getFormatFromExtension($p['extension']);
        }

        throw Exceptional::Format(
            'Format could not be extracted from path: ' . $path
        );
    }

    public static function getFormatFromExtension($extension)
    {
        if (isset(self::FORMATS[strtoupper($extension)])) {
            return self::FORMATS[strtoupper($extension)];
        }

        $extension = strtolower($extension);

        throw Exceptional::Format(
            'Format could not be extracted from extension: ' . $extension
        );
    }

    public static function getFormatFromMime($mime)
    {
        Glitch::incomplete($mime);
    }

    public static function isFormatValid($format)
    {
        return isset(self::FORMATS[$format]);
    }

    public static function getFormatDescriptionFor($format)
    {
        if (isset(self::FORMATS[$format])) {
            return self::FORMATS[$format];
        }
    }
}

<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\raster;

use df;
use df\core;
use df\neon;
    
class Format implements IFormat {

    public static function fromPath($path) {
    	$p = pathinfo($path);

    	if(isset($p['extension'])) {
    		return self::fromExtension($p['extension']);
    	}

    	throw new RuntimeException(
    		'Format could not be extracted from path: '.$path
		);
    }

    public static function fromExtension($extension) {
    	if(isset(self::$_formats[strtoupper($extension)])) {
    		return self::$_formats[strtoupper($extension)];
    	}

    	$extension = strtolower($extension);

    	throw new RuntimeException(
    		'Format could not be extracted from extension: '.$extension
		);
    }

	public static function fromMime($mime) {
    	core\stub($path);
    }

	public static function isValid($format) {
    	return isset(self::$_formats[$format]);
    }

    public static function getDescriptionFor($format) {
    	if(isset(self::$_formats[$format])) {
    		return self::$_formats[$format];
    	}
    }


    protected static $_formats = [
    	'AAI' => 'AAI Dune image',
    	'ART' => 'PFS: 1st Publisher Format originally used on the Macintosh (MacPaint?) and later used for PFS: 1st Publisher clip art.',
		'ARW' => 'Sony Digital Camera Alpha Raw Image Format',
		'AVI' => 'Microsoft Audio/Visual Interleaved',
		'AVS' => 'AVS X image',
		'BMP' => 'Microsoft Windows bitmap version 4', 
		'BMP3' => 'Microsoft Windows bitmap	version 3',
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
		'MSL' => 'Magick Scripting Language	MSL',
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


    protected static $_extensions = [
    	'srf' => 'ARW',
    	'sr2' => 'ARW',
    	'cal' => 'CALS',
    	'dcl' => 'CALS',
    	'ras' => 'CALS',
    	'htm' => 'HTML'
    ];
}
<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\mime;

use df;
use df\core;

class Type {

    protected static $types = array(
        'ez' => 'application/andrew-inset',
        'hqx' => 'application/mac-binhex40',
        'cpt' => 'application/mac-compactpro',
        'bin' => 'application/octet-stream',
        'dms' => 'application/octet-stream',
        'lha' => 'application/octet-stream',
        'lzh' => 'application/octet-stream',
        'exe' => 'application/octet-stream',
        'class' => 'application/octet-stream',
        'so' => 'application/octet-stream',
        'dll' => 'application/octet-stream',
        'dmg' => 'application/octet-stream',
        'oda' => 'application/oda',
        'smi' => 'application/smil',
        'smil' => 'application/smil',
        'gram' => 'application/srgs',
        'grxml' => 'application/srgs+xml',
        'mif' => 'application/vnd.mif',
        'xul' => 'application/vnd.mozilla.xul+xml',
        'wbxml' => 'application/vnd.wap.wbxml',
        'wmlc' => 'application/vnd.wap.wmlc',
        'wmlsc' => 'application/vnd.wap.wmlscriptc',
        'vxml' => 'application/voicexml+xml',
        'bcpio' => 'application/x-bcpio',
        'pgn' => 'application/x-chess-pgn',
        'cpio' => 'application/x-cpio',
        'csh' => 'application/x-csh',
        'hdf' => 'application/x-hdf',
        'skp' => 'application/x-koan',
        'skd' => 'application/x-koan',
        'skt' => 'application/x-koan',
        'skm' => 'application/x-koan',
        'latex' => 'application/x-latex',
        'nc' => 'application/x-netcdf',
        'cdf' => 'application/x-netcdf',
        'sh' => 'application/x-sh',
        'shar' => 'application/x-shar',
        'sv4cpio' => 'application/x-sv4cpio',
        'sv4crc' => 'application/x-sv4crc',
        'tcl' => 'application/x-tcl',
        'tex' => 'application/x-tex',
        'texinfo' => 'application/x-texinfo',
        'texi' => 'application/x-texinfo',
        't' => 'application/x-troff',
        'tr' => 'application/x-troff',
        'roff' => 'application/x-troff',
        'man' => 'application/x-troff-man',
        'me' => 'application/x-troff-me',
        'ms' => 'application/x-troff-ms',
        'ustar' => 'application/x-ustar',
        'src' => 'application/x-wais-source',
        'xspf' => 'application/xspf+xml',
    
        
    // Archives
        'gtar' => 'application/x-gtar',
        'rar' => 'application/x-rar-compessed',
        'sit' => 'application/x-stuffit',
        'tar' => 'application/x-tar',
        'zip' => 'application/zip',
    
    // Web scripts
        'atom' => 'application/atom+xml',
        'json' => 'application/json',
        'mathml' => 'application/mathml+xml',
        'rdf' => 'application/rdf+xml', 
        'xhtml' => 'application/xhtml+xml',
        'xht' => 'application/xhtml+xml',
        'xslt' => 'application/xslt+xml',
        'xml' => 'application/xml',
        'xsl' => 'application/xml',
        'dtd' => 'application/xml-dtd',
    
    // Office
        'doc' => 'application/msword',
        'xls' => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',
        'opd' => 'application/vnd.oasis.opendocument.presentation',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        'odt' => 'application/vnd.oasis.opendocument.text',
        'odg' => 'application/vnd.oasis.opendocument.graphics',
    
    // Multimedia
        'anx' => 'application/annodex',
        'ogx' => 'application/ogg',
        'pdf' => 'application/pdf',
        'ai' => 'application/postscript',
        'eps' => 'application/postscript',
        'ps' => 'application/postscript',
        'dcr' => 'application/x-director',
        'dir' => 'application/x-director',
        'dxr' => 'application/x-director',
        'dvi' => 'application/x-dvi',
        'spl' => 'application/x-futuresplash',
        'vcd' => 'application/x-cdlink',    
        'swf' => 'application/x-shockwave-flash',
    
    // Audio
        '3gp' => 'audio/3gpp',
        '3gpp' => 'audio/3gpp',
        'axa' => 'audio/annodex',
        'au' => 'audio/basic',
        'snd' => 'audio/basic',
        'flac' => 'audio/flac',
        'mid' => 'audio/midi',
        'midi' => 'audio/midi',
        'kar' => 'audio/midi',
        'mpga' => 'audio/mpeg',
        'mp2' => 'audio/mpeg',
        'mp3' => 'audio/mpeg',
        'aif' => 'audio/x-aiff',
        'aiff' => 'audio/x-aiff',
        'aifc' => 'audio/x-aiff',
        'm4a' => 'audio/x-m4a',
        'm4b' => 'audio/x-m4b',
        'm4p' => 'audio/x-m4p',
        'm3u' => 'audio/x-mpegurl',
        'oga' => 'audio/ogg',
        'ogg' => 'audio/ogg',
        'spx' => 'audio/ogg',
        'ram' => 'audio/x-pn-realaudio',
        'ra' => 'audio/x-pn-realaudio',
        'wma' => 'audio/x-ms-wma',
        'wax' => 'audio/x-ms-wax',
        'rm' => 'application/vnd.rn-realmedia',
        'wav' => 'audio/x-wav',
    
    // Chemical
        'pdb' => 'chemical/x-pdb',
        'xyz' => 'chemical/x-xyz',
    
    // Image
        'art' => 'image/x-jg',
        'arw' => 'image/sonyrawfile',
        'bmp' => 'image/bmp',
        'cgm' => 'image/cgm',
        'gif' => 'image/gif',
        'ief' => 'image/ief',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'jpe' => 'image/jpeg',
        'pict' => 'image/pict',
        'pic' => 'image/pict',
        'pct' => 'image/pict',
        'png' => 'image/png',
        'svg' => 'image/svg+xml',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff',
        'djvu' => 'image/vnd.djvu',
        'djv' => 'image/vnd.djvu',
        'wbmp' => 'image/vnd.wap.wbmp',
        'ras' => 'image/x-cmu-raster',
        'ico' => 'image/x-icon',
        'pnm' => 'image/x-portable-anymap',
        'pbm' => 'image/x-portable-bitmap',
        'pgm' => 'image/x-portable-graymap',
        'ppm' => 'image/x-portable-pixmap',
        'targa' => 'image/x-targa',
        'tga' => 'image/x-targa',
        'qti' => 'image/x-quicktime',
        'qtif' => 'image/x-quicktime',
        'rgb' => 'image/x-rgb',
        'xbm' => 'image/x-xbitmap',
        'xpm' => 'image/x-xpixmap',
        'xwd' => 'image/x-xwindowdump',
    
    // Model
        'igs' => 'model/iges',
        'iges' => 'model/iges',
        'msh' => 'model/mesh',
        'mesh' => 'model/mesh',
        'silo' => 'model/mesh',
        'wrl' => 'model/vrml',
        'vrml' => 'model/vrml',
    
    // Text
        'ics' => 'text/calendar',
        'ifb' => 'text/calendar',
        'css' => 'text/css',
        'csv' => 'text/csv',
        'html' => 'text/html',
        'htm' => 'text/html',
        'js' => 'text/javascript',
        'asc' => 'text/plain',
        'txt' => 'text/plain',
        'rtx' => 'text/richtext',
        'rtf' => 'text/rtf',
        'sgml' => 'text/sgml',
        'sgm' => 'text/sgml',
        'tsv' => 'text/tab-separated-values',
        'wml' => 'text/vnd.wap.wml',
        'wmls' => 'text/vnd.wap.wmlscript',
        'etx' => 'text/x-setext',
    
    // Video
        '3g2' => 'video/3ppg2',
        '3gp2' => 'video/3ppg2',
        'axv' => 'video/annodex',
        'mpeg' => 'video/mpeg',
        'mpg' => 'video/mpeg',
        'mpe' => 'video/mpeg',
        'mp4' => 'video/mp4',
        'mpg4' => 'video/mp4',
        'qt' => 'video/quicktime',
        'mov' => 'video/quicktime',
        'mxu' => 'video/vnd.mpegurl',
        'm4u' => 'video/vnd.mpegurl',
        'webm' => 'video/webm',
        'flv' => 'video/x-flv',
        'avi' => 'video/x-msvideo',
        'movie' => 'video/x-sgi-movie',
        'asf' => 'video/x-ms-asf',
        'asx' => 'video/x-ms-asf',
        'wm' => 'video/x-ms-wm',
        'wvx' => 'video/x-ms-wvx',
        'ice' => 'x-conference/x-cooltalk',
    );

    public static function extToMime($extension, $default='application/octet-stream') {
        $extension = strtolower($extension);
        
        if(array_key_exists($extension, self::$types)) {
            return self::$types[$extension];
        } else {
            return $default;
        }
    }

    public static function fileToMime($file) {
        $parts = pathinfo((string)$file);
        
        if(!isset($parts['extension'])) {
            return 'application/octet-stream';  
        }
        
        return self::extToMime($parts['extension']);
    }

}
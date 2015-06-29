<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\archive;

use df;
use df\core;

class Gz extends Base {
    
    public function __construct() {
        if(!extension_loaded('zlib')) {
            throw new RuntimeException(
                'The zlib extension is not loaded'
            );
        }
    }

    public function extractFile($file, $destDir=null, $flattenRoot=false) {
        $destFile = null;

        if($destDir !== null) {
            $destFile = $destDir.'/'.$this->_getDecompressFileName($file, 'gz');
        }

        return dirname($this->decompressFile($file, $destFile));
    }

    public function decompressFile($file, $destFile=null) {
        $destFile = $this->_normalizeDecompressDestination($file, $destFile, 'gz');

        if(!$archive = fopen($file, 'rb')) {
            throw new RuntimeException(
                'Unable to open gz file: '.$file
            );
        }

        fseek($archive, -4, \SEEK_END);
        $packet = fread($archive, 4);
        $bytes = unpack('V', $packet);
        $size = end($bytes);
        fclose($archive);


        $output = fopen($destFile, 'w');
        $archive = gzopen($file, 'r');
        $block = 1024;

        while($size > 0) {
            if($block > $size) {
                $block = $size;
            }

            $size -= $block;
            fwrite($output, gzread($archive, $block));
        }

        gzclose($archive);
        fclose($output);

        return $destFile;
    }

    public function compressString($string) {
        // TODO: support for inflate, level option
        $output = gzcompress($string, 9);

        if($output === false) {
            throw new RuntimeException(
                'Unable to compress bz string'
            );
        }

        return $output;
    }

    public function decompressString($string) {
        // TODO: support for inflate
        $output = gzuncompress($string);

        if($output === false) {
            throw new RuntimeException(
                'Unable to decompress gz string, appears invalid'
            );
        }

        return $output;
    }
}
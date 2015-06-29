<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\archive;

use df;
use df\core;

class Bz2 extends Base {
    
    public function __construct() {
        if(!extension_loaded('bz2')) {
            throw new RuntimeException(
                'The bz2 extension is not loaded'
            );
        }
    }

    public function extractFile($file, $destDir=null, $flattenRoot=false) {
        $destFile = null;

        if($destDir !== null) {
            $destFile = $destDir.'/'.$this->_getDecompressFileName($file, 'bz2');
        }

        return dirname($this->decompressFile($file, $destFile));
    }

    public function decompressFile($file, $destFile=null) {
        $destFile = $this->_normalizeDecompressDestination($file, $destFile, 'bz2');
        
        $output = fopen($destFile, 'w');
        $archive = bzopen($file, 'r');
        $block = 1024;

        while($size > 0) {
            if($block > $size) {
                $block = $size;
            }

            $size -= $block;
            fwrite($output, bzread($archive, $block));
        }

        bzclose($archive);
        fclose($output);

        return $destFile;
    }

    public function compressString($string) {
        $output = bzcompress($string, 4);

        if(is_int($output)) {
            throw new RuntimeException(
                'Unable to compress bz string'
            );
        }

        return $output;
    }

    public function decompressString($string) {
        $output = bzdecompress($string);

        if(is_int($output)) {
            throw new RuntimeException(
                'Unable to decompress bz string, appears invalid'
            );
        }

        return $output;
    }
}
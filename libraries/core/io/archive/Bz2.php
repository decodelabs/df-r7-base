<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\io\archive;

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

    public function decompressFile($file, $destination=null, $flattenRoot=false) {
        $destination = $this->_normalizeExtractDestination($file, $destination);
        
        $fileName = basename($file);

        if(strtolower(substr($fileName, -4)) == '.bz2') {
            $fileName = substr($fileName, 0, -4);
        } else {
            throw new RuntimeException(
                'Unable to extract file name from '.$file
            );
        }

        $output = fopen($destination.'/'.$fileName, 'w');
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

        if($flattenRoot) {
            $this->_flattenRoot($destination);
        }

        return $destination;
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
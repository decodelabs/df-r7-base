<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\archive;

use df;
use df\core;

class Lzf extends Base {
    
    public function __construct() {
        if(!extension_loaded('lzf')) {
            throw new RuntimeException(
                'The lzf extension is not loaded'
            );
        }
    }

    public function compressString($string) {
        $output = lzf_compress($string);

        if($output === false) {
            throw new RuntimeException(
                'Unable to compress lzf string'
            );
        }

        return $output;
    }

    public function decompressString($string) {
        $output = lzf_decompress($string);

        if($output === false) {
            throw new RuntimeException(
                'Unable to decompress lzf string, appears invalid'
            );
        }

        return $output;
    }
}
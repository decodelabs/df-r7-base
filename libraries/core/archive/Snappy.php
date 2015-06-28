<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\archive;

use df;
use df\core;

class Snappy extends Base {
    
    public function __construct() {
        if(!extension_loaded('snappy')) {
            throw new RuntimeException(
                'The snappy extension is not loaded'
            );
        }
    }

    public function compressString($string) {
        $output = snappy_compress($string);

        if($output === false) {
            throw new RuntimeException(
                'Unable to compress snappy string'
            );
        }

        return $output;
    }

    public function decompressString($string) {
        $output = snappy_uncompress($string);

        if($output === false) {
            throw new RuntimeException(
                'Unable to decompress snappy string, appears invalid'
            );
        }

        return $output;
    }
}
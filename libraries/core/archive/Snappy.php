<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\archive;

use df;
use df\core;

use DecodeLabs\Exceptional;

class Snappy extends Base
{
    public function __construct()
    {
        if (!extension_loaded('snappy')) {
            throw Exceptional::Unsupported(
                'The snappy extension is not loaded'
            );
        }
    }

    public function compressString(string $string): string
    {
        $output = snappy_compress($string);

        if ($output === false) {
            throw Exceptional::Runtime(
                'Unable to compress snappy string'
            );
        }

        return $output;
    }

    public function decompressString(string $string): string
    {
        $output = snappy_uncompress($string);

        if ($output === false) {
            throw Exceptional::Runtime(
                'Unable to decompress snappy string, appears invalid'
            );
        }

        return $output;
    }
}

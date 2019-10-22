<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\archive;

use df;
use df\core;

class Bz2 extends Base
{
    public function __construct()
    {
        if (!extension_loaded('bz2')) {
            throw core\Error::EUnsupported(
                'The bz2 extension is not loaded'
            );
        }
    }

    public function extractFile(string $file, string $destDir=null, bool $flattenRoot=false): string
    {
        $destFile = null;

        if ($destDir !== null) {
            $destFile = $destDir.'/'.$this->_getDecompressFileName($file, 'bz2');
        }

        return dirname($this->decompressFile($file, $destFile));
    }

    public function decompressFile(string $file, string $destFile=null): string
    {
        $destFile = $this->_normalizeDecompressDestination($file, $destFile, 'bz2');

        $output = fopen($destFile, 'w');
        $archive = bzopen($file, 'r');

        while (!feof($archive)) {
            fwrite($output, bzread($archive, 4096));
        }

        bzclose($archive);
        fclose($output);

        return $destFile;
    }

    public function compressString(string $string): string
    {
        return bzcompress($string, 4);
    }

    public function decompressString(string $string): string
    {
        return bzdecompress($string);
    }
}

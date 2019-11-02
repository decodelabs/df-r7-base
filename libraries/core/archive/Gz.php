<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\archive;

use df;
use df\core;

use DecodeLabs\Glitch;

class Gz extends Base
{
    public function __construct()
    {
        if (!extension_loaded('zlib')) {
            throw Glitch::EUnsupported(
                'The zlib extension is not loaded'
            );
        }
    }

    public function extractFile(string $file, string $destDir=null, bool $flattenRoot=false): string
    {
        $destFile = null;

        if ($destDir !== null) {
            $destFile = $destDir.'/'.$this->_getDecompressFileName($file, 'gz');
        }

        return dirname($this->decompressFile($file, $destFile));
    }

    public function decompressFile(string $file, string $destFile=null): string
    {
        $destFile = $this->_normalizeDecompressDestination($file, $destFile, 'gz');

        if (false === ($archive = fopen($file, 'rb'))) {
            throw Glitch::ENotFound(
                'Unable to open gz file: '.$file
            );
        }

        fseek($archive, -4, \SEEK_END);
        $packet = fread($archive, 4);
        $bytes = unpack('V', (string)$packet);
        $size = end($bytes);
        fclose($archive);


        if (false === ($output = fopen($destFile, 'w'))) {
            throw Glitch::ERuntime('Unable to open gz file for writing', null, $destFile);
        }

        if (false === ($archive = gzopen($file, 'r'))) {
            throw Glitch::ERuntime('Unable to open gz file', null, $file);
        }

        $block = 1024;

        while ($size > 0) {
            if ($block > $size) {
                $block = $size;
            }

            $size -= $block;
            fwrite($output, gzread($archive, $block));
        }

        gzclose($archive);
        fclose($output);

        return $destFile;
    }

    public function compressString(string $string): string
    {
        // TODO: support for inflate, level option
        $output = gzcompress($string, 9);

        if ($output === false) {
            throw Glitch::ERuntime(
                'Unable to compress bz string'
            );
        }

        return $output;
    }

    public function decompressString(string $string): string
    {
        // TODO: support for inflate
        $output = gzuncompress($string);

        if ($output === false) {
            throw Glitch::ERuntime(
                'Unable to decompress gz string, appears invalid'
            );
        }

        return $output;
    }
}

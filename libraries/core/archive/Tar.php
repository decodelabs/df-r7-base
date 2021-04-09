<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\archive;

use df;
use df\core;

use DecodeLabs\Atlas;

class Tar extends Base
{
    public function extractFile(string $file, string $destination=null, bool $flattenRoot=false): string
    {
        $destination = $this->_normalizeExtractDestination($file, $destination);
        $archive = new \PharData($file);

        if ($isGz = preg_match('/\.(gz|bz2)$/i', $file)) {
            $parts = explode('.', basename($file));
            array_pop($parts); // gz
            array_shift($parts); // name

            $archive->decompress(implode('.', $parts));
            $tarFile = substr($file, 0, -3);
        } else {
            $tarFile = null;
        }

        $archive->extractTo($destination);

        if ($isGz && $tarFile !== null) {
            Atlas::deleteFile($tarFile);
        }

        if ($flattenRoot) {
            $this->_flattenRoot($destination);
        }

        return $destination;
    }
}

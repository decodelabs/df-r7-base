<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\io\archive;

use df;
use df\core;

class Tar extends Base {
    
    public function decompressFile($file, $destination=null, $flattenRoot=false) {
        $destination = $this->_normalizeExtractDestination($file, $destination);
        $archive = new \PharData($file);

        if($isGz = (strtolower(substr($file, -3)) == '.gz')) {
            $parts = explode('.', basename($file));
            array_pop($parts); // gz
            array_shift($parts); // name

            $archive->decompress(implode('.', $parts));
            $tarFile = substr($file, 0, -3);
        }

        $archive->extractTo($destination);

        if($isGz) {
            core\io\Util::deleteFile($tarFile);
        }

        if($flattenRoot) {
            $this->_flattenRoot($destination);
        }
        
        return $destination;
    }
}
<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\archive;

use df;
use df\core;

class Rar extends Base {
    
    public function __construct() {
        if(!extension_loaded('rar')) {
            throw new RuntimeException(
                'The rar extension is not loaded'
            );
        }
    }

    public function decompressFile($file, $destination=null, $flattenRoot=false) {
        $destination = $this->_normalizeExtractDestination($file, $destination);
        
        // TODO: add password support

        if(!$archive = rar_open($file)) {
            throw new RuntimeException(
                'Unable to open rar archive: '.$file
            );
        }

        if(!$files = rar_list($archive)) {
            throw new RuntimeException(
                'Unable to read file list from rar archive: '.$file
            );
        }

        foreach($files as $file) {
            $file->extract($destination);
        }

        rar_close($archive);

        if($flattenRoot) {
            $this->_flattenRoot($destination);
        }

        return $destination;
    }
}
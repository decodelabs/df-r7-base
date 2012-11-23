<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\raster;

use df;
use df\core;
use df\neon;
    
class Cache extends core\cache\Base {

    const USE_DIRECT_FILE_BACKEND = true;
    const IS_DISTRIBUTED = false;
    const DEFAULT_LIFETIME = 86400;

    public function getTransformationFilePath($sourceFilePath, $transformation) {
        if(!$output = $this->getDirectFilePath($sourceFilePath)) {
            $image = Image::loadFile($sourceFilePath)->setOutputFormat('PNG24');
            $image->transform($transformation)->apply();

            $this->set($sourceFilePath, $image->toString());
            $output = $this->getDirectFilePath($sourceFilePath);
        }

        return $output;
    }
}
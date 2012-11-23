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
        $key = basename(dirname($sourceFilePath)).'_'.basename($sourceFilePath).'-'.md5($sourceFilePath.':'.$transformation);
        $mTime = filemtime($sourceFilePath);

        if($mTime > $this->getCreationTime($key)) {
            $this->remove($key);
        }


        if(!$output = $this->getDirectFilePath($key)) {
            $image = Image::loadFile($sourceFilePath)->setOutputFormat('PNG24');
            $image->transform($transformation)->apply();

            $this->set($key, $image->toString());
            $output = $this->getDirectFilePath($key);
        }

        return $output;
    }
}
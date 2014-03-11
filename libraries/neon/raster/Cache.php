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
        $keyPath = core\io\Util::stripLocationFromFilePath($sourceFilePath);
        $key = basename(dirname($keyPath)).'_'.basename($keyPath).'-'.md5($keyPath.':'.$transformation);
        $mTime = filemtime($sourceFilePath);

        if($mTime > $this->getCreationTime($key)) {
            $this->remove($key);
        }

        if(!$output = $this->getDirectFilePath($key)) {
            try {
                $image = Image::loadFile($sourceFilePath)->setOutputFormat('PNG32');
            } catch(FormatException $e) {
                $image = Image::newCanvas(100, 100, neon\Color::factory('black'));
            }

            $image->transform($transformation)->apply();

            $this->set($key, $image->toString());
            $output = $this->getDirectFilePath($key);
        }

        return $output;
    }
}
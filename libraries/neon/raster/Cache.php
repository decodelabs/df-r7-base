<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\raster;

use df;
use df\core;
use df\neon;
use df\link;
    
class Cache extends core\cache\Base {

    const USE_DIRECT_FILE_BACKEND = true;
    const IS_DISTRIBUTED = false;
    const DEFAULT_LIFETIME = 86400; // 1 day
    const URL_LIFETIME = 31536000; // 1 year

    public function getTransformationFilePath($sourceFilePath, $transformation, core\time\IDate $modificationDate=null) {
        $mTime = null;
        $isUrl = false;
        $lifetime = static::DEFAULT_LIFETIME;

        if($sourceFilePath instanceof link\http\IUrl) {
            $isUrl = true;
            $path = (string)$sourceFilePath->getPath();
            $key = basename(dirname($path)).'_'.basename($path).'-'.md5($sourceFilePath.':'.$transformation);

            if($modificationDate !== null) {
                $mTime = $modificationDate->toTimestamp();
            }

            $lifetime = static::URL_LIFETIME;
        } else {
            $keyPath = core\fs\Dir::stripPathLocation($sourceFilePath);
            $key = basename(dirname($keyPath)).'_'.basename($keyPath).'-'.md5($keyPath.':'.$transformation);
            $mTime = filemtime($sourceFilePath);
        }


        if($mTime !== null && $mTime > $this->getCreationTime($key)) {
            $this->remove($key);
        }

        if(!$output = $this->getDirectFilePath($key)) {
            if($isUrl) {
                $http = new link\http\peer\Client();
                $file = core\fs\File::createTemp();
                $response = $http->getFile($sourceFilePath, $file);

                if(!$response->isOk()) {
                    throw new RuntimeException(
                        'Unable to fetch remote image for transformation'
                    );
                }

                $sourceFilePath = $file->getPath();
            }

            try {
                $image = Image::loadFile($sourceFilePath)->setOutputFormat('PNG32');
            } catch(FormatException $e) {
                $image = Image::newCanvas(100, 100, neon\Color::factory('black'));
            }

            $image->transform($transformation)->apply();

            $this->set($key, $image->toString(), $lifetime);
            $output = $this->getDirectFilePath($key);

            if($isUrl) {
                $file->unlink();
            }
        }

        return $output;
    }
}
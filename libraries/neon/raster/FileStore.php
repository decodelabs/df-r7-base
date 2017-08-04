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

class FileStore extends core\cache\FileStore {

    const DEFAULT_LIFETIME = '1 day';
    const URL_LIFETIME = '1 month';

    public function getTransformationFilePath($sourceFilePath, $transformation, core\time\IDate $modificationDate=null): string {
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

        if(!$file = $this->get($key, $lifetime)) {
            if($isUrl) {
                $http = new link\http\Client();
                $download = core\fs\File::createTemp();
                $response = $http->getFile($sourceFilePath, $download);

                if(!$response->isOk()) {
                    throw new RuntimeException(
                        'Unable to fetch remote image for transformation'
                    );
                }

                $sourceFilePath = $download->getPath();
            }

            try {
                $image = Image::loadFile($sourceFilePath)->setOutputFormat('PNG32');
            } catch(FormatException $e) {
                $image = Image::newCanvas(100, 100, neon\Color::factory('black'));
            }

            $image->transform($transformation)->apply();

            $this->set($key, $image->toString());
            $file = $this->get($key);

            if($isUrl) {
                $download->unlink();
            }
        }

        return $file->getPath();
    }

    public static function createKey(string $sourceFilePath, $transformation=null): string {
        if($sourceFilePath instanceof link\http\IUrl) {
            $path = (string)$sourceFilePath->getPath();
            $key = basename(dirname($path)).'_'.basename($path).'-'.md5($sourceFilePath.':'.$transformation);
        } else {
            $keyPath = core\fs\Dir::stripPathLocation($sourceFilePath);
            $key = basename(dirname($keyPath)).'_'.basename($keyPath).'-'.md5($keyPath.':'.$transformation);
        }

        return $key;
    }

    public function getIconFilePath(string $absolutePath, int ...$sizes): string {
        $key = $this->createKey($absolutePath).'-ico';

        if(!$file = $this->get($key, self::DEFAULT_LIFETIME)) {
            $ico = new neon\raster\Ico($absolutePath, 16, 32);
            $this->set($key, $ico->generate());
            $file = $this->get($key);
        }

        return $file->getPath();
    }
}

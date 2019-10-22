<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\packaging\bower\resolver;

use df;
use df\core;
use df\spur;
use df\link;

use DecodeLabs\Atlas;

class FileSystem implements spur\packaging\bower\IResolver
{
    public function resolvePackageName(spur\packaging\bower\Package $package)
    {
        return $package->name;
    }

    public function fetchPackage(spur\packaging\bower\Package $package, $cachePath, $currentVersion=null)
    {
        if ($currentVersion) {
            return false;
        }

        if ($extension = core\uri\Path::extractExtension($package->url)) {
            $extension = '.'.$extension;
        }

        $package->cacheFileName = $package->name.'-'.md5($package->url).$extension;
        $package->version = time();

        Atlas::$fs->copyFile($package->url, $cachePath.'/packages/'.$package->cacheFileName);
        return true;
    }

    public function getTargetVersion(spur\packaging\bower\Package $package, $cachePath)
    {
        return 'latest';
    }
}

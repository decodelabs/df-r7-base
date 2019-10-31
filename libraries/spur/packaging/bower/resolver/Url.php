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

use DecodeLabs\Glitch;
use DecodeLabs\Atlas;

class Url implements spur\packaging\bower\IResolver
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

        $package->cacheFileName = $package->name.'-'.md5($package->url).'.zip';
        $package->version = time();

        Atlas::$http->getFile($package->url, $cachePath.'/packages/'.$package->cacheFileName);
        return true;
    }

    public function getTargetVersion(spur\packaging\bower\Package $package, $cachePath)
    {
        return 'latest';
    }
}

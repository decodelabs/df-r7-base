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

class FileSystem implements spur\packaging\bower\IResolver {
    
    public function fetchPackage(spur\packaging\bower\IPackage $package, $cachePath, $currentVersion=null) {
        if($currentVersion) {
            return false;
        }

        if($extension = core\uri\Path::extractExtension($package->url)) {
            $extension = '.'.$extension;
        }

        $package->cacheFileName = $package->name.'-'.md5($package->url).$extension;
        $package->version = time();

        core\fs\File::copy($package->url, $cachePath.'/'.$package->cacheFileName);
        return true;
    }
}
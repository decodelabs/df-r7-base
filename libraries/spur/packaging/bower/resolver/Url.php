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
use GuzzleHttp\Client as HttpClient;

class Url implements spur\packaging\bower\IResolver
{
    protected $_httpClient;

    public function __construct()
    {
        $this->_httpClient = new HttpClient();
    }

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

        $response = $this->_httpClient->get($package->url);
        $body = $response->getBody();
        $file = Atlas::$fs->file($cachePath.'/packages/'.$package->cacheFileName, 'wb');

        while (!$body->eof()) {
            $file->write($body->read(8192));
        }

        $file->close();

        return true;
    }

    public function getTargetVersion(spur\packaging\bower\Package $package, $cachePath)
    {
        return 'latest';
    }
}

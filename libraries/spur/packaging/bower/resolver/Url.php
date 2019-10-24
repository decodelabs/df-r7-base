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

class Url implements spur\packaging\bower\IResolver
{
    protected $_httpClient;

    public function __construct()
    {
        $this->_httpClient = new link\http\Client();
    }

    public function resolvePackageName(spur\packaging\bower\Package $package)
    {
        return $package->name;

        /*
        $url = link\http\Url::factory($package->url);
        return $url->path->getFileName();
        */
    }

    public function fetchPackage(spur\packaging\bower\Package $package, $cachePath, $currentVersion=null)
    {
        if ($currentVersion) {
            return false;
        }

        $package->cacheFileName = $package->name.'-'.md5($package->url).'.zip';
        $package->version = time();

        $response = $this->_httpClient->getFile(
            $package->url,
            $cachePath.'/packages/',
            $package->cacheFileName
        );

        if (!$response->isOk()) {
            throw Glitch::ERuntime(
                'Unable to fetch file: '.$package->url
            );
        }

        return true;
    }

    public function getTargetVersion(spur\packaging\bower\Package $package, $cachePath)
    {
        return 'latest';
    }
}

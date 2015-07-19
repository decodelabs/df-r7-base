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

class Url implements spur\packaging\bower\IResolver {
    
    protected $_httpClient;

    public function __construct() {
        $this->_httpClient = new link\http\peer\Client();
    }

    public function fetchPackage(spur\packaging\bower\IPackage $package, $cachePath, $currentVersion=null) {
        if($currentVersion) {
            return false;
        }

        $package->cacheFileName = $package->name.'-'.md5($package->url).'.zip';
        $package->version = time();

        $request = new link\http\request\Base($package->url);
        $request->setResponseFilePath($cachePath.'/'.$package->cacheFileName);
        $response = $this->_httpClient->sendRequest($request);

        if(!$response->isOk()) {
            throw new spur\packaging\bower\RuntimeException(
                'Unable to fetch file: '.$url
            );
        }

        return true;
    }
}
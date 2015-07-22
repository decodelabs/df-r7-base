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
        $this->_httpClient = new link\http\Client();
    }

    public function fetchPackage(spur\packaging\bower\IPackage $package, $cachePath, $currentVersion=null) {
        if($currentVersion) {
            return false;
        }

        $package->cacheFileName = $package->name.'-'.md5($package->url).'.zip';
        $package->version = time();

        $response = $this->_httpClient->getFile(
            $package->url, 
            $cachePath, 
            $package->cacheFileName
        );

        if(!$response->isOk()) {
            throw new spur\packaging\bower\RuntimeException(
                'Unable to fetch file: '.$url
            );
        }

        return true;
    }
}
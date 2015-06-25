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
use df\flex;

class Github implements spur\packaging\bower\IResolver {
    
    const TAG_TIMEOUT = '1 hour';

    protected $_mediator;

    public function __construct() {
        $this->_mediator = new spur\vcs\github\Mediator();
    }

    public function fetchPackage(spur\packaging\bower\IPackage $package, $cachePath, $currentVersion=null) {
        $repoName = $this->_extractRepoName($package);

        if($tag = $this->_getRequiredTag($package, $repoName, $cachePath)) {
            $url = $tag->getUrl('zipball');
            $version = $package->version = $tag->getVersion()->toString();
        } else {
            $branch = $this->_mediator->getRepositoryBranch($repoName, 'master');
            $url = $branch->getUrl('zipball');
            $version = $branch->getCommit()->getSha();
        }

        if($currentVersion !== null && $currentVersion == $package->version) {
            return false;
        }
        
        $package->cacheFileName = $package->name.'#'.$version.'.zip';

        if(is_file($cachePath.'/'.$package->cacheFileName)) {
            return true;
        }

        $http = $this->_mediator->getHttpClient();
        $request = new link\http\request\Base($url);
        $request->setResponseFilePath($cachePath.'/'.$package->cacheFileName);
        $response = $http->sendRequest($request);

        if(!$response->isOk()) {
            throw new spur\packaging\bower\RuntimeException(
                'Unable to fetch file: '.$url
            );
        }

        return true;
    }

    protected function _extractRepoName(spur\packaging\bower\IPackage $package) {
        if(!preg_match('/(?:@|:\/\/)github.com[:\/]([^\/\s]+?)\/([^\/\s]+?)(?:\.git)?\/?$/i', $package->url, $matches)) {
            throw new spur\packaging\bower\RuntimeException('Unable to extract repo name from url: '.$package->url);
        }

        return $matches[1].'/'.$matches[2];
    }

    protected function _getRequiredTag(spur\packaging\bower\IPackage $package, $repoName, $cachePath) {
        $range = core\string\VersionRange::factory($package->version);

        try {
            $tags = $this->_fetchTags($package, $repoName, $cachePath);
        } catch(spur\ApiError $e) {
            return false;
        }


        $singleVersion = $range->getSingleVersion();

        if(!$singleVersion || !$singleVersion->preRelease) {
            $temp = [];

            foreach($tags as $i => $tag) {
                $version = $tag->getVersion();

                if(!$version || $version->preRelease) {
                    continue;
                } else {
                    $temp[] = $tag;
                }
            }

            if(!empty($temp)) {
                $tags = $temp;
            }
        }

        if(empty($tags)) {
            return false;
        }

        foreach($tags as $tag) {
            if(($version = $tag->getVersion()) && $range->contains($version)) {
                return $tag;
            }
        }
        
        return false;
    }

    protected function _fetchTags(spur\packaging\bower\IPackage $package, $repoName, $cachePath) {
        $path = dirname($cachePath).'/tags/github-'.str_replace('/', '-', $repoName).'.json';

        if(!core\io\Util::isFileRecent($path, self::TAG_TIMEOUT)) {
            $tags = $this->_mediator->getRepositoryTags($repoName);

            @usort($tags, function($left, $right) {
                $leftVersion = $left->getVersion();
                $rightVersion = $right->getVersion();

                if(!$leftVersion && !$rightVersion) {
                    return 0;
                } else if(!$leftVersion && $rightVersion) {
                    return 1;
                } else if($leftVersion && !$rightVersion) {
                    return -1;
                }

                if($leftVersion->eq($rightVersion)) {
                    return 0;
                } else if($leftVersion->lt($rightVersion)) {
                    return 1;
                } else {
                    return -1;
                }
            });

            $data = [];

            foreach($tags as $tag) {
                $data[] = $tag->toArray();
            }

            core\io\Util::writeFileExclusive($path, flex\json\Codec::encode($data));
            return $tags;
        }
        
        $data = flex\json\Codec::decode(file_get_contents($path));
        $tags = [];

        foreach($data as $tag) {
            $tags[] = new spur\vcs\github\Tag($this->_mediator, new core\collection\Tree($tag));
        }

        return $tags;
    }
}
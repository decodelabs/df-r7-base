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

    use spur\packaging\bower\TGitResolver;

    const TAG_TIMEOUT = '5 hours';

    protected $_mediator;

    public function __construct() {
        $this->_mediator = new spur\vcs\github\Mediator();
    }

    public function resolvePackageName(spur\packaging\bower\IPackage $package) {
        $repoName = $this->_extractRepoName($package);
        $parts = explode('/', $repoName);
        return array_pop($parts);
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

        if(is_file($cachePath.'/packages/'.$package->cacheFileName)) {
            return true;
        }


        $http = $this->_mediator->getHttpClient();
        $response = $http->getFile($url, $cachePath.'/packages/', $package->cacheFileName);

        if(!$response->isOk()) {
            throw new spur\packaging\bower\RuntimeException(
                'Unable to fetch file: '.$url
            );
        }

        return true;
    }

    public function getTargetVersion(spur\packaging\bower\IPackage $package, $cachePath) {
        $repoName = $this->_extractRepoName($package);

        if(!$tag = $this->_getRequiredTag($package, $repoName, $cachePath)) {
            return 'latest';
        }

        return $tag->getVersion();
    }

    protected function _extractRepoName(spur\packaging\bower\IPackage $package) {
        if(!preg_match('/(?:@|:\/\/)github.com[:\/]([^\/\s]+?)\/([^\/\s]+?)(?:\.git)?\/?$/i', $package->url, $matches)) {
            throw new spur\packaging\bower\RuntimeException('Unable to extract repo name from url: '.$package->url);
        }

        return $matches[1].'/'.$matches[2];
    }

    protected function _getRequiredTag(spur\packaging\bower\IPackage $package, $repoName, $cachePath) {
        try {
            $tags = $this->_fetchTags($package, $repoName, $cachePath);
        } catch(EApi $e) {
            return false;
        }

        return $this->_findRequiredTag($tags, $package);
    }

    protected function _fetchTags(spur\packaging\bower\IPackage $package, $repoName, $cachePath) {
        $path = $cachePath.'/tags/github-'.str_replace('/', '-', $repoName).'.json';

        if(!core\fs\File::isFileRecent($path, self::TAG_TIMEOUT)) {
            $tags = $this->_mediator->getRepositoryTags($repoName);
            $tags = $this->_sortTags($tags);

            $data = [];

            foreach($tags as $tag) {
                $data[] = $tag->toArray();
            }

            flex\Json::toFile($path, $data);
            return $tags;
        }

        $data = flex\Json::fileToTree($path);
        $tags = [];

        foreach($data as $tag) {
            $tags[] = new spur\vcs\github\Tag($this->_mediator, $tag);
        }

        return $tags;
    }
}
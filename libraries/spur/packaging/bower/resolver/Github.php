<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\spur\packaging\bower\resolver;

use DecodeLabs\Atlas;
use DecodeLabs\Exceptional;
use DecodeLabs\Hydro;

use df\flex;
use df\spur;

class Github implements spur\packaging\bower\IResolver
{
    use spur\packaging\bower\TGitResolver;

    public const TAG_TIMEOUT = '5 hours';

    protected $_mediator;

    public function __construct()
    {
        $this->_mediator = new spur\vcs\github\Mediator();
    }

    public function resolvePackageName(spur\packaging\bower\Package $package)
    {
        $repoName = $this->_extractRepoName($package);
        $parts = explode('/', $repoName);
        return array_pop($parts);
    }

    public function fetchPackage(spur\packaging\bower\Package $package, $cachePath, $currentVersion = null)
    {
        $repoName = $this->_extractRepoName($package);

        if ($tag = $this->_getRequiredTag($package, $repoName, $cachePath)) {
            $url = $tag->getUrl('zipball');
            $version = $package->version = $tag->getVersion()->toString();
        } else {
            $branch = $this->_mediator->getRepositoryBranch($repoName, 'master');
            $url = $branch->getUrl('zipball');
            $version = $branch->getCommit()->getSha();
        }

        if ($currentVersion !== null && $currentVersion == $package->version) {
            return false;
        }

        $package->cacheFileName = $package->name . '#' . $version . '.zip';

        if (is_file($cachePath . '/packages/' . $package->cacheFileName)) {
            return true;
        }

        Hydro::getFile($url, $cachePath . '/packages/' . $package->cacheFileName);
        return true;
    }

    public function getTargetVersion(spur\packaging\bower\Package $package, $cachePath)
    {
        $repoName = $this->_extractRepoName($package);

        if (!$tag = $this->_getRequiredTag($package, $repoName, $cachePath)) {
            return 'latest';
        }

        return $tag->getVersion();
    }

    protected function _extractRepoName(spur\packaging\bower\Package $package)
    {
        if (!preg_match('/(?:@|:\/\/)github.com[:\/]([^\/\s]+?)\/([^\/\s]+?)(?:\.git)?\/?$/i', (string)$package->url, $matches)) {
            throw Exceptional::Runtime(
                'Unable to extract repo name from url: ' . $package->url
            );
        }

        return $matches[1] . '/' . $matches[2];
    }

    protected function _getRequiredTag(spur\packaging\bower\Package $package, $repoName, $cachePath)
    {
        try {
            $tags = $this->_fetchTags($package, $repoName, $cachePath);
        } catch (ApiException $e) {
            return false;
        }

        return $this->_findRequiredTag($tags, $package);
    }

    protected function _fetchTags(spur\packaging\bower\Package $package, $repoName, $cachePath)
    {
        $path = $cachePath . '/tags/github-' . str_replace('/', '-', $repoName) . '.json';

        if (!Atlas::hasFileChangedIn($path, self::TAG_TIMEOUT)) {
            $tags = $this->_mediator->getRepositoryTags($repoName);
            $tags = $this->_sortTags($tags);

            $data = [];

            foreach ($tags as $tag) {
                $data[] = $tag->toArray();
            }

            flex\Json::toFile($path, $data);
            return $tags;
        }

        $data = flex\Json::fileToTree($path);
        $tags = [];

        foreach ($data as $tag) {
            $tags[] = new spur\vcs\github\Tag($this->_mediator, $tag);
        }

        return $tags;
    }
}

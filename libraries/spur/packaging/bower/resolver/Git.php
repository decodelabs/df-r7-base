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

use DecodeLabs\Atlas;

class Git implements spur\packaging\bower\IResolver
{
    use spur\packaging\bower\TGitResolver;

    const TAG_TIMEOUT = '5 hours';

    protected $_remote;

    public function __construct()
    {
    }

    public function resolvePackageName(spur\packaging\bower\Package $package)
    {
        $parts = explode('/', $package->url);
        $name = (string)array_pop($parts);
        return substr($name, -4);
    }

    public function fetchPackage(spur\packaging\bower\Package $package, $cachePath, $currentVersion=null)
    {
        $this->_getRemote($package);

        if ($tag = $this->_getRequiredTag($package, $cachePath)) {
            $commitId = $tag->getCommitId();
            $version = $package->version = $tag->getVersion()->toString();
        } else {
            $heads = $this->_remote->getHeads();

            if (isset($heads['master'])) {
                $version = $commitId = $heads['master'];
            } else {
                $version = $commitId = array_pop($heads);
            }
        }

        if ($currentVersion !== null && $currentVersion == $package->version) {
            $this->_remote = null;
            return false;
        }

        $package->cacheFileName = $package->name.'#'.$version;

        if (!is_dir($cachePath.'/packages/'.$package->cacheFileName)) {
            $repo = $this->_remote->cloneTo($cachePath.'/packages/'.$package->cacheFileName);
            $repo->checkoutCommit($commitId);
        }

        $this->_remote = null;
        return true;
    }

    protected function _getRemote(spur\packaging\bower\Package $package)
    {
        if (!$this->_remote) {
            $this->_remote = new spur\vcs\git\Remote($package->url);
        }

        return $this->_remote;
    }

    public function getTargetVersion(spur\packaging\bower\Package $package, $cachePath)
    {
        if (!$tag = $this->_getRequiredTag($package, $cachePath)) {
            return 'latest';
        }

        return $tag->getVersion();
    }

    protected function _getRequiredTag(spur\packaging\bower\Package $package, $cachePath)
    {
        try {
            $tags = $this->_fetchTags($package, $cachePath);
        } catch (spur\vcs\git\Exception $e) {
            return false;
        }

        return $this->_findRequiredTag($tags, $package);
    }

    protected function _fetchTags(spur\packaging\bower\Package $package, $cachePath)
    {
        $path = $cachePath.'/tags/git-'.flex\Text::formatFileName($package->url).'.json';

        if (!Atlas::hasFileChangedIn($path, self::TAG_TIMEOUT)) {
            $tags = $this->_remote->getTags();
            $tags = $this->_sortTags($tags);

            $data = [];

            foreach ($tags as $tag) {
                $data[$tag->getName()] = $tag->getCommitId();
            }

            flex\Json::toFile($path, $data);
            return $tags;
        }

        $data = flex\Json::fileToTree($path);
        $tags = [];

        foreach ($data as $name => $commitId) {
            $tags[] = new spur\vcs\git\Tag($this->_remote, $name, (string)$commitId);
        }

        return $tags;
    }
}

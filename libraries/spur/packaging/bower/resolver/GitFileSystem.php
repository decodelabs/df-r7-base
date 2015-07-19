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

class GitFileSystem implements spur\packaging\bower\IResolver {
    
    use spur\packaging\bower\TGitResolver;

    protected $_repo;

    public function fetchPackage(spur\packaging\bower\IPackage $package, $cachePath, $currentVersion=null) {
        $this->_repo = new spur\vcs\git\Repository($package->url);

        if($tag = $this->_getRequiredTag($package)) {
            $commitId = $tag->getCommitId();
            $version = $package->version = $tag->getVersion()->toString();
        } else {
            $heads = $this->_repo->getHeads();

            if(isset($heads['master'])) {
                $version = $commitId = $heads['master'];
            } else {
                $version = $commitId = array_pop($heads);
            }
        }

        if($currentVersion !== null && $currentVersion == $package->version) {
            $this->_repo = null;
            return false;
        }

        $package->cacheFileName = $package->name.'#'.$version;

        if(!is_dir($cachePath.'/'.$package->cacheFileName)) {
            $repo = $this->_repo->cloneTo($cachePath.'/'.$package->cacheFileName);
            $repo->checkoutCommit($commitId);
        }

        $this->_repo = null;
        return true;
    }

    protected function _getRequiredTag(spur\packaging\bower\IPackage $package) {
        $tags = $this->_repo->getTags();
        $tags = $this->_sortTags($tags);

        return $this->_findRequiredTag($tags, $package);
    }
}
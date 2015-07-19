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

class Git implements spur\packaging\bower\IResolver {

    const TAG_TIMEOUT = '5 hours';

    protected $_remote;

    public function __construct() {
        
    }

    public function fetchPackage(spur\packaging\bower\IPackage $package, $cachePath, $currentVersion=null) {
        $this->_remote = new spur\vcs\git\Remote($package->url);

        if($tag = $this->_getRequiredTag($package, $cachePath)) {
            $commitId = $tag->getCommitId();
            $version = $package->version = $tag->getVersion()->toString();
        } else {
            $heads = $this->_remote->getHeads();

            if(isset($heads['master'])) {
                $version = $commitId = $heads['master'];
            } else {
                $version = $commitId = array_pop($heads);
            }
        }

        if($currentVersion !== null && $currentVersion == $package->version) {
            $this->_remote = null;
            return false;
        }

        $package->cacheFileName = $package->name.'#'.$version;

        if(!is_dir($cachePath.'/'.$package->cacheFileName)) {
            $repo = $this->_remote->cloneTo($cachePath.'/'.$package->cacheFileName);
            $repo->checkoutCommit($commitId);
        }

        $this->_remote = null;
        return true;
    }

    protected function _getRequiredTag(spur\packaging\bower\IPackage $package, $cachePath) {
        $range = core\string\VersionRange::factory($package->version);

        try {
            $tags = $this->_fetchTags($package, $cachePath);
        } catch(spur\vcs\git\IException $e) {
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
    
    protected function _fetchTags(spur\packaging\bower\IPackage $package, $cachePath) {
        $path = dirname($cachePath).'/tags/git-'.core\string\Manipulator::formatFileName($package->url).'.json';

        if(!core\fs\File::isFileRecent($path, self::TAG_TIMEOUT)) {
            $tags = $this->_remote->getTags();

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
                $data[$tag->getName()] = $tag->getCommitId();
            }

            flex\json\Codec::encodeFile($path, $data);
            return $tags;
        }
        
        $data = flex\json\Codec::decodeFileAsTree($path);
        $tags = [];

        foreach($data as $name => $commitId) {
            $tags[] = new spur\vcs\git\Tag($this->_remote, $name, (string)$commitId);
        }

        return $tags;
    }
}
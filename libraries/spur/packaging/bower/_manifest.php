<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\packaging\bower;

use df;
use df\core;
use df\spur;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class LogicException extends \LogicException implements IException {}


// Interfaces
interface IBridge {

    public function setInstallPath($path);
    public function getInstallPath();
    public function setExecPath($path);
    public function getExecPath();
    public function generate(array $deps);
    public function install(array $deps);
}



interface IInstaller {
    public function setMultiplexer(core\io\IMultiplexer $io=null);
    public function getMultiplexer();
    public function installPackages(array $packages);
    public function installPackage(IPackage $package);
    public function isPackageInstalled($name);
    public function getInstalledPackages();
    public function getPackageInfo($name);
    public function getPackageBowerData($name);

    public function tidyCache();
}

interface IPackage {
    public function setName($name);
    public function getName();
    public function setVersion($version);
    public function getVersion();
    public function setInstallName($name);
    public function getInstallName();
    public function setUrl($url);
    public function getUrl();
    public function setCacheFileName($fileName);
    public function getCacheFileName();
}

interface IResolver {
    public function fetchPackage(IPackage $package, $cachePath, $currentVersion=null);
}

trait TGitResolver {

    protected function _findRequiredTag(array $tags, IPackage $package) {
        $range = core\string\VersionRange::factory($package->version);
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

    protected function _sortTags(array $tags) {
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

        return $tags;
    }
}

interface IRegistry extends spur\IHttpMediator {
    public function lookup($name);
    public function resolveUrl($name);
}
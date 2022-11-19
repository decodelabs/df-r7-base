<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\spur\packaging\bower;

use DecodeLabs\Terminus\Session;
use df\flex;

use df\spur;

interface IBridge
{
    public function setInstallPath($path);
    public function getInstallPath();
    public function setExecPath($path);
    public function getExecPath();
    public function generate(array $deps);
    public function install(array $deps);
}



interface IInstaller
{
    public function getInstallPath();
    public function setCliSession(Session $session = null);
    public function getCliSession(): ?Session;
    public function installPackages(array $packages);
    public function installPackage(Package $package);
    public function isPackageInstalled($name);
    public function getInstalledPackages();
    public function getPackageInfo($name);
    public function getPackageBowerData($name);
    public function getPackageJsonData($name);

    public function tidyCache();
}

interface IResolver
{
    public function resolvePackageName(Package $package);
    public function fetchPackage(Package $package, $cachePath, $currentVersion = null);
    public function getTargetVersion(Package $package, $cachePath);
}

trait TGitResolver
{
    protected function _findRequiredTag(array $tags, Package $package)
    {
        $range = flex\VersionRange::factory($package->version);
        $singleVersion = $range->getSingleVersion();

        if (!$singleVersion || !$singleVersion->preRelease) {
            $temp = [];

            foreach ($tags as $i => $tag) {
                $version = $tag->getVersion();

                if (!$version || $version->preRelease) {
                    continue;
                } else {
                    $temp[] = $tag;
                }
            }

            if (!empty($temp)) {
                $tags = $temp;
            }
        }

        if (empty($tags)) {
            return false;
        }

        foreach ($tags as $tag) {
            if (($version = $tag->getVersion()) && $range->contains($version)) {
                return $tag;
            }
        }

        return false;
    }

    protected function _sortTags(array $tags)
    {
        usort($tags, function ($left, $right) {
            $leftVersion = $left->getVersion();
            $rightVersion = $right->getVersion();

            if (!$leftVersion && !$rightVersion) {
                return 0;
            } elseif (!$leftVersion) {
                return 1;
            } elseif (!$rightVersion) {
                return -1;
            }

            if ($leftVersion->eq($rightVersion)) {
                return 0;
            } elseif ($leftVersion->lt($rightVersion)) {
                return 1;
            } else {
                return -1;
            }
        });

        return $tags;
    }
}

interface IRegistry extends spur\IGuzzleMediator
{
    public function lookup($name);
    public function resolveUrl($name);
}

<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\package;

use df;
use df\core;
use df\apex;
use df\axis;
use df\spur;

class Model extends axis\Model
{
    protected $_gitPath = null;
    protected $_gitUser = null;

    public function getInstalledPackageList()
    {
        $repos = [];
        $packages = $remainingPackages = df\Launchpad::$loader->getPackages();
        $installed = [];

        foreach (df\Launchpad::$loader->getLocations() as $location) {
            foreach (new \DirectoryIterator($location) as $item) {
                if (!$item->isDir() || !is_file($item->getPathname().'/Package.php')) {
                    continue;
                }

                $name = $item->getFilename();
                $path = $item->getPathname();
                $package = $repo = null;

                if (isset($packages[$name]) && $packages[$name]->path == $path) {
                    $package = $packages[$name];
                }

                if (is_dir($path.'/.git') && basename(dirname($path)) !== 'vendor') {
                    $repo = new spur\vcs\git\Repository($path);
                }

                $installed[] = [
                    'name' => $name,
                    'path' => $path,
                    'instance' => $package,
                    'repo' => $repo
                ];

                if ($repo) {
                    if ($this->_gitPath) {
                        $repo->setGitPath($this->_gitPath);
                    }

                    if ($this->_gitUser) {
                        $repo->setGitUser($this->_gitUser);
                    }
                }

                unset($remainingPackages[$name]);
            }
        }

        foreach ($remainingPackages as $package) {
            $installed[] = [
                'name' => $package->name,
                'path' => $package->path,
                'instance' => $package,
                'repo' => $repo = is_dir($package->path.'/.git') ? new spur\vcs\git\Repository($package->path) : null
            ];

            if ($repo) {
                if ($this->_gitPath) {
                    $repo->setGitPath($this->_gitPath);
                }

                if ($this->_gitUser) {
                    $repo->setGitUser($this->_gitUser);
                }
            }
        }

        uasort($installed, function ($a, $b) {
            if ($a['name'] == 'app') {
                return -1;
            }

            return $a['name'] > $b['name'];
        });

        return $installed;
    }

    // Update remote
    public function updateRemote($name)
    {
        foreach ($this->getInstalledPackageList() as $package) {
            if ($package['name'] == $name && $package['repo']) {
                return $package['repo']->updateRemote();
            }
        }

        return false;
    }

    public function updateRemotes()
    {
        $output = [];

        foreach ($this->getInstalledPackageList() as $package) {
            if (!$package['repo']) {
                continue;
            }

            $output[$package['name']] = $package['repo']->updateRemote();
        }

        return $output;
    }


    // Pull
    public function pull($name)
    {
        foreach ($this->getInstalledPackageList() as $package) {
            if ($package['name'] == $name && $package['repo']) {
                return $package['repo']->pull();
            }
        }

        return false;
    }

    public function pullAll()
    {
        $output = [];

        foreach ($this->getInstalledPackageList() as $package) {
            if (!$package['repo']) {
                continue;
            }

            $output[$package['name']] = $package['repo']->pull();
        }

        return $output;
    }
}

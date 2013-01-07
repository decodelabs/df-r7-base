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
    
class Model extends axis\Model {

    public function getInstalledPackageList() {
        $repos = array();
        $packages = $remainingPackages = df\Launchpad::$loader->getPackages();
        $installed = array();

        foreach(df\Launchpad::$loader->getLocations() as $location) {
            foreach(new \DirectoryIterator($location) as $item) {
                if(!$item->isDir() || !is_file($item->getPathname().'/Package.php')) {
                    continue;
                }

                $name = $item->getFilename();
                $path = $item->getPathname();
                $package = null;

                if(isset($packages[$name]) && $packages[$name]->path == $path) {
                    $package = $packages[$name];
                }

                $installed[] = [
                    'name' => $name,
                    'path' => $path,
                    'instance' => $package,
                    'repo' => is_dir($path.'/.git') ? new spur\vcs\git\Repository($path) : null
                ];

                unset($remainingPackages[$name]);
            }
        }

        foreach($remainingPackages as $package) {
            $installed[] = [
                'name' => $package->name,
                'path' => $package->path,
                'instance' => $package,
                'repo' => is_dir($package->path.'/.git') ? new spur\vcs\git\Repository($package->path) : null
            ];
        }

        uasort($installed, function($a, $b) {
            if($a['name'] == 'app') {
                return -1;
            }

            return $a['name'] > $b['name'];
        });

        return $installed;
    }
}
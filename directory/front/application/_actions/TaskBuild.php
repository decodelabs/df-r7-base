<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\application\_actions;

use df;
use df\core;
use df\apex;
use df\arch;
use df\halo;
    
class TaskBuild extends arch\task\Action {

    const PURGE_OLD_BUILDS = true;

    protected static $_appExport = [
        'libraries', 'assets', 'daemons', 'directory', 'hooks', 'models', 'themes', 'tests'
    ];

    protected function _run() {
        if(df\Launchpad::IS_COMPILED) {
            $this->throwError(403, 'Cannot compile app from production environment - run from dev mode instead');
        }

        $this->response->writeLine('Launching app builder...');


        // Run custom actions
        if($this->directory->actionExists('application/build-custom')) {
            $this->response->writeLine('Running custom user build tasks...');
            $this->runChild('application/build-custom');
        }


        // Prepare info
        $timestamp = date('YmdHis');
        $purgeOldBuilds = $this->request->query->get('purge', self::PURGE_OLD_BUILDS);
        $isTesting = isset($this->request->query->testing);

        $appPath = df\Launchpad::$applicationPath;
        $environmentId = df\Launchpad::$environmentId;
        $prefix = df\Launchpad::$uniquePrefix;
        $loader = df\Launchpad::$loader;

        $runPath = $appPath.'/data/local/run';
        $buildId = 'df-'.$timestamp;

        if($isTesting) {
            $buildId .= '-testing';
            $this->response->writeLine('Builder is running in testing mode');
        }

        $destinationPath = $runPath.'/'.$buildId;

        if(is_dir($destinationPath)) {
            $this->throwError(500, 'Destination build directory already exists');
        }

        $umask = umask(0);
        core\io\Util::ensureDirExists($destinationPath);
        core\io\Util::chmod($destinationPath, 0777, true);


        // Generate Df.php
        $this->response->writeLine('Generating Df.php');

        $dfFile = file_get_contents(df\Launchpad::DF_PATH.'/Df.php');
        $dfFile = str_replace('IS_COMPILED = false', 'IS_COMPILED = true', $dfFile);
        $dfFile = str_replace('COMPILE_TIMESTAMP = null', 'COMPILE_TIMESTAMP = '.time(), $dfFile);

        file_put_contents($destinationPath.'/Df.php', $dfFile);

        $packages = $loader->getPackages();
        $appPackage = $packages['app'];
        unset($packages['app']);


        // Copy packages
        foreach(array_reverse($packages) as $package) {
            $this->response->writeLine('Merging '.$package->name.' package');

            if(is_dir($package->path.'/libraries')) {
                core\io\Util::copyDirInto($package->path.'/libraries', $destinationPath);
            }

            if(file_exists($package->path.'/Package.php')) {
                core\io\Util::copyFile($package->path.'/Package.php', $destinationPath.'/apex/packages/'.$package->name.'/Package.php');
            }

            foreach(scandir($package->path) as $entry) {
                if($entry == '.' 
                || $entry == '..'
                || $entry == '.git'
                || $entry == '.gitignore'
                || $entry == 'libraries') {
                    continue;
                }
                
                if(is_dir($package->path.'/'.$entry)) {
                    core\io\Util::copyDir($package->path.'/'.$entry, $destinationPath.'/apex/'.$entry, true);
                }
            }
        }



        // Copy app folder
        $this->response->writeLine('Merging app folder');

        foreach(scandir($appPackage->path) as $entry) {
            if($entry == '.' 
            || $entry == '..'
            || $entry == '.git'
            || $entry == '.gitignore') {
                continue;
            }

            if(!in_array($entry, self::$_appExport)) {
                continue;
            }

            if($entry == 'libraries') {
                core\io\Util::copyDirInto($appPackage->path.'/'.$entry, $destinationPath);
                continue;
            }

            if(is_dir($appPackage->path.'/'.$entry)) {
                core\io\Util::copyDir($appPackage->path.'/'.$entry, $destinationPath.'/apex/'.$entry, true);
            }
        }

        // Generate entries
        $this->runChild('application/generate-entries?build='.$buildId);
        
        // Clear cache
        core\cache\Base::purgeAll();


        // End
        $this->response->writeLine('App build complete');

        if($purgeOldBuilds) {
            $this->runChild('application/purge-builds?'.(!$isTesting ? 'purgeTesting' : null));
        }
    }
}
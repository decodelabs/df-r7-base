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

    public function extractCliArguments(core\cli\ICommand $command) {
        $inspector = new core\cli\Inspector([
            'purge|p=i' => 'Purge old builds',
            'test|testing|t' => 'Build in testing mode only',
            'dev|development|d' => 'Build in development mode only'
        ], $command);

        if($inspector['purge']) {
            $this->request->query->purge = true;
        }

        if($inspector['dev']) {
            $this->request->query->dev = true;
        } else if($inspector['testing']) {
            $this->request->query->testing = true;
        } 
    }

    public function execute() {
        if(df\Launchpad::IS_COMPILED) {
            $this->throwError(403, 'Cannot compile app from production environment - run from dev mode instead');
        }

        $this->io->writeLine('Launching app builder...');

        // Prepare info
        $timestamp = date('YmdHis');
        $purgeOldBuilds = $this->request->query->get('purge', self::PURGE_OLD_BUILDS);
        $isTesting = isset($this->request->query->testing);
        $isDev = isset($this->request->query->dev);

        if($isDev) {
            $this->io->writeLine('Builder is running in dev mode, no build folder will be created');
        } else if($isTesting) {
            $this->io->writeLine('Builder is running in testing mode');
        }


        // Run custom actions
        $custom = $this->task->findChildrenIn('application/build/');

        if($this->apex->actionExists('application/build-custom')) {
            $custom[] = new arch\Request('application/build-custom');
        }

        if(!empty($custom)) {
            $this->io->writeLine();
            $this->io->writeLine('Running custom user build tasks...');

            foreach($custom as $request) {
                $this->runChild($request);
            }

            $this->io->writeLine();
        }
        

        if(!$isDev) {
            $appPath = df\Launchpad::$applicationPath;
            $environmentId = df\Launchpad::$environmentId;
            $prefix = df\Launchpad::$uniquePrefix;
            $loader = df\Launchpad::$loader;

            $runPath = $appPath.'/data/local/run';
            $buildId = 'df-'.$timestamp;

            if($isTesting) {
                $buildId .= '-testing';
            }

            $destinationPath = $runPath.'/'.$buildId;

            if(is_dir($destinationPath)) {
                $this->throwError(500, 'Destination build directory already exists');
            }

            $umask = umask(0);
            core\io\Util::ensureDirExists($destinationPath);
            core\io\Util::chmod($destinationPath, 0777, true);


            // Generate Df.php
            $this->io->writeLine('Generating Df.php');

            $dfFile = file_get_contents(df\Launchpad::DF_PATH.'/Df.php');
            $dfFile = str_replace('IS_COMPILED = false', 'IS_COMPILED = true', $dfFile);
            $dfFile = str_replace('COMPILE_TIMESTAMP = null', 'COMPILE_TIMESTAMP = '.time(), $dfFile);

            file_put_contents($destinationPath.'/Df.php', $dfFile);

            $packages = $loader->getPackages();
            $appPackage = $packages['app'];
            unset($packages['app']);


            // Copy packages
            foreach(array_reverse($packages) as $package) {
                $this->io->writeLine('Merging '.$package->name.' package');

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
            $this->io->writeLine('Merging app folder');

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
            $this->io->writeLine();
            $this->runChild('application/generate-entries?build='.$buildId);
        }

        // Clear cache
        $this->io->writeLine('Clearing cache');
        core\cache\Base::purgeAll();


        // Restart daemons
        if(!$isTesting) {
            $this->io->writeLine();
            $this->runChild('daemons/restart-all');
        }


        // End
        $this->io->writeLine();
        $this->io->writeLine('App build complete');

        if($purgeOldBuilds) {
            $this->io->writeLine();
            $this->runChild('application/purge-builds?'.(!$isTesting ? 'purgeTesting' : null));
        }
    }
}
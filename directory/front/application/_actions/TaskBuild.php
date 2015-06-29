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
        $custom = $this->apex->findActionsIn('./build/', 'Task');

        if($this->apex->actionExists('./build-custom')) {
            $custom[] = $this->uri->directoryRequest('./build-custom');
        }

        if(!empty($custom)) {
            $this->io->writeLine();
            $this->io->writeLine('Running custom user build tasks...');

            foreach($custom as $request) {
                $this->runChild($request);
            }

            $this->io->writeLine();
        }


        // Clear config cache
        core\Config::clearLiveCache();
        

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
            $dir = core\fs\Dir::create($destinationPath, 0777);

            $this->io->writeLine('Packaging files...');
            $this->io->incrementLineLevel();

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
                    core\fs\Dir::merge($package->path.'/libraries', $destinationPath);
                }

                $packageFile = new core\fs\File($package->path.'/Package.php');

                if($packageFile->exists()) {
                    $packageFile->copyTo($destinationPath.'/apex/packages/'.$package->name.'/Package.php');
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
                        core\fs\Dir::merge($package->path.'/'.$entry, $destinationPath.'/apex/'.$entry, true);
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
                    core\fs\Dir::merge($appPackage->path.'/'.$entry, $destinationPath);
                    continue;
                }

                if(is_dir($appPackage->path.'/'.$entry)) {
                    core\fs\Dir::merge($appPackage->path.'/'.$entry, $destinationPath.'/apex/'.$entry, true);
                }
            }

            // Generate entries
            $this->runChild('./generate-entries?build='.$buildId, false);
        }

        $this->io->decrementLineLevel();

        // Clear cache
        $this->io->writeLine();
        $this->io->writeLine('Purging cache backends...');
        $this->runChild('cache/purge');


        // Restart daemons
        if(!$isTesting) {
            $this->io->writeLine();
            $this->runChild('daemons/restart-all', false);
        }


        // Purge
        if($purgeOldBuilds) {
            $this->io->writeLine();
            $this->io->writeLine('Purging old builds...');
            $this->runChild('./purge-builds?'.(!$isTesting ? 'purgeTesting' : null));
        }


        // Task spool
        if(!$isTesting) {
            $this->io->writeLine('Running task spool...');
            $this->runChild('manager/spool');
        }
    }
}
<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\application\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;
use df\halo;
use df\flex;

class TaskBuild extends arch\node\Task {

    const APP_EXPORT = [
        'libraries', 'assets', 'daemons', 'directory', 'hooks', 'models', 'themes', 'tests'
    ];

    public function extractCliArguments(core\cli\ICommand $command) {
        $inspector = new core\cli\Inspector([
            'dev|development|d' => 'Build in development mode only'
        ], $command);

        if($inspector['purge']) {
            $this->request->query->purge = true;
        }

        if($inspector['dev']) {
            $this->request->query->dev = true;
        }
    }

    public function execute() {
        $this->ensureDfSource();

        // Prepare info
        $buildId = (string)flex\Guid::uuid1();
        $isDev = isset($this->request['dev']);

        if(!$isDev && $this->application->isDevelopment()) {
            $isDev = true;
        }

        if($isDev) {
            $this->io->writeLine('Builder is running in dev mode, no build folder will be created');
            $this->io->writeLine();
        }


        // Run custom tasks
        $this->runChild('./build-custom', false);


        // Clear config cache
        core\Config::clearLiveCache();


        if(!$isDev) {
            $appPath = df\Launchpad::$applicationPath;
            $environmentId = df\Launchpad::$environmentId;
            $prefix = df\Launchpad::$uniquePrefix;
            $loader = df\Launchpad::$loader;

            $localPath = $appPath.'/data/local';
            $runPath = $localPath.'/run';

            $destinationPath = $localPath.'/build/'.$buildId;
            $destination = new core\fs\Dir($destinationPath);

            if($destination->exists()) {
                $this->throwError(500, 'Destination build directory already exists');
            }

            $umask = umask(0);
            $destination->ensureExists(0777);

            $this->io->writeLine('Packaging files...');
            $this->io->incrementLineLevel();


            // List packages
            $packages = $loader->getPackages();
            $appPackage = $packages['app'];
            unset($packages['app']);

            $this->io->write('Merging:');

            // Copy packages
            foreach(array_reverse($packages) as $package) {
                $this->io->write(' '.$package->name);
                $packageDir = new core\fs\Dir($package->path);

                if($libDir = $packageDir->getExistingDir('libraries')) {
                    $libDir->mergeInto($destination);
                }

                if($packageFile = $packageDir->getExistingFile('Package.php')) {
                    $packageFile->copyTo($destinationPath.'/apex/packages/'.$package->name.'/Package.php');
                }

                foreach($packageDir->scanDirs() as $name => $dir) {
                    if($name == '.git' || $name == 'libraries') {
                        continue;
                    }

                    $dir->mergeInto($destinationPath.'/apex/'.$name);
                }
            }



            // Copy app folder
            $this->io->writeLine(' app');
            $appDir = new core\fs\Dir($appPackage->path);

            foreach($appDir->scanDirs() as $name => $dir) {
                if(!in_array($name, self::APP_EXPORT)) {
                    continue;
                }

                if($name == 'libraries') {
                    $dir->mergeInto($destination);
                    continue;
                } else {
                    $dir->mergeInto($destinationPath.'/apex/'.$name);
                }
            }


            // Switch active
            $destination->moveTo($runPath, $buildId);

            core\fs\File::create($runPath.'/Active.php',
                '<?php'."\n".
                'df\\Launchpad::$isCompiled = true;'."\n".
                'df\\Launchpad::$compileTimestamp = '.time().';'."\n".
                'df\\Launchpad::$rootPath = \''.$runPath.'/'.$buildId.'\';'."\n".
                'df\\Launchpad::$environmentMode = \''.df\Launchpad::$environmentMode.'\';'
            );
        }

        $this->io->decrementLineLevel();

        // Generate entries
        $this->runChild('./generate-entry', false);

        // Clear cache
        $this->io->writeLine();
        $this->io->writeLine('Purging cache backends...');
        $this->runChild('cache/purge');

        // Restart daemons
        $this->io->writeLine();
        $this->runChild('daemons/restart-all', false);

        // Purge
        $this->io->writeLine();
        $this->io->decrementLineLevel();
        $this->runChild('./purge-builds');
        $this->io->incrementLineLevel();

        // Task spool
        $this->io->writeLine();
        $this->io->writeLine('Running task spool...');
        $this->runChild('tasks/spool');
    }
}
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
        'libraries', 'daemons', 'directory', 'models', 'themes', 'tests'
    ];

    protected function _run() {
        if(df\Launchpad::IS_COMPILED) {
            $this->throwError(403, 'Cannot compile app from production environment - run from dev mode instead');
        }

        $this->response->writeLine('Launching app builder...');

        // Prepare info
        $timestamp = date('YmdHis');
        $purgeOldBuilds = $this->request->query->get('purge', self::PURGE_OLD_BUILDS);

        $appPath = df\Launchpad::$applicationPath;
        $environmentId = df\Launchpad::$environmentId;
        $prefix = df\Launchpad::$uniquePrefix;
        $loader = df\Launchpad::$loader;

        $runPath = $appPath.'/data/local/run';
        $buildId = 'df-'.$timestamp;
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



        // PHP Entry point
        $this->response->writeLine('Generating entry points');

        foreach(['testing', 'production'] as $environmentMode) {
            $entryPath = $appPath.'/entry/'.$environmentId.'.'.$environmentMode.'.php';
            
            $data = '<?php'."\n\n".
                    '/* This file is automatically generated by the DF package builder */'."\n".
                    'require_once dirname(__DIR__).\'/data/local/run/'.$buildId.'/Df.php\';'."\n";

            $data .= 'df\\Launchpad::runAs(\''.$environmentId.'\', \''.$environmentMode.'\', dirname(__DIR__));';
            file_put_contents($entryPath, $data);

            try {
                core\io\Util::chmod($entryPath, 0777, true);
            } catch(\Exception $e) {}
        }

        $phpPath = core\Environment::getInstance($this->application)->getPhpBinaryPath();

        // Bash Entry point
        $data = '#!'.$phpPath."\n".
                '<?php'."\n\n".
                '/* This file is automatically generated by the DF package builder */'."\n".
                'require_once \''.$appPath.'/entry/'.$environmentId.'.development.php\';'."\n";

        $entryPath = $appPath.'/entry/'.$environmentId;
        file_put_contents($entryPath, $data);

        try {
            core\io\Util::chmod($entryPath, 0777, true);
        } catch(\Exception $e) {}


        foreach(['development', 'testing', 'production'] as $environmentMode) {
            $entryPath = $appPath.'/entry/'.$environmentId.'.'.$environmentMode;
            core\io\Util::deleteFile($entryPath);
        }


        // End
        $this->response->writeLine('App build complete');

        if($purgeOldBuilds) {
            return $this->directory->newRequest('task://application/purge-builds');
        }
    }
}
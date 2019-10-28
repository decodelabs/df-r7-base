<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\app\builder;

use df;
use df\core;
use df\flex;

use DecodeLabs\Glitch;
use DecodeLabs\Atlas;
use DecodeLabs\Atlas\Dir;

class Controller implements IController
{
    const APP_EXPORT = [
        'libraries', 'assets', 'daemons', 'directory', 'helpers',
        'hooks', 'models', 'themes', 'tests', 'vendor'
    ];

    const PACKAGE_IGNORE = [
        'vendor'
    ];

    const APP_FILES = [
        'composer.json', 'composer.lock'
    ];

    public $io;

    protected $_id;
    protected $_shouldCompile = true;
    protected $_runPath;
    protected $_destination;

    public function __construct()
    {
        $this->_id = (string)flex\Guid::uuid1();
        $this->_shouldCompile = !df\Launchpad::$app->isDevelopment();

        $this->_runPath = df\Launchpad::$app->getLocalDataPath().'/run';
        $this->_destination = Atlas::$fs->dir(df\Launchpad::$app->getLocalDataPath().'/build/'.$this->_id);
    }

    public function getBuildId(): string
    {
        return $this->_id;
    }

    public function shouldCompile(bool $flag=null)
    {
        if ($flag !== null) {
            $this->_shouldCompile = $flag;
            return $this;
        }

        return $this->_shouldCompile;
    }


    public function setMultiplexer(?core\io\IMultiplexer $multiplexer)
    {
        $this->io = $multiplexer;
        return $this;
    }

    public function getMultiplexer(): core\io\IMultiplexer
    {
        if (!$this->io) {
            $this->io = core\io\Multiplexer::defaultFactory('task');
        }

        return $this->io;
    }


    public function getRunPath(): string
    {
        return $this->_runPath;
    }

    public function getDestination(): Dir
    {
        return $this->_destination;
    }



    public function createBuild(): \Generator
    {
        if ($this->_destination->exists()) {
            throw Glitch::{'df/core/fs/EAlreadyExists'}(
                'Destination build directory already exists'
            );
        }

        $umask = umask(0);
        $this->_destination->ensureExists(0777);


        $packages = df\Launchpad::$loader->getPackages();
        $appPackage = $packages['app'];
        unset($packages['app']);
        $destinationPath = (string)$this->_destination;

        // Copy packages
        foreach (array_reverse($packages) as $package) {
            yield $package->name;
            $packageDir = Atlas::$fs->dir($package->path);

            if ($libDir = $packageDir->getExistingDir('libraries')) {
                $libDir->mergeInto($destinationPath);
            }

            if ($packageFile = $packageDir->getExistingFile('Package.php')) {
                $packageFile->copy($destinationPath.'/apex/packages/'.$package->name.'/Package.php');
            }

            foreach ($packageDir->scanDirs() as $name => $dir) {
                if ($name == '.git' || $name == 'libraries') {
                    continue;
                }

                if (in_array($name, self::PACKAGE_IGNORE)) {
                    continue;
                }

                $dir->mergeInto($destinationPath.'/apex/'.$name);
            }
        }


        // Copy app folder
        yield 'app';
        $appDir = Atlas::$fs->dir($appPackage->path);

        foreach ($appDir->scanDirs() as $name => $dir) {
            if (!in_array($name, self::APP_EXPORT)) {
                continue;
            }

            if ($name == 'libraries') {
                $dir->mergeInto($destinationPath);
                continue;
            } else {
                $dir->mergeInto($destinationPath.'/apex/'.$name);
            }
        }

        foreach ($appDir->scanFiles() as $name => $file) {
            if (!in_array($name, self::APP_FILES)) {
                continue;
            }

            $file->copy($destinationPath.'/apex/'.$name);
        }

        $appDir->getFile('App.php')->copy($destinationPath.'/apex/App.php');


        // Generate run file
        Atlas::$fs->createFile(
            $destinationPath.'/Run.php',
            '<?php'."\n".
            'namespace df;'."\n".
            'const COMPILE_TIMESTAMP = '.time().';'."\n".
            'const COMPILE_BUILD_ID = \''.$this->getBuildId().'\';'."\n".
            'const COMPILE_ROOT_PATH = \''.$this->_runPath.'/active\';'."\n".
            'const COMPILE_ENV_MODE = \''.df\Launchpad::$app->envMode.'\';'
        );
    }

    public function activateBuild()
    {
        Atlas::$fs->deleteDir($this->_runPath.'/backup');

        if (is_dir($this->_runPath.'/active')) {
            Atlas::$fs->renameDir($this->_runPath.'/active', 'backup');
        }

        $this->_destination->moveTo($this->_runPath, 'active');
        Atlas::$fs->deleteDir($this->_runPath.'/backup');
    }
}

<?php
/**
 * This file is part of the Decode r7 framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Genesis;

use DecodeLabs\Atlas;
use DecodeLabs\Atlas\Dir;
use DecodeLabs\Atlas\File;
use DecodeLabs\Dictum;

use DecodeLabs\Genesis;
use DecodeLabs\Genesis\Build\Manifest;
use DecodeLabs\Genesis\Build\Package;
use DecodeLabs\Genesis\Build\Task\Generic as GenericTask;
use DecodeLabs\R7\Legacy;
use DecodeLabs\Terminus\Session;
use df\arch\node\IBuildTaskNode as BuildTaskNode;
use df\arch\Request as ArchRequest;
use df\core\Config as CoreConfig;
use df\flex\Guid;
use Generator;
use ReflectionClass;

class BuildManifest implements Manifest
{
    public const APP_EXPORT = [
        'libraries', 'assets', 'daemons', 'directory', 'helpers',
        'hooks', 'models', 'provider', 'themes', 'tests', 'vendor'
    ];

    public const PACKAGE_IGNORE = [
        'provider', 'vendor'
    ];

    public const APP_FILES = [
        'composer.json', 'composer.lock'
    ];


    protected string $buildId;
    protected Session $session;

    public function __construct(Session $session)
    {
        $this->buildId = (string)Guid::uuid1();
        $this->session = $session;
    }

    /**
     * Get Terminus session
     */
    public function getCliSession(): Session
    {
        return $this->session;
    }

    /**
     * Create Guid for build
     */
    public function generateBuildId(): string
    {
        return $this->buildId;
    }



    /**
     * Get build temp dir
     */
    public function getBuildTempDir(): Dir
    {
        return Atlas::dir(Genesis::$hub->getLocalDataPath() . '/build/');
    }

    /**
     * Get run dir
     */
    public function getRunDir(): Dir
    {
        return Atlas::dir(Genesis::$hub->getLocalDataPath() . '/run/');
    }

    /**
     * Get entry file name
     */
    public function getEntryFileName(): string
    {
        return 'Run.php';
    }


    public function getRunName1(): string
    {
        return 'active';
    }

    public function getRunName2(): string
    {
        return 'active2';
    }




    /**
     * Scan pre compile tasks
     */
    public function scanPreCompileTasks(): Generator
    {
        // Task nodes
        foreach ($this->scanTaskNodes() as $request) {
            yield new GenericTask(
                'Running task: ' . $request->getPath(),
                function () use ($request) {
                    Legacy::runTask($request, true);
                }
            );
        }

        // Clear config live cache
        yield new GenericTask(
            'Clearing core Config live cache',
            function (Session $session) {
                foreach (CoreConfig::clearLiveCache() as $key) {
                    $session->{'.magenta'}($key);
                }
            }
        );
    }




    /**
     * @return Generator<Package>
     */
    public function scanPackages(): Generator
    {
        $packages = Legacy::getLoader()->getPackages();

        foreach (array_reverse($packages) as $r7Pkg) {
            $package = new Package(
                $r7Pkg->name,
                Atlas::dir($r7Pkg->path)
            );

            yield $package;
        }
    }





    /**
     * @return Generator<File|Dir, string>
     */
    public function scanPackage(Package $package): Generator
    {
        if ($package->getName() === 'app') {
            return yield from $this->scanAppPackage($package);
        }

        $packageDir = $package->source;

        if ($libDir = $packageDir->getExistingDir('libraries')) {
            yield $libDir => '/';
        }

        if ($packageFile = $packageDir->getExistingFile('Package.php')) {
            yield $packageFile => '/apex/packages/' . $package->name . '/Package.php';
        }

        foreach ($packageDir->scanDirs() as $name => $dir) {
            if (
                $name == '.git' ||
                $name == 'libraries' ||
                in_array($name, self::PACKAGE_IGNORE)
            ) {
                continue;
            }

            yield $dir => '/apex/' . $name;
        }
    }

    /**
     * @return Generator<File|Dir, string>
     */
    protected function scanAppPackage(Package $package): Generator
    {
        $appDir = $package->source;


        // Dirs
        foreach ($appDir->scanDirs() as $name => $dir) {
            if (!in_array($name, self::APP_EXPORT)) {
                continue;
            }

            if ($name == 'libraries') {
                yield $dir => '/';
            } else {
                yield $dir => '/apex/' . $name;
            }
        }


        // Root files
        foreach ($appDir->scanFiles() as $name => $file) {
            if (!in_array($name, self::APP_FILES)) {
                continue;
            }

            yield $file => '/apex/' . $name;
        }


        // App file
        yield $appDir->getFile('App.php') => '/apex/App.php';
    }



    public function writeEntryFile(File $file): void
    {
        $file->putContents(
            '<?php' . "\n" .
            'namespace df;' . "\n" .
            'const COMPILE_TIMESTAMP = ' . time() . ';' . "\n" .
            'const COMPILE_BUILD_ID = \'' . $this->buildId . '\';' . "\n" .
            'const COMPILE_ROOT_PATH = __DIR__;' . "\n" .
            'const COMPILE_ENV_MODE = \'' . Genesis::$environment->getMode() . '\';'
        );
    }


    /**
     * Scan post compile tasks
     */
    public function scanPostCompileTasks(): Generator
    {
        // Task nodes
        foreach ($this->scanTaskNodes(true) as $request) {
            yield new GenericTask(
                'Running task: ' . $request->getPath(),
                function () use ($request) {
                    Legacy::runTask($request, true);
                }
            );
        }
    }


    /**
     * Scan post activation tasks
     */
    public function scanPostActivationTasks(): Generator
    {
        // Clear cache
        yield new GenericTask(
            'Purging caches',
            function () {
                Legacy::runTask('cache/purge', true);
            }
        );

        // Restart daemons
        yield new GenericTask(
            'Restarting daemons',
            function (Session $session) {
                Legacy::runTask('daemons/restart-all', true);
            }
        );

        // Task spool
        yield new GenericTask(
            'Scanning for scheduled tasks',
            function (Session $session) {
                Legacy::runTask('tasks/scan', true);
            }
        );

        yield new GenericTask(
            'Running task spool',
            function (Session $session) {
                Legacy::runTask('tasks/spool', true);
            }
        );
    }




    /**
     * Scan task nodes
     *
     * @return Generator<ArchRequest>
     */
    protected function scanTaskNodes(bool $after = false): Generator
    {
        $fileList = Legacy::getLoader()->lookupFileListRecursive('apex/directory', ['php'], function ($path) {
            return basename($path) == '_nodes';
        });

        foreach ($fileList as $key => $path) {
            $basename = substr(basename($path), 0, -4);

            if (substr($basename, 0, 4) != 'Task') {
                continue;
            }

            $keyParts = explode('/', dirname($key));
            /** @var class-string */
            $class = 'df\\apex\\directory\\' . implode('\\', $keyParts) . '\\' . $basename;
            $ref = new ReflectionClass($class);

            if (!$ref->implementsInterface(BuildTaskNode::class)) {
                continue;
            }

            $runAfter = defined($class . '::RUN_AFTER') && (bool)$class::RUN_AFTER;

            if (
                (!$after && $runAfter) ||
                ($after && !$runAfter)
            ) {
                continue;
            }

            array_pop($keyParts);

            if ($keyParts[0] == 'front') {
                array_shift($keyParts);
            } else {
                $keyParts[0] = '~' . $keyParts[0];
            }

            /** @var ArchRequest $output */
            $output = ArchRequest::factory(
                trim(implode('/', $keyParts) . '/' . Dictum::actionSlug(substr($basename, 4)), '/')
            );

            $output->query->buildId = $this->buildId;

            yield $output;
        }
    }
}

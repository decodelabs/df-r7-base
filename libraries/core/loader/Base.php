<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\loader;

use df;
use df\core;

class Base implements core\ILoader {

    private static $_includeAttempts = 0;
    private static $_includeMisses = 0;

    protected $_locations = [];
    protected $_packages = [];

    private $_isInit = false;


// Stats
    public static function getTotalIncludeAttempts(): int {
        return self::$_includeAttempts;
    }

    public static function getTotalIncludeMisses(): int {
        return self::$_includeMisses;
    }


// Construct
    public function __construct(array $locations=[]) {
        $this->_locations = $locations;
        spl_autoload_register([$this, 'loadClass']);

        $this->_packages['base'] = new core\Package('base', 0, df\Launchpad::$rootPath);
        $this->_packages['app'] = new core\Package('app', PHP_INT_MAX, df\Launchpad::$applicationPath);
    }

// Class loader
    public function loadClass(string $class): bool {
        if(class_exists($class, false)
        || interface_exists($class, false)
        || trait_exists($class, false)) {
            return true;
        }

        $output = false;

        if($paths = $this->getClassSearchPaths($class)) {
            $included = get_included_files();

            foreach($paths as $path) {
                self::$_includeAttempts++;

                if(file_exists($path) && !in_array($path, $included)) {
                    include_once $path;

                    if(class_exists($class, false)
                    || interface_exists($class, false)
                    || trait_exists($class, false)) {
                        $output = true;
                        break;
                    }
                }

                self::$_includeMisses++;
            }
        }

        return $output;
    }

    public function getClassSearchPaths(string $class): ?array {
        $parts = explode('\\', $class);

        if(array_shift($parts) != 'df') {
            return null;
        }

        if(!$library = array_shift($parts)) {
            return null;
        }

        $fileName = array_pop($parts);
        $basePath = df\Launchpad::$rootPath.'/'.$library;

        if(!empty($parts)) {
            $basePath .= '/'.implode('/', $parts);
        }

        $output = [$basePath.'/'.$fileName.'.php'];

        if(false !== ($pos = strpos($fileName, '_'))) {
            $fileName = substr($fileName, 0, $pos);
            $output[] = $basePath.'/'.$fileName.'.php';
        }

        $output[] = $basePath.'/_manifest.php';

        return $output;
    }

    public function lookupClass(string $path): ?string {
        $parts = explode('/', trim($path, '/'));
        $class = 'df\\'.implode('\\', $parts);

        if(!class_exists($class)) {
            return null;
        }

        return $class;
    }


// File finder
    public function findFile(string $path): ?string {
        if(null === ($paths = $this->getFileSearchPaths($path))) {
            return null;
        }

        foreach($paths as $path) {
            if(is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    public function getFileSearchPaths(string $path): array {
        $path = core\uri\Path::normalizeLocal($path);
        return [df\Launchpad::$rootPath.'/'.$path];
    }

    public function lookupFileList(string $path, array $extensions=null): \Generator {
        $paths = $this->getFileSearchPaths(rtrim($path, '/').'/');
        $index = [];

        foreach($paths as $path) {
            if(!is_dir($path)) {
                continue;
            }

            $dir = new \DirectoryIterator($path);

            foreach($dir as $item) {
                if(!$item->isFile()) {
                    continue;
                }

                $filePath = $item->getPathname();
                $baseName = basename($filePath);

                if($extensions !== null) {
                    $parts = explode('.', $baseName);
                    $ext = array_pop($parts);

                    if(!in_array($ext, $extensions)) {
                        continue;
                    }
                }

                if(isset($index[$baseName])) {
                    continue;
                }

                $index[$baseName] = true;
                yield $baseName => $filePath;
            }
        }
    }

    public function lookupFileListRecursive(string $path, array $extensions=null, Callable $folderCheck=null): \Generator {
        $path = core\uri\Path::normalizeLocal($path);

        if(!($folderCheck && !core\lang\Callback($folderCheck, $path))) {
            foreach($this->lookupFileList($path, $extensions) as $key => $val) {
                yield $key => $val;
            }
        }

        $index = [];

        foreach($this->lookupFolderList($path) as $dirName => $dirPath) {
            foreach($this->lookupFileListRecursive($path.'/'.$dirName, $extensions, $folderCheck) as $name => $filePath) {
                if(isset($index[$dirName.'/'.$name])) {
                    continue;
                }

                $index[$dirName.'/'.$name] = true;
                yield $dirName.'/'.$name => $filePath;
            }
        }
    }

    public function lookupClassList(string $path, bool $test=true): \Generator {
        $path = trim($path, '/');

        foreach($this->lookupFileList($path, ['php']) as $fileName => $filePath) {
            $name = substr($fileName, 0, -4);

            if(substr($name, 0, 1) == '_') {
                continue;
            }

            $class = 'df\\'.str_replace('/', '\\', $path).'\\'.$name;

            if($test) {
                if(!class_exists($class)) {
                    continue;
                }

                $ref = new \ReflectionClass($class);

                if($ref->isAbstract()) {
                    continue;
                }
            }

            yield $name => $class;
        }
    }

    public function lookupFolderList(string $path): \Generator {
        $paths = $this->getFileSearchPaths(rtrim($path, '/').'/');

        if(!$paths) {
            return;
        }

        $index = [];

        foreach($paths as $path) {
            if(!is_dir($path)) {
                continue;
            }

            $dir = new \DirectoryIterator($path);

            foreach($dir as $item) {
                if(!$item->isDir() || $item->isDot()) {
                    continue;
                }

                $filePath = $item->getPathname();
                $baseName = basename($filePath);

                if(isset($index[$baseName])) {
                    continue;
                }

                $index[$baseName] = true;
                yield $baseName => $filePath;
            }
        }
    }

    public function lookupLibraryList(): array {
        $libList = ['apex'];

        foreach($this->lookupFolderList('/') as $folder) {
            $libList[] = basename($folder);
        }

        $libList = array_unique($libList);
        sort($libList);
        return $libList;
    }


// Locations
    public function registerLocations(array $locations) {
        $this->_locations = $locations + $this->_locations;
        return $this;
    }

    public function registerLocation(string $name, string $path) {
        $this->_locations = [$name => $path] + $this->_locations;
        return $this;
    }

    public function unregisterLocation(string $name) {
        unset($this->_locations[$name]);
        return $this;
    }

    public function getLocations(): array {
        return $this->_locations;
    }


// Packages
    public function loadPackages(array $packages) {
        if($this->_isInit) {
            throw core\Error::ELogic(
                'Cannot load packages after init'
            );
        }

        $this->_loadPackageList($packages);

        uasort($this->_packages, function($a, $b) {
            return $a->priority < $b->priority;
        });

        foreach($this->_packages as $package) {
            $package->init();
        }

        $this->_isInit = true;
        return $this;
    }

    private function _loadPackageList(array $packages) {
        foreach($packages as $package) {
            if(isset($this->_packages[$package])) {
                continue;
            }

            $package = core\Package::factory($package);
            $this->_packages[$package->name] = $package;

            $deps = $package::DEPENDENCIES;

            if(is_array($deps) && !empty($deps)) {
                $this->_loadPackageList($deps);
            }
        }
    }

    public function getPackages(): array {
        return $this->_packages;
    }

    public function hasPackage(string $package): bool {
        return isset($this->_packages[$package]);
    }

    public function getPackage(string $package): ?core\IPackage {
        if(isset($this->_packages[$package])) {
            return $this->_packages[$package];
        }

        return null;
    }



// Shutdown
    public function shutdown(): void {
        // do nothing yet
    }
}

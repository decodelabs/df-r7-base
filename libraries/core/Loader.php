<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core;

use df;
use df\core;

class Loader implements ILoader {
    
    private static $_includeAttempts = 0;
    private static $_includeMisses = 0;
    
    private $_isActive = false;
    protected $_locations = [];
    protected $_packages = [];
    
    
// Stats
    public static function getTotalIncludeAttempts() {
        return self::$_includeAttempts;
    }
    
    public static function getTotalIncludeMisses() {
        return self::$_includeMisses;
    }
    
    
// Construct
    public function __construct(array $locations=[]) {
        $this->_locations = $locations;
    }
    
    
// Activation
    public function activate() {
        if(!$this->_isActive) {
            spl_autoload_register([$this, 'loadClass']);
            $this->_isActive = true;
        }
        
        return $this;
    }
    
    public function deactivate() {
        if($this->_isActive) {
            spl_autoload_unregister([$this, 'loadClass']);
            $this->_isActive = false;
        }
        
        return $this;
    }
    
    public function isActive() {
        return $this->_isActive;
    }
    
    
// Class loader
    public function loadClass($class) {
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
                        $output = $path;
                        break;
                    }
                }
                
                self::$_includeMisses++;
            }
        }
        
        return $output;
    }
    
    public function getClassSearchPaths($class) {
        $parts = explode('\\', $class);
        
        if(array_shift($parts) != 'df') {
            return false;
        }
        
        if(!$library = array_shift($parts)) {
            return false;
        }
        
        $fileName = array_pop($parts);
        $basePath = df\Launchpad::DF_PATH.'/'.$library;
        
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

    public function lookupClass($path) {
        $parts = explode('/', trim($path, '/'));
        $class = 'df\\'.implode('\\', $parts);

        if(!class_exists($class)) {
            return null;
        }

        return $class;
    }
    
    
// File finder
    public function findFile($path) {
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
    
    public function getFileSearchPaths($path) {
        return [df\Launchpad::DF_PATH.'/'.$path];
    }
    
    public function lookupFileList($path, $extensions=null) {
        if($extensions !== null && !is_array($extensions)) {
            $extensions = [$extensions];
        }

        if(empty($extensions)) {
            $extensions = null;
        }

        $output = [];
        $paths = $this->getFileSearchPaths(rtrim($path, '/').'/');
        
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
                
                $output[$baseName] = $filePath;
            }
        }
        
        return $output;
    }

    public function lookupFileListRecursive($path, $extensions=null, $folderCheck=null) {
        if($folderCheck && !core\lang\Callback($folderCheck, $path)) {
            $output = [];
        } else {
            $output = $this->lookupFileList($path, $extensions);
        }

        foreach($this->lookupFolderList($path) as $dirName => $dirPath) {
            foreach($this->lookupFileListRecursive($path.'/'.$dirName, $extensions, $folderCheck) as $name => $filePath) {
                $output[$dirName.'/'.$name] = $filePath;
            }
        }
        
        return $output;
    }

    public function lookupClassList($path, $test=true) {
        $path = trim($path, '/');
        $output = [];

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

            $output[$name] = $class;
        }

        return $output;
    }

    public function lookupFolderList($path) {
        $output = [];
        $paths = $this->getFileSearchPaths(rtrim($path, '/').'/');

        if(!$paths) {
            return $output;
        }
        
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
                $output[$baseName] = $filePath;
            }
        }
        
        return $output;
    }

    public function lookupLibraryList() {
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
    
    public function registerLocation($name, $path) {
        $this->_locations = [$name => $path] + $this->_locations;
        return $this;
    }
    
    public function unregisterLocation($name) {
        unset($this->_locations[$name]);
        return $this;
    }
    
    public function getLocations() {
        return $this->_locations;
    }
    
    
// Packages
    public function loadBasePackages() {
        $this->_packages['base'] = new core\Package('base', 0, df\Launchpad::DF_PATH);
        $this->_packages['app'] = new core\Package('app', PHP_INT_MAX, df\Launchpad::$applicationPath);
        
        return $this;
    }

    public function loadPackages(array $packages) {
        $this->_loadPackageList($packages);

        uasort($this->_packages, function($a, $b) {
            return $a->priority < $b->priority;
        });

        return $this;
    }

    private function _loadPackageList(array $packages) {
        foreach($packages as $package) {
            if(isset($this->_packages[$package])) {
                continue;
            }

            $package = core\Package::factory($package);
            $this->_packages[$package->name] = $package;

            $deps = $package::$dependencies;

            if(is_array($deps) && !empty($deps)) {
                $this->_loadPackageList($deps);
            }
        }
    }
    
    public function getPackages() {
        return $this->_packages;
    }
    
    public function hasPackage($package) {
        return isset($this->_packages[$package]);
    }
    
    public function getPackage($package) {
        if(isset($this->_packages[$package])) {
            return $this->_packages[$package];
        }
        
        return null;
    }
    
    
    
// Shutdown
    public function shutdown() {
        // do nothing yet
    }
}

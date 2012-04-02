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
    protected $_locations = array();
    protected $_packages = array();
    
    
// Stats
    public static function getTotalIncludeAttempts() {
        return self::$_includeAttempts;
    }
    
    public static function getTotalIncludeMisses() {
        return self::$_includeMisses;
    }
    
    
// Construct
    public function __construct(array $locations=array()) {
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
                    include $path;
                    
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
        
        $filename = array_pop($parts);
        $basePath = df\Launchpad::ROOT_PATH.'/'.$library;
        
        if(!empty($parts)) {
            $basePath .= '/'.implode('/', $parts);
        }
        
        if(false !== ($pos = strpos($filename, '_'))) {
            $filename = substr($filename, 0, $pos);
        }
        
        $output = [
            $basePath.'/'.$filename.'.php',
            $basePath.'/_manifest.php'
        ];
        
        return $output;
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
        return [df\Launchpad::ROOT_PATH.'/'.$path];
    }
    
    public function lookupFileList($path, array $extensions=null) {
        $output = array();
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
                $basename = basename($filePath);
                
                if($extensions !== null) {
                    $parts = explode('.', $basename);
                    $ext = array_pop($parts);
                    
                    if(!in_array($ext, $extensions)) {
                        continue;
                    }
                }
                
                $output[$basename] = $filePath;
            }
        }
        
        return $output;
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
        $name = df\Launchpad::BASE_PACKAGE;
        $this->_packages[$name] = new core\Package($name, 0, df\Launchpad::ROOT_PATH.'/'.$name);
        $this->_packages['app'] = new core\Package('app', PHP_INT_MAX, df\Launchpad::$applicationPath);
        
        return $this;
    }

    public function loadPackages(array $packages) {
        foreach($packages as $package) {
            $package = core\Package::factory($package);
            $this->_packages[$package->name] = $package;
        }
        
        uasort($this->_packages, function($a, $b) {
            return $a->priority < $b->priority;
        });
        
        return $this;
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

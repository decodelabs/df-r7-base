<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core;

use df;
use df\core;

class DevLoader extends Loader {
    
    public function getClassSearchPaths($class) {
        $parts = explode('\\', $class);
        
        if(array_shift($parts) != 'df') {
            return false;
        }
        
        if(!$library = array_shift($parts)) {
            return false;
        }
        
        $output = array();
        
        if($library == 'apex') {
            $section = array_shift($parts);
            $pathName = implode('/', $parts);
            
            switch($section) {
                case 'packages':
                    foreach($this->_locations as $location) {
                        $output[] = $location.'/'.$pathName.'.php';
                    }
                    
                    return $output;
                    
                default:
                    foreach($this->_packages as $package) {
                        $output[] = $package->path.'/'.$section.'/'.$pathName.'.php';
                    } 
                    
                    return $output;
            }
        }
        
        $filename = array_pop($parts);
        $basePath = $library;
        
        if(!empty($parts)) {
            $basePath .= '/'.implode('/', $parts);
        }
        
        $paths = [
            $basePath.'/'.$filename.'.php',
            $basePath.'/_manifest.php'
        ];
        
        foreach($this->_packages as $package) {
            foreach($paths as $path) {
                $output[] = $package->path.'/libraries/'.$path;
            }
        }
       
        return $output;
    }

    public function getFileSearchPaths($path) {
        $parts = explode('/', $path);
        
        if(!$library = array_shift($parts)) {
            return false;
        }
        
        $pathName = implode('/', $parts);
        $output = array();
        
        if($library == 'apex') {
            foreach($this->_packages as $package) {
                $output[] = $package->path.'/'.$pathName;
            }
        } else {
            foreach($this->_packages as $package) {
                $output[] = $package->path.'/libraries/'.$library.'/'.$pathName;
            }
        }
        
        return $output;
    }
}

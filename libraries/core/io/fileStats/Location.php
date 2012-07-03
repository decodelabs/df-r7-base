<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\io\fileStats;

use df;
use df\core;

class Location {
    
    protected $_name;
    protected $_id;
    protected $_path;
    protected $_blackList = array();
    
    protected $_directories = 0;
    protected $_types = array();
    
    public function __construct($name, $path, array $blackList=array()) {
        $this->setName($name);
        $this->setPath($path);
        $this->setBlackList($blackList);
    }
    
    public function setName($name) {
        $this->_name = $name;
        $this->_id = core\string\Manipulator::formatSlug($name);
        return $this;
    }
    
    public function getName() {
        return $this->_name;
    }
    
    public function getId() {
        return $this->_id;
    }
    
    public function setPath($path) {
        $this->_path = core\uri\FilePath::factory($path);
        return $this;
    }
    
    public function getPath() {
        return $this->_path;
    }
    
    public function setBlackList(array $blackList) {
        $this->_blackList = $blackList;
        return $this;
    }
    
    public function getBlackList() {
        return $this->_blackList;
    }
    
    public function run() {
        $this->_parsePath((string)$this->_path);
        
        ksort($this->_types);
    }
    
    public function importLocation(self $location) {
        $this->_directories += $location->_directories;
        
        foreach($location->_types as $type) {
            $ext = $type->getExtension();
            
            if(isset($this->_types[$ext])) {
                $this->_types[$ext]->importType($type);
            } else {
                $this->_types[$ext] = clone $type;
            }
        }
        
        ksort($this->_types);
        
        return $this;
    }
    
    public function getTypes() {
        return $this->_types;
    }
    
    public function getType($type) {
        if(isset($this->_types[$type])) {
            return $this->_types[$type];
        }
        
        return new Type($type);
    }
    
    public function getTotalsType() {
        $output = new Type('TOTAL');
        return $output
            ->setFileCount($this->countFiles())
            ->setLineCount($this->countLines())
            ->setByteCount($this->countBytes());
    }
    
    public function countDirectories() {
        return $this->_directories;
    }
    
    public function countFiles() {
        $output = 0;
        
        foreach($this->_types as $type) {
            $output += $type->countFiles();
        }
        
        return $output;
    }
    
    public function countBytes() {
        $output = 0;
        
        foreach($this->_types as $type) {
            $output += $type->countBytes();
        }
        
        return $output;
    }
    
    public function countLines() {
        $output = 0;
        
        foreach($this->_types as $type) {
            $output += $type->countLines();
        }
        
        return $output;
    }
    
    protected function _parsePath($path) {
        $this->_directories++;
        
        try {
            $dir = new \DirectoryIterator($path);
        } catch(\Exception $e) {
            return;
        }
        
        foreach($dir as $item) {
            if($item->isDot()) {
                continue;
            }
            
            $pathName = $item->getPathname();
            
            if(in_array($pathName, $this->_blackList)) {
                continue;
            }
            
            if($item->isFile()) {
                $this->_parseFile($pathName);
            }
            
            if($item->isDir()) {
                $this->_parsePath($pathName);
            }
        }
    }
    
    protected function _parseFile($file) {
        // Skip temp files
        if(substr($file, -1) == '~') {
            return;        
        }
        
        $file = new File($file);
        $ext = $file->getExtension();
        
        if(!isset($this->_types[$ext])) {
            $this->_types[$ext] = $type = new Type($ext);
        } else {
            $type = $this->_types[$ext];
        }
        
        $type->addFile($file);
    }
}
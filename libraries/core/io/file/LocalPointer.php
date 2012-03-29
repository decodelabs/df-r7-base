<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\io\file;

use df\core;

class LocalPointer implements IFileSystemPointer, core\IDumpable {
    
    protected $_path;
    
    public function __construct($path) {
        $this->_path = (string)core\uri\FilePath::factory($path);
    }
    
    public function open($mode=IMode::READ_WRITE) {
        return new Local($this->_path, $mode);
    }
    
    public function getPath() {
        return $this->_path;
    }
    
    public function isOnDisk() {
        return true;
    }
    
    public function exists() {
        return file_exists($this->_path);
    }
    
    public function getSize() {
        if(!$this->exists()) {
            return 0;
        }
        
        return filesize($this->_path);
    }
    
    public function getContentType() {
        return core\mime\Type::fileToMime($this->_path);
    }
    
    public function getLastModified() {
        return filemtime($this->_path);
    }
    
    public function getContents() {
        if(!$this->exists()) {
            throw new RuntimeException('Cannot read from file pointer');
        }
        
        return file_get_contents($this->_path);
    }
    
    public function putContents($data) {
        if(!$this->exists()) {
            throw new RuntimeException('Cannot read from file pointer');
        }
        
        $this->open(IMode::WRITE_TRUNCATE)->putContents($data);
        
        return $this;
    }
    
    public function saveTo(core\uri\FilePath $path) {
        if(!$this->exists()) {
            throw new RuntimeException('Cannot read from file pointer');
        }
        
        $path = (string)$path;
        
        if(!is_dir(dirname($path))) {
            $mask = umask(0);
            mkdir(dirname($path), 0777, true);
            umask($mask);
        }
        
        file_put_contents($path, $this->getContents());
        return $this;
    }
    
    
// Dump
    public function getDumpProperties() {
        return $this->_path;
    }
}
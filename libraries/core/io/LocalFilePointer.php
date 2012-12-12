<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\io;

use df\core;

class LocalFilePointer implements ILocalFilePointer, core\IDumpable {
    
    protected $_path;
    protected $_contentType;

    public function __construct($path) {
        $this->_path = $path;
        //$this->_path = (string)core\uri\FilePath::factory($path);
    }
    
    public function open($mode=IMode::READ_WRITE) {
        return new core\io\channel\File($this->_path, $mode);
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
    
    public function setContentType($type) {
        $this->_contentType = $type;
        return $this;
    }

    public function getContentType() {
        if($this->_contentType === null) {
            $this->_contentType = core\mime\Type::fileToMime($this->_path);
        }

        return $this->_contentType;
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
        
        $this->open(core\io\IMode::WRITE_TRUNCATE)->putContents($data);
        
        return $this;
    }
    
    public function saveTo(core\uri\FilePath $path) {
        if(!$this->exists()) {
            throw new RuntimeException('Cannot read from file pointer');
        }
        
        $path = (string)$path;
        
        Util::ensureDirExists(dirname($path));
        file_put_contents($path, $this->getContents());
        
        return $this;
    }
    
    
// Dump
    public function getDumpProperties() {
        return $this->_path;
    }
}
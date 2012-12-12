<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\storage\file;

use df\core;

class MemoryFileSystem extends Memory implements core\io\ILocalFilePointer {
    
    private $_path;
    
    public function __construct($data, $path, $contentType, $mode=core\io\IMode::READ_WRITE) {
        parent::__construct($data, $contentType, $mode);
        $this->setPath($path);
    }
    
    public function setPath($path) {
        $this->_path = (string)core\uri\FilePath::factory($path);
        return $this;
    }
    
    public function getPath() {
        return $this->_path;
    }
    
    public function isOnDisk() {
        return false;
    }
}
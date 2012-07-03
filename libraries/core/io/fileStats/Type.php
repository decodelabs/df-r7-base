<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\io\fileStats;

use df;
use df\core;

class Type {
    
    protected $_extension;
    protected $_files = 0;
    protected $_lines = 0;
    protected $_bytes = 0;
    
    public function __construct($extension) {
        $this->_extension = $extension;
    }
    
    public function getExtension() {
        return $this->_extension;
    }
    
    public function addFile(File $file) {
        $this->_files++;
        $this->_lines += $file->countLines();
        $this->_bytes += $file->countBytes();
        
        return $this;
    }
    
    public function importType(self $type) {
        $this->_files += $type->_files;
        $this->_lines += $type->_lines;
        $this->_bytes += $type->_bytes;
        
        return $this;
    }
    
    public function setFileCount($files) {
        $this->_files = (int)$files;
        return $this;
    }
    
    public function countFiles() {
        return $this->_files;
    }
    
    public function setLineCount($lines) {
        $this->_lines = (int)$lines;
        return $this;
    }
    
    public function countLines() {
        return $this->_lines;
    }
    
    public function setByteCount($bytes) {
        $this->_bytes = (int)$bytes;
        return $this;
    }
    
    public function countBytes() {
        return $this->_bytes;
    }
}
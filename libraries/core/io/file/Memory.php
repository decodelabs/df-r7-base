<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\io\file;

use df\core;

class Memory implements core\io\IFile {

    protected $_contentType = null;

    private $_data;
    private $_pos = 0;


    public function __construct($data, $contentType=null, $mode=core\io\IMode::READ_WRITE) {
        $this->putContents($data);
        $this->setContentType($contentType);
    }


// Loading
    public function open($mode=core\io\IMode::READ_WRITE) {
        return $this;
    }

    public function exists() {
        return true;
    }

    public function saveTo(core\uri\FilePath $path) {
        $path = (string)$path;
        
        core\io\Util::ensureDirExists(dirname($path));
        file_put_contents($path, $this->_data);

        return $this;
    }


// Content type
    public function setContentType($type) {
        $this->_contentType = $type;
        return $this;
    }

    public function getContentType() {
        if(!$this->_contentType) {
            $this->_contentType = 'application\octet-stream';
        }
        
        return $this->_contentType;
    }


// Meta
    public function getLastModified() {
        return time();
    }
    
    public function getSize() {
        return strlen($this->_data);
    }
    
    

// Contents
    public function putContents($data) {
        $this->_data = $data;
        return $this;
    }
    
    public function getContents() {
        return $this->_data;
    }
    


// Lock
    public function lock($type, $nonBlocking=false) {
        return true;
    }

    public function unlock() {
        return true;
    }



// Traversal
    public function seek($offset, $whence=\SEEK_SET) {
        switch($whence) {
            case \SEEK_SET:
                $this->_pos = $offset;
                break;
                
            case \SEEK_CUR:
                $this->_pos += $offset;
                break;
                
            case \SEEK_END:
                $this->_pos = strlen($this->_data);
                $this->_pos += $offset;
                break;
                
            default: 
                break;
        }
        
        return $this;
    }

    public function tell() {
        return $this->_pos;
    }



// Housekeeping
    public function flush() {
        return true;
    }

    public function truncate($size=0) {
        $this->_data = substr($this->_data, 0, $size);
        return $this;
    }
    
    public function eof() {
        return $this->_pos >= strlen($this->_data);    
    }
    
    public function close() {
        return true;    
    }
    
    


// Read
    protected function _readChunk($length) {
        $output = substr($this->_data, $this->_pos, $length);
        $this->_pos += $length;
        
        return $output;
    }

    protected function _readLine() {
        $output = '';
        $length = strlen($this->_data);

        while($this->_pos < $length) {
            if($this->_data{$this->_pos} == "\n") {
                $this->_pos++;
                return $output;
            }

            $output .= $this->_data{$this->_pos};
            $this->_pos++;
        }

        return $output;
    }

// Write
    protected function _writeChunk($data, $length) {
        $this->_data .= substr($data, 0, $length);
        $this->_pos = strlen($this->_data);
        
        return $this;
    }
}
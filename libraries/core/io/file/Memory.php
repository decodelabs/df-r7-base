<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\io\file;

use df\core;

class Memory implements IFile {

    use TFile;

    private $_data;
    private $_pos = 0;

    public function __construct($data, $contentType=null, $mode=IMode::READ_WRITE) {
        $this->putContents($data);
        $this->setContentType($contentType);
    }
    
    public function getSize() {
        return strlen($this->_data);
    }
    
    public function getLastModified() {
        return time();
    }
    
    public function putContents($data) {
        $this->_data = $data;
        return $this;
    }
    
    public function getContents() {
        return $this->_data;
    }
    
    public function seek($offset, $whence=SEEK_SET) {
        switch($whence) {
            case SEEK_SET:
                $this->_pos = $offset;
                break;
                
            case SEEK_CUR:
                $this->_pos += $offset;
                break;
                
            case SEEK_END:
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

    public function flush() {
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
    
    public function truncate($size=0) {
        $this->_data = substr($this->_data, 0, $size);
        return $this;
    }
    

// Lock
    public function lock($type, $nonBlocking=false) {
        return true;
    }

    public function unlock() {
        return true;
    }
    
    public function close() {
        return true;    
    }
    
    public function eof() {
        return $this->_pos >= strlen($this->_data);    
    }
}
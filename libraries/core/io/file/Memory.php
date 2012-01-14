<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\io\file;

use df\core;

class Memory extends Base {

    private $_data;
    private $_pos = 0;

    public function __construct($data, $contentType=null, $mode=namespace\READ_WRITE) {
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
    
    public function read($length=1024) {
        $output = substr($this->_data, $this->_pos, $length);
        $this->_pos += $length;
        
        return $output;
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

    public function write($data, $length=null) {
        if(!is_null($length)) {
            $this->_data .= substr($data, 0, $length);
        } else {
            $this->_data .= $data;
        }
        
        $this->_pos = strlen($this->_data);
        
        return $this;
    }
    
    public function truncate($size=0) {
        $this->_data = substr($this->_data, 0, $size);
        return $this;
    }
    
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
<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\io\file;

use df\core;

class Local extends Base implements IFileSystemPointer {

    protected $_fp;
    protected $_mode;
    protected $_path;

    public function __construct($path, $mode=IMode::READ_WRITE) {
        //$this->_path = (string)core\uri\FilePath::factory($path);
        $this->_path = $path;
        $this->open($mode);
    }
    
    public function getPath() {
        return $this->_path;
    }
    
    public function isOnDisk() {
        return true;
    }
    
    public function getSize() {
        return filesize($this->_path);
    }
    
    public function getContentType() {
        if(!$this->_contentType) {
            $this->_contentType = core\mime\Type::fileToMime($this->_path);
        }
        
        return parent::getContentType();
    }
    
    public function getLastModified() {
        return filemtime($this->_path);
    }
    
    public function open($mode=IMode::READ_WRITE) {
        if($this->_fp) {
            if($this->_mode == $mode) {
                return $this;
            }
            
            $this->close();
        }
        
        $this->_mode = $mode;
        
        if($mode{0} == 'r' && !is_readable($this->_path)) {
            throw new RuntimeException('File '.$this->_path.' is not readable!');
        }
        
        $this->_fp = fopen($this->_path, $mode);
        
        return $this;
    }

    public function truncate($size=0) {
        ftruncate($this->_fp, $size);
        return $this;
    }
    
    public function seek($offset, $whence=SEEK_SET) {
        fseek($this->_fp, $offset, $whence);
        return $this;
    }

    public function tell() {
        return ftell($this->_fp);
    }

    public function flush() {
        return fflush($this->_fp);
    }

    public function close() {
        if($this->_fp !== null) {
            @fclose($this->_fp);
            $this->_fp = null;
        }
        
        return $this;
    }

    public function size() {
        $pos = ftell($this->_fp);
        fseek($this->_fp, 0, SEEK_END);
        
        $size = ftell($this->_fp);
        fseek($this->_fp, $pos);
        
        return $size;
    }

    public function read($length=1024) {
        if($length == 0) {
            return '';
        }
        
        if($this->eof()) {
            return false;    
        }

        return fread($this->_fp, $length);

    }

    public function write($data, $length=null) {
        if(is_null($length)) {
            fwrite($this->_fp, $data);
        } else {
            fwrite($this->_fp, $data, $length);
        }
        
        return $this;
    }

    public function lock($type, $nonBlocking=false) {
        if($nonBlocking) {
            return flock($this->_fp, $type | LOCK_NB);
        } else {
            return flock($this->_fp, $type);
        }
        
        return $this;
    }

    public function unlock() {
        if($this->_fp !== null) {
            return flock($this->_fp, LOCK_UN);
        } else {
            return true;
        }
    }
    
    public function eof() {
        return feof($this->_fp);
    }
}
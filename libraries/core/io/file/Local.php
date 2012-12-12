<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\io\file;

use df\core;

class Local implements core\io\IFile, core\io\ILocalFilePointer {

    protected $_fp;
    protected $_mode;
    protected $_path;
    protected $_contentType = null;

    public function __construct($path, $mode=core\io\IMode::READ_WRITE) {
        //$this->_path = (string)core\uri\FilePath::factory($path);
        $this->_path = $path;
        $this->open($mode);
    }
    

// Loading
    public function open($mode=core\io\IMode::READ_WRITE) {
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

    public function exists() {
        return true;
    }

    public function getPath() {
        return $this->_path;
    }
    
    public function isOnDisk() {
        return true;
    }

    public function saveTo(core\uri\FilePath $path) {
        $path = (string)$path;
        
        core\io\Util::ensureDirExists(dirname($path));
        file_put_contents($path, $this->getContents());

        return $this;
    }


// Content type
    public function setContentType($type) {
        $this->_contentType = $type;
        return $this;
    }

    public function getContentType() {
        if(!$this->_contentType) {
            $this->_contentType = core\mime\Type::fileToMime($this->_path);
        }
        
        return $this->_contentType;
    }



// Meta
    public function getLastModified() {
        return filemtime($this->_path);
    }

    public function getSize() {
        return filesize($this->_path);
    }



// Contents
    public function putContents($data) {
        $this->truncate();
        return $this->write($data);
    }
    
    public function getContents() {
        return $this->read();
    }



// Lock
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

    
    
    

// Traversal
    public function seek($offset, $whence=SEEK_SET) {
        fseek($this->_fp, $offset, $whence);
        return $this;
    }

    public function tell() {
        return ftell($this->_fp);
    }



// Housekeeping
    public function flush() {
        return fflush($this->_fp);
    }

    public function truncate($size=0) {
        ftruncate($this->_fp, $size);
        return $this;
    }
    
    public function eof() {
        return feof($this->_fp);
    }

    public function close() {
        if($this->_fp !== null) {
            @fclose($this->_fp);
            $this->_fp = null;
        }
        
        return $this;
    }


// Read
    protected function _readChunk($length) {
        return fread($this->_fp, $length);
    }

    protected function _readLine() {
        try {
            $output = fgets($this->_fp);
        } catch(\Exception $e) {
            return false;
        }

        if($output === ''
        || $output === null
        || $output === false) {
            return false;
        }
        
        return $output;
    }

// Write
    protected function _writeChunk($data, $length) {
        return fwrite($this->_fp, $data, $length);
    }
}
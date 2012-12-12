<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\io\file;

use df\core;

// Exceptions
interface IException {}
class OverflowException extends \OverflowException implements IException {}
class RuntimeException extends \RuntimeException implements IException {}

// Constants
interface IMode {
    const READ_ONLY = 'rb';
    const READ_WRITE = 'r+b';
    const WRITE_TRUNCATE = 'wb';
    const READ_WRITE_TRUNCATE = 'w+b';
    const WRITE_APPEND = 'ab';
    const READ_WRITE_APPEND = 'a+b';
    const WRITE_NEW = 'xb';
    const READ_WRITE_NEW = 'x+b';
}



// Interfaces
interface IPointer {
    public function open($mode=IMode::READ_WRITE);
    public function exists();
    public function getSize();
    public function getContentType();
    public function getLastModified();
    
    public function getContents();
    public function putContents($data);
    
    public function saveTo(core\uri\FilePath $path);
}

interface IFileSystemPointer extends IPointer {
    public function getPath();
    public function isOnDisk();
}

interface ITraversable {
    public function seek($offset, $whence=SEEK_SET);
    public function tell();
    public function flush();
    public function lock($type, $nonBlocking=false);
    public function unlock();
    public function close();
    public function eof();
}

interface IReader extends ITraversable, core\io\IReader {
    //public function getContents();
}

interface IWriter extends ITraversable, core\io\IWriter {
    //public function putContents($data);
    public function truncate($size=0);
}


interface IFile extends IPointer, IReader, IWriter {}


trait TFile {

    use core\io\TReader;
    use core\io\TWriter;

    protected $_contentType = null;
    
    public function open($mode=IMode::READ_WRITE) {
        return $this;
    }
    
    public function exists() {
        return true;
    }
    
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
    
    public function saveTo(core\uri\FilePath $path) {
        $path = (string)$path;
        
        core\io\Util::ensureDirExists(dirname($path));
        file_put_contents($path, $this->getContents());

        return $this;
    }
    
    public function putContents($data) {
        $this->truncate();
        return $this->write($data);
    }
    
    public function getContents() {
        return $this->read();
    }
}
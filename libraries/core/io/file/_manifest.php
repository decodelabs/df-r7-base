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

interface IReader extends ITraversable {
    //public function getContents();
    public function read($length=1);
    public function readByte();
    public function readBytes($num);
    public function readInt();
    public function readLong();
    public function readVInt();
    public function readString();
    public function readUtf8String();
    public function readBinary();
    
}

interface IWriter extends ITraversable {
    //public function putContents($data);
    public function truncate($size=0);
    public function write($data, $length=null);
    public function writeByte($byte);
    public function writeBytes($bytes, $num=null);
    public function writeInt($int);
    public function writeLong($long);
    public function writeVInt($val);
    public function writeString($val);
    public function writeUtf8String($val);
}
<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\io;

use df;
use df\core;


// Exceptions
interface IException {}
class LogicException extends \LogicException implements IException {}
class OverflowException extends \OverflowException implements IException {}



// Interfaces
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



// Reader
interface IReader {
    public function read();
    public function readChunk($length);
    public function readLine();

    public function readByte();
    public function readBytes($num);
    public function readInt();
    public function readLong();
    public function readVInt();
    public function readString();
    public function readUtf8String();
    public function readBinary();

    public function isReadingEnabled();
}


trait TReader {

    public function read() {
        if(!$this->isReadingEnabled()) {
            throw new LogicException(
                'Reading has been shut down'
            );
        }

        $data = false;
        
        while(false !== ($read = $this->_readChunk(1024))) {
            $data .= $read;
        }
        
        return $data;
    }

    public function readChunk($length) {
        if(!$this->isReadingEnabled()) {
            throw new LogicException(
                'Reading has been shut down'
            );
        }
        
        return $this->_readChunk($length);
    }

    public function readLine() {
        if(!$this->isReadingEnabled()) {
            throw new LogicException(
                'Reading has been shut down'
            );
        }

        return $this->_readLine();
    }

    public function readByte() {
        return ord($this->readChunk(1));
    }

    public function readBytes($num) {
        return $this->readChunk($num);
    }

    public function readInt() {
        $output = $this->readChunk(4);

        return ord($output{0}) << 24 |
               ord($output{1}) << 16 |
               ord($output{2}) << 8  |
               ord($output{3});
    }

    public function readLong() {
        $output = $this->readChunk(8);
        
        if(PHP_INT_SIZE > 4) {
            return ord($output{0}) << 56  |
                   ord($output{1}) << 48  |
                   ord($output{2}) << 40  |
                   ord($output{3}) << 32  |
                   ord($output{4}) << 24  |
                   ord($output{5}) << 16  |
                   ord($output{6}) << 8   |
                   ord($output{7});
        } else {
            if((ord($output{0})          != 0) ||
               (ord($output{1})          != 0) ||
               (ord($output{2})          != 0) ||
               (ord($output{3})          != 0) ||
               ((ord($output{0}) & 0x80) != 0)) {
                throw new OverflowException('Data value exceeds 32-bit mode capacity!');
            }

            return ord($output{4}) << 24 |
                   ord($output{5}) << 16 |
                   ord($output{6}) << 8  |
                   ord($output{7});
        }
    }

    public function readVInt() {
        $nextByte = ord($this->readChunk(1));
        $val = $nextByte & 0x7F;

        for($shift=7; ($nextByte & 0x80) != 0; $shift += 7) {
            $nextByte = ord($this->readChunk(1));
            $val |= ($nextByte & 0x7F) << $shift;
        }
        
        return $val;
    }

    public function readString() {
        return $this->readUtf8String();    
    }
    
    public function readUtf8String() {
        $len = $this->readVInt();
        
        if($len == 0) {
            return '';
        }

        $val = $this->readChunk($len);
        for($count=0; $count < $len; $count++) {
            if((ord($val{$count}) & 0xC0) == 0xC0) {
                $addBytes = 1;
                
                if(ord($val{$count}) & 0x20) {
                    $addBytes++;
                    
                    if(ord($val{$count}) & 0x10) {
                        $addBytes++;
                    }
                }
                
                $val .= $this->readChunk($addBytes);
                $len += $addBytes;

                if(ord($val{$count})   == 0xC0 &&
                   ord($val{$count+1}) == 0x80) {
                    $val{$count} = 0;
                    $val = substr($val, 0, $count+1).substr($val, $count+2);
                }
                
                $count += $addBytes;
            }
        }
        
        return $val;
    }

    public function readBinary() {
        return $this->readChunk($this->readVInt());
    }

    public function isReadingEnabled() {
        return true;
    }

    abstract protected function _readChunk($length);
    abstract protected function _readLine();
}




// Peek reader
interface IPeekReader extends IReader {
    public function peek($length);
}


trait TPeekReader {

    public function peek($length) {
        if(!$this->isReadingEnabled()) {
            throw new LogicException(
                'Reading has been shut down'
            );
        }
        
        return $this->_peekChunk($length);
    }

    abstract protected function _peekChunk($length);
}



// Writer
interface IWriter {
    public function write($data);
    public function writeLine($line);
    public function writeChunk($data, $length);
    public function writeBuffer(&$buffer, $length);

    public function writeByte($byte);
    public function writeBytes($bytes, $num=null);
    public function writeInt($int);
    public function writeLong($long);
    public function writeVInt($val);
    public function writeString($val);
    public function writeUtf8String($val);

    public function isWritingEnabled();
}


trait TWriter {

    public function write($data) {
        if(!$this->isWritingEnabled()) {
            throw new LogicException(
                'Writing has already been shut down'
            );
        }
        
        if(!$length = strlen($data)) {
            return $this;
        }
        
        for($written = 0; $written < $length; $written += $result) {
            $result = $this->_writeChunk(substr($data, $written), $length - $written);
            
            if($result === false) {
                throw new OverflowException(
                    'Unable to write to channel'
                );
            }
        }
        
        return $this;
    }

    public function writeLine($line) {
        return $this->write($line."\r\n");
    }
    
    public function writeChunk($data, $length) {
        if(!$this->isWritingEnabled()) {
            throw new LogicException(
                'Writing has already been shut down'
            );
        }

        $length = (int)$length;

        if($length <= 0) {
            $length = strlen($data);
        }
        
        return $this->_writeChunk($data, $length);
    }

    public function writeBuffer(&$buffer, $length) {
        $result = $this->writeChunk($buffer, $length);
        $buffer = substr($buffer, $result);
        return $result;
    }



    public function writeByte($byte) {
        return $this->writeChunk(chr($byte), 1);
    }

    public function writeBytes($bytes, $num=null) {
        return $this->writeChunk($bytes, $num);
    }

    public function writeInt($int) {
        $int = (int)$int;
        
        return $this->writeChunk(
            chr($int >> 24 & 0xFF).
            chr($int >> 16 & 0xFF).
            chr($int >> 8  & 0xFF).
            chr($int       & 0xFF), 4);
    }

    public function writeLong($long) {
        if(PHP_INT_SIZE > 4) {
            $long = (int)$long;
            
            $this->writeChunk(
                chr($long >> 56 & 0xFF).
                chr($long >> 48 & 0xFF).
                chr($long >> 40 & 0xFF).
                chr($long >> 32 & 0xFF).
                chr($long >> 24 & 0xFF).
                chr($long >> 16 & 0xFF).
                chr($long >> 8  & 0xFF).
                chr($long       & 0xFF), 8);
        } else {
            if($long > 0x7FFFFFFF) {
                throw new OverflowException('Data value exceeds 32-bit mode capacity!');
            }

            $this->writeChunk(
                "\x00\x00\x00\x00".
                chr($long >> 24 & 0xFF).
                chr($long >> 16 & 0xFF).
                chr($long >> 8  & 0xFF).
                chr($long       & 0xFF), 8);
        }
    }

    public function writeVInt($val) {
        $val = (int)$val;
        
        while($val > 0x7F) {
            $this->write(chr(($val & 0x7F) | 0x80));
            $val >>= 7;
        }
        
        $this->write(chr($val));
    }
    
    public function writeString($val) {
        return $this->writeUtf8String($val);    
    }
    
    public function writeUtf8String($val) {
        $val = (string)$val;
        $chars = $len = strlen($val);
        $nullChars = false;

        for($count = 0; $count < $len; $count++) {
            if((ord($val{$count}) & 0xC0) == 0xC0) {
                $addBytes = 1;
                
                if(ord($val{$count}) & 0x20) {
                    $addBytes++;
                    
                    if(ord($val{$count}) & 0x10) {
                        $addBytes++;
                    }
                }
                
                $chars -= $addBytes;
                
                if(ord($val{$count}) == 0) {
                    $nullChars = true;
                }
                
                $count += $addBytes;
            }
        }

        if($chars < 0) {
            //throw new UnexpectedValueException('Invalid UTF-8 string!');
        }

        $this->writeVInt($chars);
        
        if($nullChars) {
            $this->write(str_replace($val, "\x00", "\xC0\x80"));
        } else {
            $this->write($val);
        }
    }


    public function isWritingEnabled() {
        return true;
    }

    
    abstract protected function _writeChunk($data, $length);
}




// Channel
interface IChannel extends IReader, IWriter {
    public function getChannelId();
    public function writeError($error);
    public function writeErrorLine($line);
}


// File
interface IFilePointer {
    public function open($mode=IMode::READ_WRITE);
    public function exists();

    public function setContentType($type);
    public function getContentType();
    
    public function getLastModified();
    public function getSize();
    
    public function putContents($data);
    public function getContents();
    
    public function saveTo(core\uri\FilePath $path);
}

interface ILocalFilePointer extends IFilePointer {
    public function getPath();
    public function isOnDisk();
}


interface IFile extends IFilePointer, IChannel {
    //public function getContents();
    //public function putContents($data);

    public function lock($type, $nonBlocking=false);
    public function unlock();

    public function seek($offset, $whence=\SEEK_SET);
    public function tell();
    
    public function flush();
    public function truncate($size=0);
    public function eof();
    public function close();
}








// Util
interface IUtil {
    public static function readFileExclusive($path);
    public static function writeFileExclusive($path, $data);

    public static function copyFile($source, $destination);
    public static function deleteFile($path);

    public static function copyDir($source, $destination, $merge=false);
    public static function copyDirInto($source, $destination);
    public static function ensureDirExists($path, $perms=0777);
    public static function isDirEmpty($path);
    public static function deleteDir($path);
    public static function emptyDir($path);

    public static function chmod($path, $mode, $recursive=false);

    public static function stripLocationFromFilePath($path);
}
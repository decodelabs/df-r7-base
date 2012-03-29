<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\io\file;

use df\core;

abstract class Base implements IPointer, IReader, IWriter {
    
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
        
        if(!is_dir(dirname($path))) {
            $mask = umask(0);
            mkdir(dirname($path), 0777, true);
            umask($mask);
        }
        
        file_put_contents($path, $this->getContents());
        return $this;
    }
    
    public function putContents($data) {
        $this->truncate();
        return $this->write($data);
    }
    
    public function getContents() {
        $output = '';
        
        while(false !== ($part = $this->read())) { 
            $output .= $part;
        }
        
        return $output;
    }
    
    public function readByte() {
        return ord($this->read(1));
    }

    public function writeByte($byte) {
        return $this->write(chr($byte), 1);
    }

    public function readBytes($num) {
        return $this->read($num);
    }

    public function writeBytes($bytes, $num=null) {
        return $this->write($bytes, $num);
    }

    public function readInt() {
        $output = $this->read(4);

        return ord($output{0}) << 24 |
               ord($output{1}) << 16 |
               ord($output{2}) << 8  |
               ord($output{3});
    }

    public function writeInt($int) {
        settype($int, 'integer');
        
        return $this->write(
            chr($int >> 24 & 0xFF).
            chr($int >> 16 & 0xFF).
            chr($int >> 8  & 0xFF).
            chr($int       & 0xFF), 4);
    }

    public function readLong() {
        $output = $this->read(8);
        
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

    public function writeLong($long) {
        if(PHP_INT_SIZE > 4) {
            settype($long, 'integer');
            
            $this->write(
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

            $this->write(
                "\x00\x00\x00\x00".
                chr($long >> 24 & 0xFF).
                chr($long >> 16 & 0xFF).
                chr($long >> 8  & 0xFF).
                chr($long       & 0xFF), 8);
        }
    }

    public function readVInt() {
        $nextByte = ord($this->read(1));
        $val = $nextByte & 0x7F;

        for($shift=7; ($nextByte & 0x80) != 0; $shift += 7) {
            $nextByte = ord($this->read(1));
            $val |= ($nextByte & 0x7F) << $shift;
        }
        
        return $val;
    }

    public function writeVInt($val) {
        settype($val, 'integer');
        
        while($val > 0x7F) {
            $this->write(chr(($val & 0x7F) | 0x80));
            $val >>= 7;
        }
        
        $this->write(chr($val));
    }
    
    public function readString() {
        return $this->readUtf8String();    
    }
    
    public function readUtf8String() {
        $len = $this->readVInt();
        
        if($len == 0) {
            return '';
        }

        $val = $this->read($len);
        for($count=0; $count < $len; $count++) {
            if((ord($val{$count}) & 0xC0) == 0xC0) {
                $addBytes = 1;
                
                if(ord($val{$count}) & 0x20) {
                    $addBytes++;
                    
                    if(ord($val{$count}) & 0x10) {
                        $addBytes++;
                    }
                }
                
                $val .= $this->read($addBytes);
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
    
    public function writeString($val) {
        return $this->writeUtf8String($val);    
    }
    
    public function writeUtf8String($val) {
        settype($val, 'string');
        $chars = $len = strlen($val);
        $nullChars = false;

        for($count=0; $count<$len; $count++) {
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
            //throw new Core_StorageException('Invalid UTF-8 string!');
        }

        $this->writeVInt($chars);
        
        if($nullChars) {
            $this->write(str_replace($val, "\x00", "\xC0\x80"));
        } else {
            $this->write($val);
        }
    }

    public function readBinary() {
        return $this->read($this->readVInt());
    }
    
    public function writeNewline() {
        return $this->write("\r\n");
    }
}
<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\io\channel;

use df;
use df\core;
use df\halo;

class NamedPipe extends Stream {
    
    private static $_isWin = null;

    protected $_path;
    protected $_mode;

    public function __construct($path, $mode) {
        $blocking = false;

        switch($mode) {
            case 'r':
            case 'w':
                break;

            case 'rb':
                $mode = 'r';
                $blocking = true;
                break;

            case 'wb':
                $mode = 'w';
                $blocking = true;
                break;

            default:
                throw new core\io\InvalidArgumentException(
                    'Named pipe mode should be r, w, rb or wb'
                );
        }

        if(self::$_isWin === null) {
            self::$_isWin = halo\system\Base::getInstance()->getPlatformType() == 'Windows';
        }

        if(self::$_isWin) {
            $this->_resource = $this->_loadWindowsPipe($path, $mode, $blocking);
        } else {
            $this->_resource = $this->_loadUnixPipe($path, $mode, $blocking);
        }

        $this->_path = $path;
        $this->_mode = $mode;
    }

    public function __destruct() {
        if(!static::$_isWin && $this->_mode == 'w') {
            core\io\Util::deleteFile($this->_path);
        }
    }

    protected function _loadUnixPipe($path, $mode, $blocking) {
        if(!file_exists($path)) {
            if($mode == 'r') {
                throw new core\io\RuntimeException(
                    'Named pipe could not found for reading'
                );
            }

            core\io\Util::ensureDirExists(dirname($path));
            posix_mkfifo($path, 0777);
        }

        if($blocking) {
            if($mode == 'r') {
                $mode .= '+';
            }
        } else {
            $mode = 'r+';
        }

        return fopen($path, $mode);
    }

    protected function _loadWindowsPipe($path, $mode, $blocking) {
        $name = php_uname('n');
        return fopen('\\\\'.$name.'\\pipe\\df-'.md5($path), $mode);
    }

    public function exists() {
        if(self::$_isWin) {
            core\stub(); //??
        } else {
            return file_exists($this->_path);
        }
    }
}
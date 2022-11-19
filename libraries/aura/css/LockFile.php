<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\css;

use DecodeLabs\Atlas;

use DecodeLabs\Exceptional;
use df\core;

class LockFile
{
    protected $_path;
    protected $_fileName = '.lock';
    protected $_timeout = 30;
    protected $_isLocked = false;

    public function __construct($path = null, $timeout = null)
    {
        if ($path !== null) {
            $this->setPath($path);
        }

        if ($timeout !== null) {
            $this->setTimeout($timeout);
        }
    }


    public function __destruct()
    {
        if ($this->isLocked()) {
            $this->unlock();
        }
    }

    public function setPath($path)
    {
        $this->_path = $path;
        return $this;
    }

    public function getPath()
    {
        return $this->_path;
    }

    public function setFileName($name)
    {
        $this->_fileName = $name;
        return $this;
    }

    public function getFileName()
    {
        return $this->_fileName;
    }

    public function setTimeout($timeout)
    {
        $this->_timeout = core\time\Duration::factory($timeout)->getSeconds();
        return $this;
    }

    public function getTimeout()
    {
        return $this->_timeout;
    }

    public function isLocked()
    {
        return $this->_isLocked;
    }

    public function canLock()
    {
        if (!$this->_path || !$this->_fileName) {
            throw Exceptional::Runtime(
                'Cannot check lock - path not set'
            );
        }

        if ($this->_isLocked) {
            return true;
        }

        $time = $this->getRemainingTime();

        if (!$time) {
            $this->unlock();
            return true;
        }

        return false;
    }

    public function getRemainingTime()
    {
        $file = Atlas::file($this->_path . '/' . $this->_fileName);
        $file->clearStatCache();

        if (!$file->exists()) {
            return 0;
        }

        $data = $file->getContents();
        @list($time, $timeout) = explode(':', $data, 2);

        if (!is_numeric($time) || !is_numeric($timeout)) {
            $file->delete();
            return 0;
        }

        if (!$timeout) {
            $timeout = $this->_timeout;
        }

        $output = $timeout - (time() - (int)$time);

        if ($output < 0) {
            $output = 0;
        }

        return $output;
    }

    public function lock()
    {
        if (!$this->_isLocked && !$this->canLock()) {
            throw Exceptional::Runtime(
                'Unable to create lock file - already locked in another process'
            );
        }

        Atlas::createFile(
            $this->_path . '/' . $this->_fileName,
            time() . ':' . $this->_timeout
        );

        $this->_isLocked = true;
        return $this;
    }

    public function unlock()
    {
        Atlas::deleteFile($this->_path . '/' . $this->_fileName);
        $this->_isLocked = false;
        return $this;
    }
}

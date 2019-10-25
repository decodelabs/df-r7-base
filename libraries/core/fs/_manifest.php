<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\fs;

use df;
use df\core;

class Mode extends core\lang\Enum
{
    const READ_ONLY = 'rb';
    const READ_WRITE = 'r+b';
    const WRITE_TRUNCATE = 'wb';
    const READ_WRITE_TRUNCATE = 'w+b';
    const WRITE_APPEND = 'ab';
    const READ_WRITE_APPEND = 'a+b';
    const WRITE_NEW = 'xb';
    const READ_WRITE_NEW = 'x+b';

    public function canCreate()
    {
        switch ($this->getLabel()) {
            case self::WRITE_TRUNCATE:
            case self::READ_WRITE_TRUNCATE:
            case self::WRITE_APPEND:
            case self::READ_WRITE_APPEND:
            case self::WRITE_NEW:
            case self::READ_WRITE_NEW:
                return true;

            default:
                return false;
        }
    }
}



## File
interface INode extends core\IStringProvider
{
    public function getPath();
    public function getName(): string;
    public function exists();
    public function clearStatCache();
    public function getLastModified();
    public function isRecent($timeout);

    public function getPermissions();
    public function getOwner();
    public function getGroup();

    public function renameTo($newName);
    public function moveTo($destination, $newName=null);
    public function unlink();
}

trait TNode
{
    use core\TStringProvider;

    public function getName(): string
    {
        return basename($this->getPath());
    }

    public function clearStatCache()
    {
        clearstatcache(true, $this->getPath());
        return $this;
    }

    public function isRecent($timeout)
    {
        if (!$this->exists()) {
            return false;
        }

        if (!is_int($timeout)) {
            $timeout = core\time\Duration::factory($timeout)->getSeconds();
        }

        if (time() - $this->getLastModified() > $timeout) {
            return false;
        }

        return true;
    }

    public function toString(): string
    {
        $output = $this->getPath();

        if ($output === null) {
            $output = $this->getName();
        }

        return $output;
    }
}

interface IFile extends INode, core\io\IChannel
{
    public function isOnDisk();
    public function getSize();

    public function setContentType($type);
    public function getContentType();

    public function getHash($type);
    public function getRawHash($type);

    public function putContents($data);
    public function getContents();

    public function setPermissions($mode);
    public function setOwner($owner);
    public function setGroup($group);

    public function open($mode=Mode::READ_WRITE);
    public function isOpen();
    public function isTemp();
    public function eof();
    public function close();

    public function lock($type, $nonBlocking=false);
    public function unlock();

    public function seek($offset, $whence=\SEEK_SET);
    public function readFrom($offset, $length);
    public function tell();
    public function flush();
    public function truncate($size=0);
}

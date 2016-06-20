<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\fs;

use df;
use df\core;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class LogicException extends \LogicException implements IException {}
class OverflowException extends \OverflowException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}


// Interfaces
class Mode extends core\lang\Enum {
    const READ_ONLY = 'rb';
    const READ_WRITE = 'r+b';
    const WRITE_TRUNCATE = 'wb';
    const READ_WRITE_TRUNCATE = 'w+b';
    const WRITE_APPEND = 'ab';
    const READ_WRITE_APPEND = 'a+b';
    const WRITE_NEW = 'xb';
    const READ_WRITE_NEW = 'x+b';

    public function canCreate() {
        switch($this->getLabel()) {
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
interface INode extends core\IStringProvider {
    public function getPath();
    public function getLocationPath();
    public function getName();
    public function exists();
    public function getLastModified();
    public function isRecent($timeout);

    public function getPermissions();
    public function getOwner();
    public function getGroup();

    public function copyTo($destination);
    public function renameTo($newName);
    public function moveTo($destination, $newName=null);
    public function unlink();
}

trait TNode {

    use core\TStringProvider;

    public function getName() {
        return basename($this->getPath());
    }

    public function getLocationPath() {
        if(!$path = $this->getPath()) {
            return null;
        }

        return core\fs\Dir::stripPathLocation($path);
    }

    public function isRecent($timeout) {
        if(!$this->exists()) {
            return false;
        }

        if(!is_int($timeout)) {
            $timeout = core\time\Duration::factory($timeout)->getSeconds();
        }

        if(time() - $this->getLastModified() > $timeout) {
            return false;
        }

        return true;
    }

    public function toString(): string {
        $output = $this->getPath();

        if($output === null) {
            $output = $this->getName();
        }

        return $output;
    }
}

interface IFile extends INode, core\io\IChannel {

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


trait TFile {

    use TNode;

    public function copyTo($path) {
        $target = self::factory($path);
        $target->open(Mode::WRITE_TRUNCATE);
        $closeAfter = false;

        if(!$this->_fp) {
            $closeAfter = true;
            $this->open(Mode::READ_ONLY);
        }

        while(!$this->eof()) {
            $target->write($this->readChunk(8192));
        }

        if($closeAfter) {
            $this->close();
        }

        $target->close();
        return $target;
    }
}


interface ILockFile {
    public function setPath($path);
    public function getPath();
    public function setFileName($name);
    public function getFileName();
    public function setTimeout($timeout);
    public function getTimeout();
    public function getRemainingTime();
    public function isLocked();
    public function canLock();
    public function lock();
    public function unlock();
}




interface IDirectory extends INode {
    public function ensureExists($perms=null);
    public function isEmpty();

    public function setPermissions($mode, $recursive=false);
    public function setOwner($owner, $recursive=false);
    public function setGroup($group, $recursive=false);


    public function scan($filter=null);
    public function scanNames($filter=null);
    public function countContents($filter=null);
    public function listContents($filter=null);
    public function listNames($filter=null);

    public function scanFiles($filter=null);
    public function scanFileNames($filter=null);
    public function countFiles($filter=null);
    public function listFiles($filter=null);
    public function listFileNames($filter=null);

    public function scanDirs($filter=null);
    public function scanDirNames($filter=null);
    public function countDirs($filter=null);
    public function listDirs($filter=null);
    public function listDirNames($filter=null);

    public function scanRecursive($filter=null);
    public function scanNamesRecursive($filter=null);
    public function countContentsRecursive($filter=null);
    public function listContentsRecursive($filter=null);
    public function listNamesRecursive($filter=null);

    public function scanFilesRecursive($filter=null);
    public function scanFileNamesRecursive($filter=null);
    public function countFilesRecursive($filter=null);
    public function listFilesRecursive($filter=null);
    public function listFileNamesRecursive($filter=null);

    public function scanDirsRecursive($filter=null);
    public function scanDirNamesRecursive($filter=null);
    public function countDirsRecursive($filter=null);
    public function listDirsRecursive($filter=null);
    public function listDirNamesRecursive($filter=null);


    public function getParent();
    public function getChild($name);
    public function getExistingChild($name);
    public function deleteChild($name);
    public function createDir($path);
    public function hasDir($name);
    public function getDir($name);
    public function getExistingDir($name);
    public function deleteDir($name);
    public function createFile($name, $content);
    public function newFile($name, $mode=Mode::READ_WRITE_NEW);
    public function hasFile($name);
    public function getFile($name);
    public function getExistingFile($name);
    public function deleteFile($name);

    public function emptyOut();
    public function mergeInto($destination);
}
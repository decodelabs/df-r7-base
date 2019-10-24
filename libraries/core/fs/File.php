<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\fs;

use df;
use df\core;

use DecodeLabs\Atlas;

use DecodeLabs\Glitch;
use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class File implements IFile, core\io\IContainedStateChannel, Inspectable
{
    use core\io\TReader;
    use core\io\TWriter;
    use TFile;

    protected $_fp;
    protected $_error = '';
    protected $_mode;
    protected $_path;
    protected $_contentType = null;
    protected $_isTemp = false;


    // Static
    public static function create($path, $data)
    {
        return self::factory($path, Mode::WRITE_TRUNCATE)
            ->putContents($data)
            ->close();
    }

    public static function createTemp($mode=null)
    {
        if ($mode === null) {
            $mode = Mode::READ_WRITE;
        }

        $output = new self(
            tempnam(
                sys_get_temp_dir(),
                df\Launchpad::$app->getUniquePrefix().'-'
            ),
            $mode
        );

        $output->_isTemp = true;
        return $output;
    }

    public static function getContentsOf($path)
    {
        return self::factory($path, Mode::READ_ONLY)->getContents();
    }


    public static function isFileRecent($path, $timeout)
    {
        return self::factory($path)->isRecent($timeout);
    }

    public static function setPermissionsOn($path, $mode)
    {
        return self::factory($path)->setPermissions($mode);
    }

    public static function setOwnerOn($path, $owner)
    {
        return self::factory($path)->setOwner($owner);
    }

    public static function setGroupOn($path, $group)
    {
        return self::factory($path)->setGroup($group);
    }



    public static function copy($from, $to)
    {
        if (is_string($from) && is_dir($from)) {
            return Dir::factory($from)->copyTo($to);
        }

        return self::factory($from)->copyTo($to);
    }

    public static function rename($from, $to)
    {
        if (is_string($from) && is_dir($from)) {
            return Dir::factory($from)->renameTo($to);
        }

        return self::factory($from)->renameTo($to);
    }

    public static function move($from, $to)
    {
        if (is_string($from) && is_dir($from)) {
            return Dir::factory($from)->moveTo($to);
        }

        return self::factory($from)->moveTo($to);
    }

    public static function delete($path)
    {
        if (is_string($path) && is_dir($path)) {
            return Dir::factory($path)->unlink();
        }

        return self::factory($path)->unlink();
    }


    // Init
    public static function factory($file, $mode=null)
    {
        if ($file instanceof IFile) {
            return $file;
        }

        return new self($file, $mode);
    }


    public function __construct($path, $mode=null)
    {
        $this->_path = $path;

        if ($mode !== null) {
            $this->open($mode);
        }
    }

    public function __destruct()
    {
        $this->close();

        if ($this->_isTemp) {
            $this->unlink();
        }
    }

    // Info
    public function getChannelId()
    {
        return $this->_path;
    }

    public function getPath()
    {
        return $this->_path;
    }

    public function isOnDisk()
    {
        // check path
        return true;
    }

    public function exists()
    {
        if ($this->_fp) {
            return true;
        }

        return file_exists($this->_path) || is_link($this->_path);
    }


    // Meta
    public function getLastModified()
    {
        return filemtime($this->_path);
    }

    public function getSize()
    {
        return filesize($this->_path);
    }


    // Content type
    public function setContentType($type)
    {
        $this->_contentType = $type;
        return $this;
    }

    public function getContentType()
    {
        if (!$this->_contentType) {
            $this->_contentType = Atlas::$mime->detect($this->_path);
        }

        return $this->_contentType;
    }


    // Hash
    public function getHash($type)
    {
        return hash_file($type, $this->_path);
    }

    public function getRawHash($type)
    {
        return hash_file($type, $this->_path, true);
    }




    // Contents
    public function putContents($data)
    {
        if (!$data instanceof core\io\IReader) {
            $data = new MemoryFile((string)$data);
        }

        if ($data instanceof IFile && !$data->isOpen()) {
            $data->open(Mode::READ_ONLY);
        }

        $closeAfter = false;

        if (!$this->_fp) {
            $closeAfter = true;
            $this->open(Mode::WRITE_TRUNCATE);
        }

        $this->lock(LOCK_EX);
        $this->truncate();

        while (false !== ($chunk = $data->readChunk(8192))) {
            $this->write($chunk);
        }

        $this->unlock();

        if ($closeAfter) {
            $this->close();
        }

        if ($data instanceof IFile) {
            $data->close();
        }

        return $this;
    }

    public function getContents()
    {
        $closeAfter = false;

        if (!$this->_fp) {
            $closeAfter = true;
            $this->open(Mode::READ_ONLY);
        }

        $output = '';
        $this->lock(LOCK_SH);

        try {
            // Not really sure what to do here?
            $this->seek(0);
        } catch (\Throwable $e) {
        }

        while (!$this->eof()) {
            $output .= $this->readChunk(8192);
        }

        $this->unlock();

        if ($closeAfter) {
            $this->close();
        }

        return $output;
    }


    public function setPermissions($mode)
    {
        chmod($this->_path, $mode);
        return true;
    }

    public function getPermissions()
    {
        return fileperms($this->_path);
    }

    public function setOwner($owner)
    {
        chown($this->_path, $owner);
        return $this;
    }

    public function getOwner()
    {
        return fileowner($this->_path);
    }

    public function setGroup($group)
    {
        chgrp($this->_path, $group);
        return $this;
    }

    public function getGroup()
    {
        return filegroup($this->_path);
    }



    public function renameTo($newName)
    {
        if ($this->exists()) {
            throw Glitch::{'ENotFound'}(
                'Source file does not exist'
            );
        }

        $destination = dirname($this->_path).'/'.$newName;

        if (file_exists($destination)) {
            throw Glitch::{'EAlreadyExists,ERuntime'}(
                'Destination file already exists'
            );
        }

        rename($this->_path, $destination);
        $this->_path = $destination;
        $this->_isTemp = false;

        return $this;
    }

    public function moveTo($destination, $newName=null)
    {
        if (!$this->exists()) {
            throw Glitch::{'ENotFound'}(
                'Source file does not exist'
            );
        }

        if (!strlen($newName)) {
            $newName = basename($this->_path);
        }

        $destination = rtrim($destination, '/').'/'.$newName;

        if (file_exists($destination)) {
            throw Glitch::{'EAlreadyExists,ERuntime'}(
                'Destination directory already exists'
            );
        }

        rename($this->_path, $destination);
        $this->_path = $destination;
        $this->_isTemp = false;

        return $this;
    }

    public function unlink()
    {
        $exists = $this->exists();
        $this->close();

        if ($exists) {
            try {
                unlink($this->_path);
            } catch (\Throwable $e) {
                if ($this->exists()) {
                    throw $e;
                }
            }
        }

        return $this;
    }





    // Open
    public function open($mode=Mode::READ_WRITE)
    {
        if ($this->_fp) {
            if ($this->_mode->is($mode)) {
                return $this;
            }

            $this->close();
        }

        $this->_mode = Mode::factory($mode);

        /*
        if($this->_mode->is(Mode::READ_ONLY) && !is_readable($this->_path)) {
            throw Glitch::ERuntime('File '.$this->_path.' is not readable!');
        }
        */

        if ($this->_mode->canCreate() && !file_exists($this->_path)) {
            Dir::create(dirname($this->_path));
        }

        $this->_fp = fopen($this->_path, $this->_mode->getLabel());

        return $this;
    }

    public function isOpen()
    {
        return $this->_fp !== null;
    }

    public function isTemp()
    {
        return $this->_isTemp;
    }

    public function eof()
    {
        if ($this->_fp) {
            return feof($this->_fp);
        } else {
            return true;
        }
    }

    public function close()
    {
        if ($this->_fp !== null) {
            @fclose($this->_fp);
            $this->_fp = null;
        }

        return $this;
    }


    // Lock
    public function lock($type, $nonBlocking=false)
    {
        if (!$this->_fp) {
            throw Glitch::ERuntime(
                'File is not open: '.$this->_fp
            );
        }

        if ($nonBlocking) {
            return flock($this->_fp, $type | LOCK_NB);
        } else {
            return flock($this->_fp, $type);
        }

        return $this;
    }

    public function unlock()
    {
        if ($this->_fp !== null) {
            return flock($this->_fp, LOCK_UN);
        } else {
            return true;
        }
    }



    // IO
    public function seek($offset, $whence=SEEK_SET)
    {
        if (!$this->_fp) {
            throw Glitch::ERuntime(
                'File is not open: '.$this->_fp
            );
        }

        fseek($this->_fp, $offset, $whence);
        return $this;
    }

    public function readFrom($offset, $length)
    {
        if (!$this->_fp) {
            throw Glitch::ERuntime(
                'File is not open: '.$this->_fp
            );
        }

        fseek($this->_fp, $offset);
        return $this->_readChunk($length);
    }

    public function tell()
    {
        if (!$this->_fp) {
            throw Glitch::ERuntime(
                'File is not open: '.$this->_fp
            );
        }

        return ftell($this->_fp);
    }

    public function flush()
    {
        if (!$this->_fp) {
            throw Glitch::ERuntime(
                'File is not open: '.$this->_fp
            );
        }

        return fflush($this->_fp);
    }

    public function truncate($size=0)
    {
        if ($this->_fp) {
            ftruncate($this->_fp, $size);
        } else {
            $this->open(Mode::WRITE_TRUNCATE);
            $this->close();
        }

        return $this;
    }



    // Read
    public function readChar()
    {
        if (!$this->_fp) {
            throw Glitch::ERuntime(
                'File is not open: '.$this->_fp
            );
        }

        return fgetc($this->_fp);
    }

    protected function _readChunk($length)
    {
        if ($length <= 0) {
            return '';
        }

        if (!$this->_fp) {
            throw Glitch::ERuntime(
                'File is not open: '.$this->_fp
            );
        }

        return fread($this->_fp, $length);
    }

    protected function _readLine()
    {
        if (!$this->_fp) {
            throw Glitch::ERuntime(
                'File is not open: '.$this->_fp
            );
        }

        try {
            $output = fgets($this->_fp);
        } catch (\Throwable $e) {
            return false;
        }

        if ($output === '' || $output === false) {
            return false;
        }

        return $output;
    }

    // Write
    protected function _writeChunk($data, $length)
    {
        if (!$this->_fp) {
            throw Glitch::ERuntime(
                'File is not open: '.$this->_fp
            );
        }

        return fwrite($this->_fp, $data, $length);
    }

    // Error
    public function getErrorBuffer()
    {
        return $this->_error;
    }

    public function flushErrorBuffer()
    {
        $output = $this->_error;
        $this->_error = null;

        return $output;
    }

    public function writeError($error)
    {
        $this->_error .= $error;
        return $this;
    }

    public function writeErrorLine($line)
    {
        return $this->writeError($line."\r\n");
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setDefinition($this->_path)
            ->setProperty('*mode', $inspector($this->_mode));
    }
}

<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\fs;

use df\core;
use df\halo;

use DecodeLabs\Systemic;

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class MemoryFile implements IFile, core\io\IContainedStateChannel, Inspectable
{
    use core\io\TReader;
    use core\io\TWriter;
    use TFile;

    protected $_contentType = null;
    protected $_id;

    private $_data;
    private $_error;
    private $_pos = 0;

    private $_isOpen = false;
    private $_isLocked = false;

    private $_permissions = 0777;
    private $_owner = null;
    private $_group = null;


    public function __construct($data='', $contentType=null, $mode=Mode::READ_WRITE)
    {
        try {
            $process = Systemic::$process->getCurrent();

            try {
                $this->_owner = $process->getOwnerName();
            } catch (\Throwable $e) {
            }

            try {
                $this->_group = $process->getGroupName();
            } catch (\Throwable $e) {
            }
        } catch (\Throwable $e) {
        }

        $this->putContents($data);
        $this->setContentType($contentType);

        if ($mode !== null) {
            $this->open($mode);
        }
    }

    public function setId(?string $id)
    {
        $this->_id = $id;
        return $this;
    }

    public function getId(): ?string
    {
        return $this->_id;
    }

    public function getName(): string
    {
        return $this->getChannelId();
    }

    public function getChannelId()
    {
        $output = 'Memory';

        if ($this->_id) {
            $output .= ':'.$this->_id;
        }

        return $output;
    }



    public function getPath()
    {
        return null;
    }

    public function isOnDisk()
    {
        return false;
    }

    public function exists()
    {
        return true;
    }

    public function getLastModified()
    {
        return time();
    }

    public function getSize()
    {
        return strlen($this->_data);
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
            $this->_contentType = 'application\octet-stream';
        }

        return $this->_contentType;
    }


    // Hash
    public function getHash($type)
    {
        return hash($type, $this->_data);
    }

    public function getRawHash($type)
    {
        return hash($type, $this->_data, true);
    }





    // Contents
    public function putContents($data)
    {
        $this->_data = $data;
        return $this;
    }

    public function getContents()
    {
        return $this->_data;
    }

    public function setPermissions($mode)
    {
        $this->_permissions = $mode;
        return $this;
    }

    public function getPermissions()
    {
        return $this->_permissions;
    }

    public function setOwner($owner)
    {
        return $this;
    }

    public function getOwner()
    {
        return $this->_owner;
    }

    public function setGroup($group)
    {
        return $this;
    }

    public function getGroup()
    {
        return $this->_group;
    }



    public function renameTo($newName)
    {
        return $this;
    }

    public function moveTo($destination, $newName=null)
    {
        return $this;
    }

    public function unlink()
    {
        $this->_data = '';
        $this->_pos = 0;
        $this->_isLocked = false;
        $this->_isOpen = false;

        return $this;
    }





    // Open
    public function open($mode=Mode::READ_WRITE)
    {
        if ($this->_isOpen) {
            switch ($this->_mode->getLabel()) {
                case Mode::READ_ONLY:
                case Mode::READ_WRITE:
                    $this->_pos = 0;
                    break;

                case Mode::WRITE_TRUNCATE:
                case Mode::READ_WRITE_TRUNCATE:
                    $this->_data = '';
                    $this->_pos = 0;
                    break;

                case Mode::WRITE_APPEND:
                case Mode::READ_WRITE_APPEND:
                    $this->_pos = strlen($this->_data);
                    break;

                case Mode::WRITE_NEW:
                case Mode::READ_WRITE_NEW:
                    if (!empty($this->_data)) {
                        throw new RuntimeException('Memory file is not empty');
                    }

                    $this->_pos = 0;
                    break;
            }
        }

        $this->_mode = Mode::factory($mode);
        $this->_isOpen = true;

        return $this;
    }

    public function isOpen()
    {
        return $this->_isOpen;
    }

    public function isTemp()
    {
        return true;
    }

    public function eof()
    {
        return $this->_pos >= strlen($this->_data);
    }

    public function close()
    {
        return true;
    }


    // Lock
    public function lock($type, $nonBlocking=false)
    {
        if (!$this->_isOpen) {
            throw new RuntimeException(
                'Memory file is not open'
            );
        }

        $this->_isLocked = true;
        return true;
    }

    public function unlock()
    {
        if (!$this->_isOpen) {
            throw new RuntimeException(
                'Memory file is not open'
            );
        }

        $this->_isLocked = false;
        return true;
    }


    // IO
    public function seek($offset, $whence=\SEEK_SET)
    {
        if (!$this->_isOpen) {
            throw new RuntimeException(
                'Memory file is not open'
            );
        }

        switch ($whence) {
            case \SEEK_SET:
                $this->_pos = $offset;
                break;

            case \SEEK_CUR:
                $this->_pos += $offset;
                break;

            case \SEEK_END:
                $this->_pos = strlen($this->_data);
                $this->_pos += $offset;
                break;

            default:
                break;
        }

        return $this;
    }

    public function readFrom($offset, $length)
    {
        $this->_pos = $offset;
        return $this->_readChunk($length);
    }

    public function tell()
    {
        return $this->_pos;
    }

    public function flush()
    {
        $this->_data = '';

        return $this;
    }

    public function truncate($size=0)
    {
        $this->_data = substr($this->_data, 0, $size);
        return $this;
    }


    // Read
    protected function _readChunk($length)
    {
        if (!$this->_isOpen) {
            throw new RuntimeException(
                'Memory file is not open'
            );
        }

        $output = substr($this->_data, $this->_pos, $length);
        $this->_pos += $length;

        return $output;
    }

    protected function _readLine()
    {
        if (!$this->_isOpen) {
            throw new RuntimeException(
                'Memory file is not open'
            );
        }

        $output = '';
        $length = strlen($this->_data);

        while ($this->_pos < $length) {
            if ($this->_data{$this->_pos} == "\n") {
                $this->_pos++;
                return $output;
            }

            $output .= $this->_data{$this->_pos};
            $this->_pos++;
        }

        return $output;
    }


    // Write
    protected function _writeChunk($data, $length)
    {
        if (!$this->_isOpen) {
            throw new RuntimeException(
                'Memory file is not open'
            );
        }

        $cPos = $this->_pos;
        $this->_data .= substr($data, 0, $length);
        $this->_pos = strlen($this->_data);

        return $this->_pos - $cPos;
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
            ->setText($this->_data);

        if ($this->_contentType) {
            $entity->setProperty('*contentType', $this->_contentType);
        }

        if ($this->_error) {
            $entity->setProperty('*error', $this->_error);
        }
    }
}

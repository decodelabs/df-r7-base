<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flow\mail;

use df;
use df\core;
use df\flow;

use DecodeLabs\Atlas;
use DecodeLabs\Atlas\File;

class Attachment implements IAttachment
{
    protected $_file;
    protected $_fileName;
    protected $_contentId;

    public function __construct(File $file, string $contentId=null)
    {
        $this->setFile($file);

        if ($contentId !== null) {
            $this->_contentId = $contentId;
        }
    }

    public function setFile(File $file)
    {
        $this->_file = $file;
        return $this;
    }

    public function getFile(): File
    {
        return $this->_file;
    }


    public function setFileName(string $fileName=null)
    {
        $this->_fileName = $fileName;
        return $this;
    }

    public function getFileName()
    {
        if ($this->_fileName !== null) {
            return $this->_fileName;
        }

        return $this->_file->getName();
    }


    public function getContentId(): string
    {
        if ($this->_contentId === null) {
            $this->_contentId = uniqid('cid');
        }

        return $this->_contentId;
    }

    public function getContentType(): string
    {
        return Atlas::$mime->detect($this->_file->getPath());
    }
}

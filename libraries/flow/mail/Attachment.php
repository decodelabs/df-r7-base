<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flow\mail;

use df;
use df\core;
use df\flow;

class Attachment implements IAttachment {

    protected $_file;
    protected $_fileName;
    protected $_contentId;

    public function __construct(core\fs\IFile $file, string $contentId=null) {
        $this->setFile($file);

        if($contentId !== null) {
            $this->_contentId = $contentId;
        }
    }

    public function setFile(core\fs\IFile $file) {
        $this->_file = $file;
        return $this;
    }

    public function getFile(): core\fs\IFile {
        return $this->_file;
    }


    public function setFileName(string $fileName=null) {
        $this->_fileName = $fileName;
        return $this;
    }

    public function getFileName() {
        if($this->_fileName !== null) {
            return $this->_fileName;
        }

        if(!$this->_file instanceof core\fs\MemoryFile) {
            $output = $this->_file->getName();
        } else {
            $output = $this->_fileName = $this->getContentId();
        }

        return $output;
    }


    public function getContentId(): string {
        if($this->_contentId === null) {
            $this->_contentId = uniqid('cid');
        }

        return $this->_contentId;
    }

    public function getContentType(): string {
        return $this->_file->getContentType();
    }
}
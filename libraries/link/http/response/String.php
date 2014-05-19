<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\http\response;

use df;
use df\core;
use df\link;

class String extends Base implements link\http\IStringResponse {
    
    protected $_content;
    
    public function __construct($content=null, $contentType=null) {
        if($contentType === null) {
            $contentType = 'text/plain';
        }
        
        $this->setContent($content, $contentType);
    }
    
    
    public function setLastModified(core\time\IDate $date) {
        $headers = $this->getHeaders();
        $headers->set('last-modified', $date);
        
        $headers->set('etag', md5(
            $date->toTimeZone('GMT')->format('D, d M Y H:i:s \G\M\T')
        ));
        
        return $this;
    }
    
    public function setContent($content, $contentType=null) {
        if($content instanceof core\io\IChannel) {
            $this->setContentFileStream($content);
        } else if(!$this->_content) {
            $this->_content = new core\io\channel\Memory($content, $contentType);
        } else {
            $this->_content->write($content);
        }

        if($contentType !== null) {
            $this->setContentType($contentType);
        }
        
        return $this;
    }
    
    public function getContent() {
        return $this->_content->getContents();
    }

    public function setContentType($type) {
        parent::setContentType($type);

        if($this->_content instanceof core\io\channel\Memory) {
            $this->_content->setContentType($type);
        }

        return $this;
    }

    public function setContentFileStream(core\io\IChannel $content) {
        $this->_content = $content;
        return $this;
    }

    public function getContentFileStream() {
        return $this->_content;
    }
}
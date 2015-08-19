<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\http\response;

use df;
use df\core;
use df\link;

class Stream extends Base implements link\http\IAdaptiveStreamResponse {
    
    protected $_content;
    
    public function __construct($content=null, $contentType=null, link\http\IResponseHeaderCollection $headers=null) {
        parent::__construct($headers);
        
        if($contentType === null && (!$headers || $headers->has('content-type'))) {
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
            $this->_content = new core\fs\MemoryFile($content, $contentType);
        } else {
            $this->_content->write($content);
        }

        if($contentType !== null) {
            $this->setContentType($contentType);
        }
        
        return $this;
    }
    
    public function getContent() {
        if($this->_content instanceof core\fs\IFile) {
            return $this->_content->getContents();
        } else {
            return $this->_content->read();
        }
    }

    public function setContentType($type) {
        parent::setContentType($type);

        if($this->_content instanceof core\fs\MemoryFile) {
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

    public function transferContentFileStream(core\io\IChannel $content) {
        $this->_content->seek(0);

        if($this->headers->has('content-length')) {
            $length = $this->headers->get('content-length');
            $chunkLength = 1024;

            while($length > 0) {
                $currentLength = $chunkLength;

                if($currentLength > $length) {
                    $currentLength = $length;
                }

                $chunk = $content->readChunk($currentLength);

                // TODO: check for mismatch length

                $this->_content->write($chunk);
                $length -= $currentLength;
            }
        } else {
            $content->writeTo($this->_content);
        }

        return $this;
    }
}
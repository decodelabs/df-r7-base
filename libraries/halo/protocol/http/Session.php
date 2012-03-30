<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\protocol\http;

use df;
use df\core;
use df\halo;

class Session extends halo\server\Session {
    
    protected $_contentLength = null;
    protected $_request;
    protected $_response;
    protected $_errorCode;
    protected $_fileStream;
    
    public function setRequest(halo\protocol\http\IRequest $request) {
        $this->_request = $request;
        return $this;
    }
    
    public function getRequest() {
        return $this->_request;
    }
    
    public function getContentLength() {
        if($this->_contentLength === null) {
            if(!$this->_request) {
                return 0;
            }
            
            $headers = $this->_request->getHeaders();
            
            if($headers->has('content-length')) {
                $this->_contentLength = (int)$headers->get('content-length');
            } else {
                $this->_contentLength = 0;
            }
        }
        
        return $this->_contentLength;
    }
    
    
    public function setResponse(halo\protocol\http\IResponse $response) {
        $this->_response = $response;
        return $this;
    }
    
    public function getResponse() {
        return $this->_response;
    }
    
    public function setErrorCode($code) {
        $this->_errorCode = $code;
        return $this;
    }
    
    public function getErrorCode() {
        return $this->_errorCode;
    }
    
    public function hasErrorCode() {
        return $this->_errorCode !== null;
    }
    
    
    public function setFileStream(core\io\file\IPointer $file) {
        $this->_fileStream = $file;
        return $this;
    }
    
    public function getFileStream() {
        return $this->_fileStream;
    }
}
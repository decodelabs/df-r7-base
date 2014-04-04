<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\http;

use df;
use df\core;
use df\link;

class PeerSession implements link\ISession {
    
    use link\TPeer_Session;
    use link\TPeer_RequestResponseSession;
    use link\TPeer_FileStreamSession;
    use link\TPeer_ErrorCodeSession;
    use link\TPeer_CallbackSession;
    
    protected $_contentLength = null;
    
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
}
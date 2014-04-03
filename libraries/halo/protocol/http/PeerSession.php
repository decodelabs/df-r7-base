<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\protocol\http;

use df;
use df\core;
use df\halo;

class PeerSession implements halo\net\ISession {
    
    use halo\net\TPeer_Session;
    use halo\net\TPeer_RequestResponseSession;
    use halo\net\TPeer_FileStreamSession;
    use halo\net\TPeer_ErrorCodeSession;
    use halo\net\TPeer_CallbackSession;
    
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
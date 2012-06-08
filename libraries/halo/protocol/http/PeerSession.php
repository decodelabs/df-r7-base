<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\protocol\http;

use df;
use df\core;
use df\halo;

class PeerSession implements halo\peer\ISession {
    
    use halo\peer\TPeer_Session;
    use halo\peer\TPeer_RequestResponseSession;
    use halo\peer\TPeer_FileStreamSession;
    use halo\peer\TPeer_ErrorCodeSession;
    use halo\peer\TPeer_CallbackSession;
    
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
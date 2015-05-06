<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\http\peer;

use df;
use df\core;
use df\link;

class Session implements ISession {
    
    use link\peer\TPeer_Session;
    use link\peer\TPeer_RequestResponseSession;
    use link\peer\TPeer_FileStreamSession;
    use link\peer\TPeer_ErrorCodeSession;
    use link\peer\TPeer_CallbackSession;
    
    protected $_contentLength = null;
    protected $_headerCallback;
    
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


    public function setHeaderCallback($callback) {
        if($callback !== null) {
            $callback = core\lang\Callback::factory($callback);
        }

        $this->_headerCallback = $callback;
        return $this;
    }
    
    public function getHeaderCallback() {
        return $this->_headerCallback;
    }
}
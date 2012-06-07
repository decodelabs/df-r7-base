<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\protocol\http;

use df;
use df\core;
use df\halo;

class Client extends halo\peer\Client {
    
    protected $_requests = array();
    
    public function __construct($requests) {
        if(!is_array($requests)) {
            $requests = func_get_args();
        }
        
        foreach($requests as $request) {
            $this->_requests[] = halo\protocol\http\request\Base::factory($request);
        }
    }
    
    protected function _createSockets() {
        foreach($this->_requests as $request) {
            $session = $this->_registerSession(
                halo\socket\Client::factory('tcp://'.$request->getSocketAddress())
            );
            
            $session->setRequest($request);
        }
    }
    
    protected function _createSession(halo\socket\ISocket $socket) {
        return new Session($socket);
    }
    
    
    protected function _handleReadBuffer(halo\peer\ISession $session, $data) {
        $response = halo\protocol\http\response\Base::fromString($data);
        core\dump($response);
    }
    
    protected function _handleWriteBuffer(halo\peer\ISession $session) {
        $request = $session->getRequest();
        $session->writeBuffer = $request->getHeaderString()."\r\n";
        
        // TODO: Deal with post data as a write stream
        
        // Close writing, go to read mode
        return halo\peer\IIoState::OPEN_READ;
    }
}

<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\protocol\http;

use df;
use df\core;
use df\halo;

class AsyncClient implements IAsyncClient {
    
    use halo\peer\TPeer_Client;
    
    const PROTOCOL_DISPOSITION = halo\peer\IClient::CLIENT_FIRST;
    
    public function __construct() {
        if(($num = func_num_args()) > 1) {
            $args = func_get_args();
            
            if($num == 2) {
                $requests = [$args];
            } else if($num == 1) {
                $requests = $args[0];
            }
            
            if(is_array($requests)) {
                foreach($requests as $set) {
                    $request = array_shift($set);
                    $callback = array_shift($set);
                    
                    if(!is_callable($callback)) {
                        throw new RuntimeException(
                            'Async request callback is not callable'
                        );
                    }
                    
                    $this->addRequest($request, $callback);
                }
                
                $this->run();
            }
        }
    }
    
    public function addRequest($request, Callable $callback) {
        $request = halo\protocol\http\request\Base::factory($request);
        
        $session = new PeerSession(
            halo\socket\Client::factory('tcp://'.$request->getSocketAddress())
        );
        
        $session->setRequest($request);
        $session->setCallback($callback);
        
        $this->_registerSession($session);
        
        if($this->isRunning()) {
            $this->_dispatchSession($session);
        }
        
        return $this;
    }
    
    protected function _createInitialSessions() {}
    
    protected function _handleWriteBuffer(halo\peer\ISession $session) {
        if(!$fileStream = $session->getFileStream()) {
            $request = $session->getRequest();
            $session->writeBuffer = $request->getHeaderString();
            
            if(1/*!$request->hasFileStream()*/) {
                $session->writeBuffer .= $request->getBodyData();
                
                //core\dump(str_replace(array("\r", "\n"), array('\r', '\n'."\n"), $session->writeBuffer), $request);
                //$session->writeBuffer .= "\r\n";
                return halo\peer\IIoState::OPEN_READ;
            }
            
            $session->setFileStream($fileStream = $request->getFileStream());
        }
        
        $session->writeBuffer .= $fileStream->read(8192);
        
        return $fileStream->eof() ? 
            halo\peer\IIoState::OPEN_READ : 
            halo\peer\IIoState::BUFFER;
    }
    
    protected function _handleReadBuffer(halo\peer\ISession $session, $data) {
        $response = halo\protocol\http\response\Base::fromString($data);
        $session->setResponse($response);
        
        return halo\peer\IIoState::END;
    }
    
    protected function _onSessionEnd(halo\peer\ISession $session) {
        if(!$response = $session->getResponse()) {
            core\stub('Generate a default connection error response');
        }
        
        if($callback = $session->getCallback()) {
            $callback($response, $this, $session);
        }
    }
}

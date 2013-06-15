<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\protocol\http;

use df;
use df\core;
use df\halo;

class Client implements IClient {
    
    use halo\event\TDispatcherProvider;
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

    public function sendRequest($request) {
        $output = null;

        $this->addRequest($request, function($response) use(&$output) {
            $output = $response;
        });

        if(!$this->isRunning()) {
            $this->run();
        }

        return $output;
    }

    public function get($url, $headers=null, $cookies=null) {
        $request = $this->_prepareRequest($url, 'get', $headers, $cookies);
        return $this->sendRequest($request);
    }

    public function post($url, $data, $headers=null, $cookies=null) {
        $request = $this->_prepareRequest($url, 'post', $headers, $cookies);

        if(is_string($data)) {
            $request->setBodyData($data);
        } else {
            $request->getPostData()->import($data);
        }

        return $this->sendRequest($request);
    }

    protected function _prepareRequest($url, $method, $headers=null, $cookies=null) {
        $request = halo\protocol\http\request\Base::factory($url);
        $request->setMethod($method);

        if($headers) {
            $request->getHeaders()->import(halo\protocol\http\request\HeaderCollection::factory($headers));
        }

        if($cookies) {
            $request->getCookieData()->import($cookies);
        }

        return $request;
    }
    
    protected function _createInitialSessions() {}
    
    protected function _handleWriteBuffer(halo\peer\ISession $session) {
        if(!$fileStream = $session->getFileStream()) {
            $request = $session->getRequest();
            $session->writeBuffer = $request->getHeaderString();
            $session->writeBuffer .= "\r\n\r\n";
            
            if(1/*!$request->hasFileStream()*/) {
                $session->writeBuffer .= $request->getBodyData();

                //core\dump(str_replace(array("\r", "\n"), array('\r', '\n'."\n"), $session->writeBuffer), $request);
                return halo\peer\IIoState::OPEN_READ;
            }
            
            $session->setFileStream($fileStream = $request->getFileStream());
        }
        
        $session->writeBuffer .= $fileStream->readChunk(8192);
        
        return $fileStream->eof() ? 
            halo\peer\IIoState::OPEN_READ : 
            halo\peer\IIoState::BUFFER;
    }
    
    protected function _handleReadBuffer(halo\peer\ISession $session, $data) {
        if(!$response = $session->getResponse()) {
            if(false === strpos($session->readBuffer, "\r\n\r\n")) {
                return;
            }

            $response = halo\protocol\http\response\Base::fromHeaderString($data, $session->readBuffer);
            $headers = $response->getHeaders();
            $session->setResponse($response);
            $session->setStore('isChunked', strtolower($headers->get('transfer-encoding')) == 'chunked');

            if($headers->has('content-length')) {
                $session->setStore('length', (int)$headers->get('content-length'));
            }
        }

        $isChunked = $session->getStore('isChunked', false);
        $length = $session->getStore('length', 0);
        $content = $response->getContent();

        if($isChunked) {
            if(!$length) {
                if(!preg_match("/^([\da-fA-F]+)[^\r\n]*\r\n/sm", $session->readBuffer = ltrim($session->readBuffer), $matches)) {
                    throw new halo\protocol\http\UnexpectedValueException('The body does not appear to be chunked properly');
                }

                $session->setStore('length', $length = hexdec(trim($matches[1])));
                $parts = explode("\r\n", $session->readBuffer, 2);
                $session->readBuffer = array_pop($parts);

                if(!$length) {
                    return halo\peer\IIoState::END;
                }
            }
        } else if(!$length) {
            core\stub('No HTTP content length detected');
        }

        if(strlen($session->readBuffer) >= $length) {
            $content .= substr($session->readBuffer, 0, $length);
            $session->readBuffer = substr($session->readBuffer, $length);

            $response->setContent($content);

            if($isChunked) {
                if(trim($session->readBuffer) == '0') {
                    return halo\peer\IIoState::END;
                }

                $session->setStore('length', 0);
            } else {
                return halo\peer\IIoState::END;
            }
        }
    }
    
    protected function _onSessionEnd(halo\peer\ISession $session) {
        if(!$response = $session->getResponse()) {
            core\dump($this->_test);
            core\stub('Generate a default connection error response');
        }
        
        if($callback = $session->getCallback()) {
            $callback($response, $this, $session);
        }
    }
}

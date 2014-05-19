<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\http;

use df;
use df\core;
use df\link;
use df\halo;

class Client implements IClient, core\IDumpable {
    
    use link\TPeer_Client;
    use halo\event\TDispatcherProvider;
    
    const PROTOCOL_DISPOSITION = link\IClient::CLIENT_FIRST;
    const USER_AGENT = 'DF link HTTP client';

    protected $_followRedirects = true;
    protected $_maxRedirects = 15;

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

    public function shouldFollowRedirects($flag=null) {
        if($flag !== null) {
            $this->_followRedirects = (bool)$flag;
            return $this;
        }

        return $this->_followRedirects;
    }
    
    public function addRequest($request, Callable $callback) {
        $request = link\http\request\Base::factory($request);
        $headers = $request->getHeaders();

        if(!$headers->has('user-agent')) {
            $headers->set('user-agent', static::USER_AGENT);
        }
        
        $scheme = $request->getUrl()->isSecure() ? 'ssl' : 'tcp';

        $session = new PeerSession(
            link\socket\Client::factory($scheme.'://'.$request->getSocketAddress())
                ->setReceiveTimeout(100)
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

    public function getFile($url, $file, $headers=null, $cookies=null) {
        $request = $this->_prepareRequest($url, 'get', $headers, $cookies);
        $request->setResponseFilePath($file);
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
        $request = link\http\request\Base::factory($url);
        $request->setMethod($method);

        if($headers) {
            $request->getHeaders()->import(link\http\request\HeaderCollection::factory($headers));
        }

        if($cookies) {
            $request->getCookieData()->import($cookies);
        }

        return $request;
    }
    
    protected function _createInitialSessions() {}
    
    protected function _handleWriteBuffer(link\ISession $session) {
        if(!$fileStream = $session->getFileStream()) {
            $request = $session->getRequest();
            $session->writeBuffer = $request->getHeaderString();
            $session->writeBuffer .= "\r\n\r\n";

            $body = $request->getBodyData();

            if($body instanceof core\io\IFilePointer) {
                $fileStream = $body->open();
                $session->setFileStream($fileStream);
            } else {
                $session->writeBuffer .= (string)$body;
                return link\IIoState::OPEN_READ;
            }
        }

        if(!$session->hasStore('isChunked')) {
            $session->setStore('isChunked', $isChunked = ($request->getHeaders()->get('Transfer-Encoding') == 'chunked'));
        } else {
            $isChunked = $session->getStore('isChunked');
        }
        
        $chunk = $fileStream->readChunk(8192);
        $eof = $fileStream->eof();

        if($isChunked) {
            $session->writeBuffer .= dechex(strlen($chunk))."\r\n";
            $session->writeBuffer .= $chunk."\r\n";

            if($eof) {
                $session->writeBuffer .= "0\r\n\r\n";
            }
        } else {
            $session->writeBuffer .= $chunk;
        }

        return $eof ? 
            link\IIoState::OPEN_READ : 
            link\IIoState::BUFFER;
    }

    protected function _handleReadBuffer(link\ISession $session, $data) {
        $request = $session->getRequest();

        if(!$response = $session->getResponse()) {
            if(false === strpos($session->readBuffer, "\r\n\r\n")) {
                return;
            }

            $response = link\http\response\Base::fromHeaderString($data, $session->readBuffer);
            $headers = $response->getHeaders();
            $session->setResponse($response);
            $session->setStore('isChunked', strtolower($headers->get('transfer-encoding')) == 'chunked');

            if($headers->has('content-length')) {
                $session->setStore('length', (int)$headers->get('content-length'));
            }

            if($path = $request->getResponseFilePath()) {
                if($path instanceof core\io\IChannel) {
                    $response->setContentFileStream($path);
                } else {
                    core\io\Util::ensureDirExists(dirname($path));
                    $response->setContentFileStream(new core\io\channel\File($path, core\io\IMode::READ_WRITE_TRUNCATE));
                }
            }

            $session->setFileStream($response->getContentFileStream());
        }


        if($request->getMethod() == 'HEAD') {
            return link\IIoState::END;
        }

        $isChunked = $session->getStore('isChunked', false);
        $length = $session->getStore('length', 0);

        $fileStream = $session->getFileStream();

        if($isChunked) {
            if(!$length) {
                if(!preg_match("/^([\da-fA-F]+)[^\r\n]*\r\n/sm", $session->readBuffer = ltrim($session->readBuffer), $matches)) {
                    throw new link\http\UnexpectedValueException('The body does not appear to be chunked properly');
                }

                $session->setStore('length', $length = hexdec(trim($matches[1])));
                $parts = explode("\r\n", $session->readBuffer, 2);
                $session->readBuffer = array_pop($parts);

                if(!$length) {
                    return link\IIoState::END;
                }
            }
        }

        if(strlen($session->readBuffer) >= $length) {
            $fileStream->writeChunk(substr($session->readBuffer, 0, $length));
            $session->readBuffer = substr($session->readBuffer, $length);

            if($isChunked) {
                if(trim($session->readBuffer) == '0') {
                    return link\IIoState::END;
                }

                $session->setStore('length', 0);
            } else {
                return link\IIoState::END;
            }
        }
    }

    protected function _onSessionEnd(link\ISession $session) {
        if(!$response = $session->getResponse()) {
            core\stub('Generate a default connection error response', $session);
        }

        $callback = $session->getCallback();

        if($response->isRedirect() && $this->_followRedirects) {
            $redirCount = $session->getStore('redirects', 0);
            $request = clone $session->getRequest();

            if($redirCount >= $this->_maxRedirects) {
                throw new RuntimeException(
                    'Too many redirects - '.$request->getUrl()
                );
            }

            $location = $response->getHeaders()->get('Location');

            if(!$location) {
                core\stub('No redirect location specified', $response, $request);
            }

            $request->setUrl($location);
            $this->addRequest($request, $callback);
            $session->setStore('redirects', ++$redirCount);
            return;
        }
        
        if($callback) {
            $callback($response, $this, $session);
        }
    }


// Dump
    public function getDumpProperties() {
        return [
            'isRunning' => $this->isRunning(),
            'chunkSize' => $this->_readChunkSize.' / '.$this->_writeChunkSize,
            'sessions' => $this->_sessionCount,
            'dispatcher' => $this->_dispatcher
        ];
    }
}

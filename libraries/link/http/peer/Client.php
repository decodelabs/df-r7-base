<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\http\peer;

use df;
use df\core;
use df\link;
use df\halo;

class Client implements IClient, core\IDumpable {
    
    use link\peer\TPeer_Client;
    use halo\event\TDispatcherProvider;
    
    const PROTOCOL_DISPOSITION = IClient::CLIENT_FIRST;
    const USER_AGENT = 'DF link HTTP client';

    protected $_followRedirects = true;
    protected $_maxRedirects = 15;
    protected $_maxRetries = 20;
    protected $_retries = 0;
    protected $_saveIfNotOk = false;

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

    public function setMaxRetries($retries) {
        $this->_maxRetries = (int)$retries;
        return $this;
    }

    public function getMaxRetries() {
        return $tis->_maxRetries;
    }
    
    public function shouldSaveIfNotOk($flag=null) {
        if($flag !== null) {
            $this->_saveIfNotOk = (bool)$flag;
            return $this;
        }

        return $this->_saveIfNotOk;
    }

    public function addRequest($request, $callback, $headerCallback=null) {
        $callback = core\lang\Callback::factory($callback);
        $request = link\http\request\Base::factory($request);
        $headers = $request->getHeaders();
        $request->options->sanitize();

        if(!$headers->has('user-agent')) {
            $headers->set('user-agent', static::USER_AGENT);
        }
        
        if($request->isSecure()) {
            $scheme = $request->options->getSecureTransport();
        } else {
            $scheme = 'tcp';
        }

        $session = new Session(
            link\socket\Client::factory($scheme.'://'.$request->getSocketAddress())
                ->setReceiveTimeout(100)
        );
        
        $session->setRequest($request);
        $session->setCallback($callback);
        $session->setHeaderCallback($headerCallback);
        
        $this->_registerSession($session);
        
        if($this->isRunning()) {
            $this->_dispatchSession($session);
        }
        
        return $this;
    }

    public function sendRequest($request, $headerCallback=null) {
        $output = null;

        $this->addRequest($request, function($response) use(&$output) {
            $output = $response;
        }, $headerCallback);

        if(!$this->isRunning()) {
            $this->run();
        }

        return $output;
    }

    public function get($url, $headers=null, $cookies=null) {
        $request = $this->prepareRequest($url, 'get', $headers, $cookies);
        return $this->sendRequest($request);
    }

    public function getFile($url, $file, $headers=null, $cookies=null) {
        $request = $this->prepareRequest($url, 'get', $headers, $cookies);
        $request->options->setDownloadFilePath($file);
        return $this->sendRequest($request);
    }

    public function post($url, $data, $headers=null, $cookies=null) {
        $request = $this->prepareRequest($url, 'post', $headers, $cookies);

        if(is_string($data)) {
            $request->setBodyData($data);
        } else {
            $request->getPostData()->import($data);
        }

        return $this->sendRequest($request);
    }

    public function prepareRequest($url, $method='get', $headers=null, $cookies=null) {
        $request = link\http\request\Base::factory($url);
        $request->setMethod($method);

        if($headers) {
            $request->headers->import(link\http\request\HeaderCollection::factory($headers));
        }

        if($cookies) {
            $request->cookies->import($cookies);
        }

        return $request;
    }
    
    protected function _createInitialSessions() {}
    
    protected function _handleWriteBuffer(link\peer\ISession $session) {
        if(!$fileStream = $session->getWriteFileStream()) {
            $request = $session->getRequest();
            $session->writeBuffer = $request->getHeaderString();
            $session->writeBuffer .= "\r\n\r\n";

            $body = $request->getBodyData();

            if($body instanceof core\fs\IFile) {
                $session->setWriteFileStream($fileStream = $body->open());
            } else {
                $session->setWriteFileStream($fileStream = new core\fs\MemoryFile((string)$body, $request->getHeaders()->get('Content-Type')));
            }

            $fileStream->seek(0);
            $session->setStore('isChunked', $request->getHeaders()->get('Transfer-Encoding') == 'chunked');

            if($request->getMethod() == 'put') {
                $session->setStore('writeListen', true);
                return link\peer\IIoState::WRITE_LISTEN;
            }
        }

        $isChunked = $session->getStore('isChunked');

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

        if($eof) {
            return link\peer\IIoState::OPEN_READ;
        } else if($session->getStore('writeListen')) {
            return link\peer\IIoState::WRITE_LISTEN;
        } else {
            return link\peer\IIoState::WRITE;
        }
    }

    protected function _handleReadBuffer(link\peer\ISession $session, $data) {
        $request = $session->getRequest();

        if(!$response = $session->getResponse()) {
            if(!preg_match('|(?:\r?\n){2}|m', $session->readBuffer)) {
                return;
            }

            $response = link\http\response\Base::fromHeaderString($data, $session->readBuffer);
            $headers = $response->getHeaders();
            $session->setResponse($response);

            if($callback = $session->getHeaderCallback()) {
                if(false === $callback->invoke($headers)) {
                    return link\peer\IIoState::END;
                }
            }

            $session->setStore('isChunked', strtolower($headers->get('transfer-encoding')) == 'chunked');

            if($headers->has('content-length')) {
                $session->setStore('length', (int)$headers->get('content-length'));
            }

            if(($path = $request->options->getDownloadFilePath()) 
            && ($this->_saveIfNotOk || $headers->hasStatusCode(200))) {
                if($path instanceof core\io\IChannel) {
                    $response->setContentFileStream($path);
                } else {
                    core\fs\Dir::create(dirname($path));
                    $response->setContentFileStream(new core\fs\File($path, core\fs\Mode::READ_WRITE_TRUNCATE));
                }
            }

            $session->setReadFileStream($response->getContentFileStream());
        }

        if($request->getMethod() == 'head') {
            return link\peer\IIoState::END;
        }

        $isChunked = $session->getStore('isChunked', false);
        $length = $session->getStore('length', 0);

        $fileStream = $session->getReadFileStream();

        if($isChunked) {
            while(!empty(ltrim($session->readBuffer))) {
                if(!$length) {
                    if(!preg_match("/^([\da-fA-F]+)[^\r\n]*\r\n/sm", $session->readBuffer = ltrim($session->readBuffer), $matches)) {
                        throw new link\http\UnexpectedValueException('The body does not appear to be chunked properly');
                    }

                    $session->setStore('length', $length = hexdec(trim($matches[1])));
                    $parts = explode("\r\n", $session->readBuffer, 2);
                    $session->readBuffer = array_pop($parts);

                    if(!$length) {
                        return link\peer\IIoState::END;
                    }
                }

                if(strlen($session->readBuffer) >= $length) {
                    $fileStream->writeChunk(substr($session->readBuffer, 0, $length));
                    $session->readBuffer = substr($session->readBuffer, $length);

                    if(trim($session->readBuffer) == '0') {
                        $fileStream->close();
                        return link\peer\IIoState::END;
                    }

                    $session->setStore('length', $length = 0);
                    continue;
                }

                break;
            }
        } else {
            $length -= strlen($session->readBuffer);
            $session->setStore('length', $length);
            $fileStream->writeChunk($session->readBuffer);
            $session->readBuffer = '';

            if($length <= 0) {
                $fileStream->close();
                return link\peer\IIoState::END;
            }
        }
    }

    protected function _onSessionEnd(link\peer\ISession $session) {
        $callback = $session->getCallback();

        if(!$response = $session->getResponse()) {
            $request = clone $session->getRequest();
            $this->_retries++;

            if($this->_retries > $this->_maxRetries) {
                throw new RuntimeException('No response was read from http connection after '.$this->_retries.' attempt(s)');
            }
            
            $this->addRequest($request, $callback);

            return;
        }

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
                throw new RuntimeException('No redirect location specified');
            }

            $request->setUrl($location);
            $this->addRequest($request, $callback, $session->getHeaderCallback());
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
            'dispatcher' => $this->events
        ];
    }
}

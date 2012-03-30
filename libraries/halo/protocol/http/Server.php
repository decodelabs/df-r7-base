<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\protocol\http;

use df;
use df\core;
use df\halo;

class Server extends halo\server\Base {
    
    const PROTOCOL_DISPOSITION = self::PEER_FIRST;
    
    protected $_maxRequestLength = 1048576;
    
    protected function _createMasterSockets() {
        $this->_registerMasterSocket(
            halo\socket\Server::factory('tcp://127.0.0.1:2000')
        );
    }
    
    protected function _createSession(halo\socket\IServerPeerSocket $socket) {
        return new Session($socket);
    }
    
    protected function _handleReadBuffer(halo\server\ISession $session, $data) {
        if(!$request = $session->getRequest()) {
            if(false !== ($pos = strpos($session->readBuffer, "\n\n"))) {
                $headers = substr($session->readBuffer, 0, $pos);
                $content = substr($session->readBuffer, $pos + 2);
                
            } else if(false !== ($pos = strpos($session->readBuffer, "\r\n\r\n"))) {
                $headers = substr($session->readBuffer, 0, $pos);
                $content = substr($session->readBuffer, $pos + 4);
                
            } else if(strlen($session->readBuffer) > $this->_maxRequestLength) {
                // Request too large
                $session->setErrorCode(413);
                return self::WRITE;
            } else {
                return self::BUFFER;
            }
            
            // TODO: put content into a temp file
            $session->readBuffer = $content;
            
            try {
                $session->setRequest($request = halo\protocol\http\request\Base::fromString($headers));
            } catch(UnexpectedValueException $e) {
                // Bad request
                $session->setErrorCode(400);
                return self::WRITE;
            }
            
            if($contentLength = $session->getContentLength()) {
                if($contentLength > $this->_maxRequestLength) {
                    // Request too large
                    $session->setErrorCode(413);
                    return self::WRITE;
                }
            }
            
            $request->setIp($session->getSocket()->getAddress()->getIp());
        }
        
        if($contentLength = $session->getContentLength()) {
            if(strlen($session->readBuffer) < $contentLength) {
                return self::BUFFER;
            } else {
                // TODO: set post data
                core\stub('set post data');
            }
        }
        
        
        echo $request->getMethod().' '.$request->getUrl()."\n";
        $session->readBuffer = '';
        
        return self::WRITE;
    }
    
    protected function _handleWriteBuffer(halo\server\ISession $session) {
        if(!$response = $session->getResponse()) {
            if(!$request = $session->getRequest()) {
                // Bad request
                $session->setErrorCode(400);
            }
            
            $debugContext = df\Launchpad::setDebugContext(new core\debug\Context());
            
            if($session->hasErrorCode()) {
                $response = $this->_createErrorResponse($session);
            } else {
                $response = $this->_createResponse($session);
            }
            
            df\Launchpad::setDebugContext($debugContext);
            gc_collect_cycles();
            
            $session->setResponse($response);
            
            if($response instanceof IFileResponse) {
                $session->setFileStream($response->getContentFileStream());
            }
            
            $session->writeBuffer = $response->getHeaderString();
        }
        
        if($fileStream = $session->getFileStream()) {
            $session->writeBuffer .= $fileStream->read(8192);
            return $fileStream->eof() ? self::END : self::BUFFER;
        } else {
            $session->writeBuffer .= $response->getContent();
            return self::END;
        }
    }
    
    protected function _createResponse(halo\server\ISession $session) {
        try {
            core\debug()->setTransport(new core\debug\transport\HttpCapture());
            $currentApp = df\Launchpad::getActiveApplication();
            
            $app = core\application\Base::factory(
                'Http', 
                $currentApp->getApplicationPath(), 
                $currentApp->getEnvironmentId()
            );
            
            return $app->run($session->getRequest());
        } catch(DebugPayload $e) {
            return $e->response;
        } catch(\Exception $e) {
            return new halo\protocol\http\response\String($e->__toString());
        }
    }
    
    protected function _createErrorResponse(halo\server\ISession $session) {
        $code = $session->getErrorCode();
        
        $response = new halo\protocol\http\response\String(
            halo\protocol\http\response\HeaderCollection::statusCodeToString($code), 
            'text/plain'
        );
        
        $response->getHeaders()->setStatusCode($code);
        
        return $response;
    }
}
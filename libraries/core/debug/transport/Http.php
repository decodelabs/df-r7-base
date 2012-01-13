<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug\transport;

use df;
use df\core;
use df\halo;

class Http implements core\debug\ITransport {
    
    protected $_isExecuting = false;
    protected $_responseAugmentor;
    
    public function setResponseAugmentor(halo\protocol\http\IResponseAugmentor $augmentor=null) {
        $this->_responseAugmentor = $augmentor;
        return $this;
    }
    
    public function execute(core\debug\IContext $context) {
        if($this->_isExecuting) {
            throw new \Exception(
                'Debug transport is already executing'
            );
        }
        
        $this->_isExecuting = true;
        
        require_once dirname(__DIR__).'/renderer/Html.php';
        $renderer = new core\debug\renderer\Html($context);
        
        if(!headers_sent()) {
            if(strncasecmp(PHP_SAPI, 'cgi', 3)) {
                header('HTTP/1.1 501');
            } else {
                header('Status: 501');
            }

            header('Content-Type: text/html; charset=UTF-8');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            
            try {
                if($this->_responseAugmentor) {
                    $cookies = $this->_responseAugmentor->getCookieCollectionForCurrentRequest();
                    
                    foreach($cookies->toArray() as $cookie) {
                        header('Set-Cookie: '.$cookie->toString());
                    }
                    
                    foreach($cookies->getRemoved() as $cookie) {
                        header('Set-Cookie: '.$cookie->toInvalidateString());
                    }
                }
            } catch(\Exception $e) {}
        }
        
        echo $renderer->render();
        df\Launchpad::shutdown();
    }
}

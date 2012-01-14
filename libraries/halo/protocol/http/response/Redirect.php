<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\protocol\http\response;

use df;
use df\core;
use df\halo;

class Redirect extends Base implements halo\protocol\http\IRedirectResponse {
    
    protected $_url;
    
    public function __construct($url) {
        $this->getHeaders()->setStatusCode(302);
        $this->setUrl($url);
        $this->setContentType('text/html');
    }
    
    public function setUrl($url) {
        $url = halo\protocol\http\Url::factory($url);
        
        if(!$url->isAbsolute()) {
            throw new halo\protocol\http\InvalidArgumentException('Redirect URL must include host');
        }
        
        $this->_url = $url;
        $this->_headers->set('location', $url);
        
        return $this;
    }
    
    public function getUrl() {
        return $this->_url;
    }
    
    public function isPermanent($flag=null) {
        if($flag !== null) {
            if($flag) {
                $this->_headers->setStatusCode(301);
            } else {
                $this->_headers->setStatusCode(302);
            }
            
            return $this;
        }
        
        return $this->_headers->getStatusCode() == 301;
    }
    
    public function isTemporary($flag=null) {
        if($flag !== null) {
            if($flag) {
                $this->_headers->setStatusCode(307);
            } else {
                $this->_headers->setStatusCode(302);
            }
            
            return $this;
        }
        
        return $this->_headers->getStatusCode() == 307;
    }
    
    public function isAlternativeContent($flag=null) {
        if($flag !== null) {
            if($flag) {
                $this->_headers->setStatusCode(303);
            } else {
                $this->_headers->setStatusCode(302);
            }
            
            return $this;
        }
        
        return $this->_headers->getStatusCode() == 303;
    }
    
    public function getContent() {
        $url = $this->_url->toString(false);
        
        return '<html><head><title>Redirecting...</title></head><body>'.
               '<p>Redirecting to <a href="'.$url.'">'.$url.'</a></p>'.
               '</body></html>';
    }
}
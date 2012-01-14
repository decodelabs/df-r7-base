<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\protocol\http\response;

use df;
use df\core;
use df\halo;

class Augmentor implements halo\protocol\http\IResponseAugmentor {
    
    protected $_globalCookies;
    protected $_currentCookies;
    
    public function __construct() {
        $this->_globalCookies = new halo\protocol\http\response\CookieCollection();
        $this->resetCurrent();
    }
    
    public function resetCurrent() {
        $this->_currentCookies = clone $this->_globalCookies;
        return $this;
    }
    
    public function apply(halo\protocol\http\IResponse $response) {
        $response->getCookies()->import($this->_currentCookies);
        return $this;
    }
    
    public function newCookie($name, $value) {
        return new Cookie($name, $value);
    }
    
    
    
    public function setCookieForCurrentRequest(halo\protocol\http\IResponseCookie $cookie) {
        $this->_currentCookies->set($cookie);
        return $this;
    }
    
    public function removeCookieForCurrentRequest(halo\protocol\http\IResponseCookie $cookie) {
        $this->_currentCookies->remove($cookie);
        return $this;
    }
    
    
    public function setCookieForAnyRequest(halo\protocol\http\IResponseCookie $cookie) {
        $this->_globalCookies->set($cookie);
        $this->_currentCookies->set($cookie);
        return $this;
    }
    
    public function removeCookieForAnyRequest(halo\protocol\http\IResponseCookie $cookie) {
        $this->_globalCookies->remove($cookie);
        $this->_currentCookies->remove($cookie);
        return $this;
    }
    
    public function getCookieCollectionForCurrentRequest() {
        return $this->_currentCookies;
    }
    
    public function getCookieCollectionForAnyRequest() {
        return $this->_globalCookies;
    }
}

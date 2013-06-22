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
    
    protected $_globalHeaders = array();
    protected $_currentHeaders = array();

    protected $_globalCookies;
    protected $_currentCookies;
    
    public function __construct() {
        $this->resetAll();
    }

    public function resetAll() {
        $this->_globalHeaders = array();
        $this->_globalCookies = new halo\protocol\http\response\CookieCollection();
        $this->resetCurrent();
        return $this;
    }
    
    public function resetCurrent() {
        $this->_currentHeaders = $this->_globalHeaders;
        $this->_currentCookies = clone $this->_globalCookies;
        return $this;
    }
    
    public function apply(halo\protocol\http\IResponse $response) {
        $headers = $response->getHeaders();

        foreach($this->_currentHeaders as $set) {
            switch($set[0]) {
                case '+':
                    $headers->add($set[1], $set[2]);
                    break;

                case '*':
                    $headers->set($set[1], $set[2]);
                    break;

                case '-':
                    $headers->remove($set[1]);
                    break;
            }
        }

        $response->getCookies()->import($this->_currentCookies);
        return $this;
    }
    

// Headers
    public function addHeaderForCurrentRequest($name, $value) {
        $this->_currentHeaders[] = ['+', $name, $value];
        return $this;
    }

    public function setHeaderForCurrentRequest($name, $value) {
        $this->_currentHeaders[] = ['*', $name, $value];
        return $this;
    }

    public function removeHeaderForCurrentRequest($name) {
        $this->_currentHeaders[] = ['-', $name];
        return $this;
    }

    public function addHeaderForAnyRequest($name, $value) {
        $this->_currentHeaders[] = ['+', $name, $value];
        $this->_globalHeaders[] = ['+', $name, $value];
        return $this;
    }

    public function setHeaderForAnyRequest($name, $value) {
        $this->_currentHeaders[] = ['*', $name, $value];
        $this->_globalHeaders[] = ['*', $name, $value];
        return $this;
    }

    public function removeHeaderAnyRequest($name) {
        $this->_currentHeaders[] = ['-', $name];
        $this->_globalHeaders[] = ['-', $name];
        return $this;
    }


// Cookies
    public function newCookie($name, $value) {
        return new Cookie($name, $value);
    }
    
    
    
    public function setCookieForCurrentRequest(halo\protocol\http\IResponseCookie $cookie) {
        $this->_currentCookies->set($cookie);
        return $this;
    }
    
    public function removeCookieForCurrentRequest($cookie) {
        $this->_currentCookies->remove($cookie);
        return $this;
    }
    
    
    public function setCookieForAnyRequest(halo\protocol\http\IResponseCookie $cookie) {
        $this->_globalCookies->set($cookie);
        $this->_currentCookies->set($cookie);
        return $this;
    }
    
    public function removeCookieForAnyRequest($cookie) {
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

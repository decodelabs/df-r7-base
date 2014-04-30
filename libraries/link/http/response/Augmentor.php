<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\http\response;

use df;
use df\core;
use df\link;

class Augmentor implements link\http\IResponseAugmentor {
    
    protected $_globalHeaders = [];
    protected $_currentHeaders = [];

    protected $_globalCookies;
    protected $_currentCookies;

    protected $_statusCode;
    
    public function __construct() {
        $this->resetAll();
    }

    public function resetAll() {
        $this->_globalHeaders = [];
        $this->_globalCookies = new link\http\response\CookieCollection();
        $this->resetCurrent();
        return $this;
    }
    
    public function resetCurrent() {
        $this->_currentHeaders = $this->_globalHeaders;
        $this->_currentCookies = clone $this->_globalCookies;
        $this->_statusCode = null;
        return $this;
    }
    
    public function apply(link\http\IResponse $response) {
        $headers = $response->getHeaders();

        if($this->_statusCode !== null) {
            $headers->setStatusCode($this->_statusCode);
        }

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


// Status
    public function setStatusCode($code) {
        if(!HeaderCollection::isValidStatusCode($code)) {
            $code = null;
        }

        $this->_statusCode = $code;
        return $this;
    }

    public function getStatusCode() {
        return $this->_statusCode;
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
    
    
    
    public function setCookieForCurrentRequest(link\http\IResponseCookie $cookie) {
        $this->_currentCookies->set($cookie);
        return $this;
    }
    
    public function removeCookieForCurrentRequest($cookie) {
        $this->_currentCookies->remove($cookie);
        return $this;
    }
    
    
    public function setCookieForAnyRequest(link\http\IResponseCookie $cookie) {
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

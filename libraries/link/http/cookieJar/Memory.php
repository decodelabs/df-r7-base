<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\http\cookieJar;

use df;
use df\core;
use df\link;

class Memory implements link\http\ICookieJar, core\IDumpable {
    
    protected $_cookies = [];

    public function __construct(array $cookies=null) {
        if($cookies) {
            foreach($cookies as $cookie) {
                if(is_string($cookie)) {
                    $cookie = link\http\Cookie::fromString($cookie);
                } else if(!$cookie instanceof link\http\ICookie) {
                    throw new link\http\InvalidArgumentException(
                        'Invalid cookie'
                    );
                }

                $this->set($cookie);
            }
        }
    }

    public function applyTo(link\http\IRequest $request) {
        if(empty($this->_cookies)) {
            return $this;
        }

        $path = (string)$request->url->path;
        $domain = (string)$request->url->getDomain();
        $isSecure = $request->url->isSecure();

        foreach($this->_cookies as $cookie) {
            if($cookie->matchesPath($path)
            && $cookie->matchesDomain($domain)
            && !$cookie->isExpired()
            && $cookie->isSecure() == $isSecure) {
                $request->cookies->set($cookie->getName(), $cookie->getValue());
            }
        }

        return $this;
    }

    public function import(link\http\IResponse $response) {
        $cookies = $response->getCookies();

        foreach($cookies->toArray() as $cookie) {
            $this->set($cookie);
        }

        foreach($cookies->getRemoved() as $cookie) {
            $this->set($cookie);
        }

        return $this;
    }

    public function set(link\http\ICookie $cookie) {
        $value = $cookie->getValue();

        if($value === '' || $value === null) {
            $this->clear(
                $cookie->getDomain(),
                $cookie->getPath(),
                $cookie->getName()
            );

            return $this;
        }

        foreach($this->_cookies as $i => $test) {
            if($cookie->getName() != $test->getName()
            || $cookie->getDomain() != $test->getDomain()
            || $cookie->getPath() != $test->getPath()) {
                continue;
            }

            $newExpiry = $cookie->getExpiryDate();
            $oldExpiry = $test->getExpiryDate();

            if($newExpiry && !$oldExpiry) {
                unset($this->_cookies[$i]);
                continue;
            }

            if($newExpiry && $oldExpiry && $newExpiry->gt($oldExpiry)) {
                unset($this->_cookies[$i]);
                continue;
            }

            if($cookie->getValue() != $test->getValue()) {
                unset($this->_cookies[$i]);
                continue;
            }

            return $this;
        }

        $this->_cookies[] = clone $cookie;
        return $this;
    }

    public function clear($domain=null, $path=null, $name=null) {
        if(!$domain) {
            $this->_cookies = [];
            return $this;
        }

        $this->_cookies = array_filter(
            $this->_cookies,
            function($cookie) use($domain, $path, $name) {
                return !($cookie->matchesDomain($domain)
                      && $cookie->matchesPath($path)
                      && $cookie->matchesName($name));
            }
        );

        return $this;
    }

    public function clearSession() {
        $this->_cookies = array_filter(
            $this->_cookies,
            function($cookie) {
                return (bool)$cookie->getExpiryDate();
            }
        );

        return $this;
    }

// Dump
    public function getDumpProperties() {
        $output = [];
        
        foreach($this->_cookies as $cookie) {
            $output[$cookie->getName()] = $cookie->toString();
        }
        
        return $output;
    }
}
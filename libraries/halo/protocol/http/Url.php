<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\protocol\http;

use df;
use df\core;
use df\halo;
use df\arch;

class Url extends core\uri\Url implements IUrl {
    
    use core\uri\TUrl;
    use core\uri\TCredentialContainer;
    use core\uri\TDomainContainer;
    use core\uri\TPortContainer;
    
    public static function fromDirectoryRequest(arch\IRequest $request, $scheme, $domain, $port, array $basePath) {
        if($request->_isJustFragment) {
            return new self('#'.$request->_fragment);
        }
        
        $path = null;
        
        if($request->_path) {
            $path = clone $request->_path;
            
            if($path->get(0) == '~'.$request::DEFAULT_AREA) {
                $path->shift();
            }
            
            if(!empty($basePath)) {
                $path->unshift($basePath);
            }
        } else if(!empty($basePath)) {
            $path = new core\uri\Path($basePath);
            $path->shouldAddTrailingSlash(true);
        }
        
        $output = new self();
        $output->_scheme = $scheme;
        $output->_domain = $domain;
        $output->_port = $port;
        
        if(!empty($path)) {
            $output->_path = $path;
        }
        
        if(!empty($request->_query)) {
            $output->_query = $request->_query;
        }
        
        if(!empty($request->_fragment)) {
            $output->_fragment = $request->_fragment;
        }
        
        return $output;
    }
    
    public static function factory($url) {
        if($url instanceof IUrl) {
            return $url;
        }
        
        $class = get_called_class();
        return new $class($url);
    }
    
    public function import($url='') {
        if(empty($url)) {
            return $this;
        }
        
        $this->reset();
        
        if($url instanceof self) {
            $this->_scheme = $url->_scheme;
            $this->_username = $url->_username;
            $this->_password = $url->_password;
            $this->_domain = $url->_domain;
            $this->_port = $url->_port;
            
            if($url->_path !== null) {
                $this->_path = clone $url->_path;
            }
            
            if($url->_query !== null) {
                $this->_query = clone $url->_query;
            }
            
            $this->_fragment = $url->_fragment;
            
            return $this;
        }
        
        // Fragment
        $parts = explode('#', $url, 2);
        $url = array_shift($parts);
        $this->setFragment(array_shift($parts));
        
        // Query
        $parts = explode('?', $url, 2);
        $url = array_shift($parts);
        $this->setQuery(array_shift($parts));
        
        // Scheme
        $parts = explode('://', $url, 2);
        $url = array_pop($parts);
        $this->setScheme(array_shift($parts));
        
        $path = explode('/', $url);
        
        if(substr($url, 0, 1) == '/') {
            unset($path[0]);
            $app = df\Launchpad::getActiveApplication();
            
            if($app instanceof core\application\Http) {
                $requestUrl = $app->getHttpRequest()->getUrl();
                
                $this->_username = $requestUrl->getUsername();
                $this->_password = $requestUrl->getPassword();
                $this->_domain = $requestUrl->getDomain();
                $this->_port = $requestUrl->getPort();
            } else if(isset($_SERVER['HTTP_HOST'])) {
                $this->_domain = $_SERVER['HTTP_HOST'];
                $this->_port = $_SERVER['SERVER_PORT'];
            }
        } else {
            $domain = array_shift($path);
            
            // Credentials
            $credentials = explode('@', $domain, 2);
            $domain = array_pop($credentials);
            $credentials = array_shift($credentials);
            
            if(!empty($credentials)) {
                $credentials = explode(':', $credentials, 2);
                $this->setUsername(array_shift($credentials));
                $this->setPassword(array_shift($credentials));
            }
            
            // Host + port
            $port = explode(':', $domain, 2);
            $this->setDomain(array_shift($port));
            $this->setPort(array_shift($port));
            
            if(!empty($path) && empty($path[0])) {
                $path = null;
            }
        }
        
        if(!empty($path)) {
            $this->setPath($path);
        }
        
        return $this;
    }
    
    
    public function __get($member) {
        switch($member) {
            case 'username':
                return $this->getUsername();
                
            case 'password':
                return $this->getPassword();
                
            case 'domain':
                return $this->getDomain();
                
            case 'port':
                return $this->getPort();
        }
    }
    
    public function __set($member, $value) {
        switch($member) {
            case 'username':
                return $this->setUsername($value);
                
            case 'password':
                return $this->setPassword($value);
                
            case 'domain':
                return $this->setDomain($value);
                
            case 'port':
                return $this->setPort($value);
        }
    }
    
// Scheme
    public function setScheme($scheme) {
        $scheme = strtolower($scheme);
        
        if($scheme !== 'http' && $scheme !== 'https') {
            $scheme = 'http';
        }
        
        $this->_scheme = $scheme;
        
        return $this;
    }
    
    public function isSecure($flag=null) {
        if($flag !== null) {
            $this->_scheme = (bool)$flag ? 'https' : 'http';
            return $this;    
        }
        
        return $this->_scheme == 'https';
    }
    
    
// Port
    public function getPort() {
        if($this->_port === null) {
            if($this->_scheme == 'http') {
                return 80;
            } else if($this->_scheme == 'https') {
                return 443;
            }
        }
        
        return $this->_port;
    }
    
    
// Strings
    public function toString() {
        if($this->isJustFragment()) {
            return $this->_getFragmentString();
        }
        
        $output = $this->getScheme().'://';
        $output .= $this->_getCredentialString();
        $output .= $this->_domain;
        $output .= $this->_getPortString($this->_scheme == 'https' ? 443 : 80);
        $output .= $this->getLocalString();
        
        return $output;
    }
    
    public function getLocalString() {
        if($this->isJustFragment()) {
            return $this->_getFragmentString();
        }
        
        $output = $this->_getPathString(true);
        $output .= $this->_getQueryString();
        $output .= $this->_getFragmentString();
        
        return $output;
    }
}

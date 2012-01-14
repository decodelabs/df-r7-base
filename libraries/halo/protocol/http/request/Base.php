<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\protocol\http\request;

use df;
use df\core;
use df\halo;

class Base implements halo\protocol\http\IRequest, core\IDumpable {
    
    const GET     = 'GET';
    const POST    = 'POST';
    const PUT     = 'PUT';
    const HEAD    = 'HEAD';
    const DELETE  = 'DELETE';
    const TRACE   = 'TRACE';
    const OPTIONS = 'OPTIONS';
    const CONNECT = 'CONNECT';
    const MERGE   = 'MERGE';
    
    protected $_method = self::GET;
    protected $_url;
    protected $_headers;
    protected $_ip;
    protected $_postData;
    protected $_cookieData;
    protected $_environmentMode = false;
    
    public static function fromString($string) {
        $class = get_called_class();
        $output = new $class();
        
        $parts = preg_split('|(?:\r?\n){2}|m', $string, 2);
        
        $headers = array_shift($parts);
        $content = array_shift($parts);
        
        $lines = explode("\n", $headers);
        $http = array_shift($lines);
        
        $output->setMethod(trim(strtok(trim($http), ' ')));
        $headers = $output->getHeaders();
        
        $url = trim(strtok(' '));
        $protocol = strtok('/');
        
        if($protocol !== 'HTTP') {
            throw new halo\protocol\http\UnexpectedValueException(
                'Protocol '.$protocol.' is not valid HTTP'
            );
        }
        
        $headers->setHttpVersion(trim(strtok('')));
        
        foreach($lines as $line) {
            $headers->set(trim(strtok(trim($line), ':')), trim(strtok('')));
        }
        
        $output->setUrl($headers->get('host').$url);
        
        if($headers->has('cookie')) {
            $output->setCookieData($headers->get('cookie'));
        }
        
        return $output;
    }
    
    public function __construct($url=null, $environmentMode=false) {
        if($url === true || $url === false) {
            $environmentMode = $url;
            $url = null;
        }
        
        if($url !== null) {
            $this->setUrl($url);
        }
        
        if($this->_environmentMode = $environmentMode) {
            $this->setMethod($_SERVER['REQUEST_METHOD']);
        }
    }
    
    public function __clone() {
        if($this->_url) {
            $this->_url = clone $this->_url;
        }
        
        if($this->_postData) {
            $this->_postData = clone $this->_postData;
        }
        
        if($this->_headers) {
            $this->_headers = clone $this->_headers;
        }
        
        parent::__clone();
    }
    
    public function __get($member) {
        switch($member) {
            case 'method':
                return $this->getMethod();
                
            case 'url':
                return $this->getUrl();
                
            case 'headers':
                return $this->getHeaders();
                
            case 'post':
                return $this->getPostData();
                
            case 'cookies':
                return $this->getCookieData();
                
            case 'ip':
                return $this->getIp();
        }
    }
    
    public function __set($member, $value) {
        switch($member) {
            case 'method':
                return $this->setMethod($value);
                
            case 'url':
                return $this->setUrl($value);
                
            case 'headers':
                return $this->setHeaders($value);
                
            case 'post':
                return $this->setPostData($value);
                
            case 'cookies':
                return $this->setCookieData($value);
                
            case 'ip':
                return $this->setIp($value);
        }
    }
    
    public function shouldUseEnvironment($flag=null) {
        if($flag !== null) {
            $this->_environmentMode = (bool)$flag;
            return $this;
        }
        
        return $this->_environmentMode;
    }
    
    
// Method
    public function setMethod($method) {
        $method = strtoupper($method);
        
        switch($method) {
            case self::POST:
            case self::GET:
            case self::PUT:
            case self::HEAD:
            case self::DELETE:
            case self::TRACE:
            case self::OPTIONS:
            case self::CONNECT:
            case self::MERGE:
                $this->_method = $method;
                break;
                
            default:
                throw new halo\protocol\http\UnexpectedValueException(
                    $method.' is not a valid request method'
                );
        }
        
        if($this->_method === self::POST && $this->_postData === null) {
            $this->setPostData(null);
        } else if($this->_method !== self::POST) {
            $this->_postData = null;
        }
        
        return $this;
    }
    
    public function getMethod() {
        return $this->_method;
    }
    
    public function isGet() {
        return $this->_method === self::GET;
    }
    
    public function isPost() {
        return $this->_method === self::POST;
    }
    
    public function isPut() {
        return $this->_method === self::PUT;
    }
    
    public function isHead() {
        return $this->_method === self::HEAD;
    } 
    
    public function isDelete() {
        return $this->_method === self::DELETE;
    }
    
    public function isTrace() {
        return $this->_method === self::TRACE;
    }
    
    public function isOptions() {
        return $this->_method === self::OPTIONS;
    }
    
    public function isConnect() {
        return $this->_method === self::CONNECT;
    }
    
    public function isMerge() {
        return $this->_method === self::MERGE;
    }
    
    
// Url
    public function setUrl($url) {
        if($url !== null) {
            $url = halo\protocol\http\Url::factory($url);
        }
        
        $this->_url = $url;
        return $this;
    }
    
    public function getUrl() {
        if(!$this->_url) {
            $url = null;
            
            if($this->_environmentMode) {
                $url = (isset($_SERVER['HTTPS']) && !strcasecmp($_SERVER['HTTPS'], 'on')) ? 'https' : 'http';
                $url .= '://'.$_SERVER['HTTP_HOST'].':'.$_SERVER['SERVER_PORT'];
            
                $req = explode('?', ltrim($_SERVER['REQUEST_URI'], '/'), 2);
                $req[0] = urldecode($req[0]);
                
                $url .= '/'.implode('?', $req);
            }
            
            $this->_url = new halo\protocol\http\Url($url);
        }
        
        return $this->_url;
    }
    
    
// Headers
    public function setHeaders(halo\protocol\http\IRequestHeaderCollection $headers) {
        $this->_headers = $headers;
        return $this;
    }
    
    public function getHeaders() {
        if(!$this->_headers) {
            $this->_headers = new HeaderCollection();
            
            if($this->_environmentMode) {
                foreach($_SERVER as $key => $var) {
                    if(substr($key, 0, 5) != 'HTTP_') {
                        continue;
                    }
                    
                    $key = substr($key, 5);
                    
                    if($key == 'COOKIE') {
                        $this->setCookieData($var);
                    }
                    
                    $this->_headers->set($key, $var);
                }
            }
        }
        
        return $this->_headers;
    }
    
    public function isCachedByClient() {
        switch($this->_method) {
            case self::POST:
            case self::PUT:
            case self::DELETE:
            case self::OPTIONS:
            case self::CONNECT:
                return false;
        }
        
        if(!$this->_headers) {
            if($this->_environmentMode) {
                return isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
                    || isset($_SERVER['HTTP_IF_NONE_MATCH']);
            }
            
            return false;
        }
        
        return $this->_headers->has('if-modified-since')
            || $this->_headers->has('if-none-match');
    }
    
    
// Post data
    public function setPostData($post) {
        if($this->_method != 'POST') {
            if($post !== null) {
                throw new halo\protocol\http\UnexpectedValueException(
                    'Post data can only be set when request method is POST'
                );
            }
        } else if($post !== null) {
            if(is_string($post)) {
                $post = core\collection\Tree::fromArrayDelimitedString($post);
            } else if(!$post instanceof core\collection\ITree) {
                $post = new core\collection\Tree($post);
            }
        }
        
        $this->_postData = $post;
        
        return $this;
    }
    
    public function getPostData() {
        if(!$this->_postData && $this->_method == 'POST') {
            $this->_postData = new core\collection\Tree($this->_environmentMode ? $_POST : null);
        }
        
        return $this->_postData;
    }
    
    public function hasPostData() {
        return $this->_postData !== null;
    }
    
    
// Cookies
    public function setCookieData($cookies) {
        if(empty($cookies)) {
            $this->_cookieData = null;
        } else {
            if(is_string($cookies)) {
                $cookies = core\collection\Tree::fromArrayDelimitedString($cookies, ';');
            } else if(!$query instanceof core\collection\ITree) {
                $cookies = new core\collection\Tree($cookies);
            }
            
            $this->_cookieData = $cookies;
        }
        
        return $this;
    }
    
    public function getCookieData() {
        if(!$this->_cookieData) {
            $this->_cookieData = new core\collection\Tree();
            $this->getHeaders();
        }
        
        return $this->_cookieData;
    }
    
    public function hasCookieData() {
        if($this->_environmentMode) {
            $this->getHeaders();
        }
        
        return $this->_cookieData !== null;
    }
    
    
// Ip
    public function setIp($ip) {
        if(empty($ip)) {
            $this->_ip = null;
        } else {
            $this->_ip = halo\Ip::factory($ip);
        }
        
        return $this;
    }
    
    public function getIp() {
        if(!$this->_ip && $this->_environmentMode) {
            $ip = '0.0.0.0';
        
            if(isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } else if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else if(isset($_SERVER['REMOTE_ADDR'])) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
            
            if(($pos = strpos($ip, ',')) > 0) {
                $ip = substr($ip, 0, $pos - 1);
            }
            
            $this->_ip = new halo\Ip($ip); 
        }
        
        return $this->_ip;
    }
    
    public function hasIp() {
        return $this->_ip !== null;
    }
    
    
// Sending
    public function prepareToSend() {
        $url = $this->getUrl();
        $host = $url->getDomain();
        $port = $url->getPort();
        
        if(($url->isSecure() && $port !== 443)
        || (!$url->isSecure() && $port !== 80)) {
            $host.':'.$port;
        }
        
        $headers = $this->getHeaders();
        $headers->set('host', $host);
        
        if($this->_cookieData && !$this->_cookieData->isEmpty()) {
            $headers->set('cookie', $this->_cookieData->toString());
        }
        
        return $this;
    }
    
    public function __toString() {
        try {
            return (string)$this->toString();
        } catch(\Exception $e) {
            return '';
        }
    }
    
    public function toString() {
        $this->prepareToSend();
        
        $headers = $this->getHeaders();
        $output = $this->getMethod().' '.$this->_url->getLocalString().' HTTP/'.$header->getHttpVersion()."\r\n";
        $output .= $headers->toString()."\r\n";
        
        if($this->_postData) {
            core\stub('add post data');
        }
        
        return $output;
    }
    
    
// Dump
    public function getDumpProperties() {
        $output = array(
            'method' => $this->getMethod(),
            'url' => $this->getUrl()
        );
        
        if($ip = $this->getIp()) {
            $output['ip'] = $ip;
        }
        
        $output['headers'] = $this->getHeaders();
        
        if($this->_method == 'POST') {
            $output['post'] = $this->getPostData();
        }
        
        if($this->_cookieData && count($this->_cookieData)) {
            $output['cookies'] = $this->_cookieData;
        }
        
        return $output;
    }
}
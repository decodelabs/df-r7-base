<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\http\request;

use df;
use df\core;
use df\link;

class Base implements link\http\IRequest, core\IDumpable {
    
    use core\TStringProvider;

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
    protected $_bodyData;
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
            throw new link\http\UnexpectedValueException(
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

    public static function factory($url) {
        if($url instanceof link\http\IRequest) {
            return $url;
        }
        
        return new self($url);
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
            if(isset($_SERVER['REQUEST_METHOD'])) {
                $this->setMethod($_SERVER['REQUEST_METHOD']);
            } else {
                $this->setMethod('GET');
            }
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

        $this->_ip = null;
        
        return $this;
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
                throw new link\http\UnexpectedValueException(
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
            $url = link\http\Url::factory($url);
        }
        
        $this->_url = $url;
        $this->_ip = null;

        if($this->_headers) {
            $this->_headers->remove('Host');
        }

        return $this;
    }
    
    public function getUrl() {
        if(!$this->_url) {
            $url = null;
            
            if($this->_environmentMode) {
                if(isset($_SERVER['HTTPS']) && !strcasecmp($_SERVER['HTTPS'], 'on')) {
                    $url = 'https';
                } else {
                    $url = 'http';
                }
                
                if(isset($_SERVER['HTTP_HOST'])) {
                    $url .= '://'.$_SERVER['HTTP_HOST'].':'.$_SERVER['SERVER_PORT'];
                } else {
                    $url .= '://'.gethostname();
                }
                
                if(isset($_SERVER['REQUEST_URI'])) {
                    $req = ltrim($_SERVER['REQUEST_URI'], '/');
                } else if(isset($_SERVER['argv'][2])) {
                    $req = $_SERVER['argv'][2];
                } else {
                    $req = '';
                }

                $req = explode('?', $req, 2);
                $req[0] = urldecode($req[0]);
                
                $url .= '/'.implode('?', $req);
            }
            
            $this->_url = new link\http\Url($url);
        }
        
        return $this->_url;
    }
    
    
// Headers
    public function setHeaders(core\collection\IHeaderMap $headers) {
        if(!$headers instanceof link\http\IRequestHeaderCollection) {
            throw new link\http\InvalidArgumentException(
                'Request headers must implement IRequestHeaderCollection'
            );
        }

        $this->_headers = $headers;
        return $this;
    }
    
    public function getHeaders() {
        if(!$this->_headers) {
            if($this->_environmentMode) {
                $this->_headers = HeaderCollection::fromEnvironment();

                if($this->_headers->has('cookie')) {
                    $this->setCookieData($this->_headers->get('cookie'));
                }
            } else {
                $this->_headers = new HeaderCollection();
            }
        }
        
        return $this->_headers;
    }

    public function hasHeaders() {
        return $this->_headers && !$this->_headers->isEmpty();
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
                throw new link\http\UnexpectedValueException(
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
            $postData = null;
            
            if($this->_environmentMode) {
                $payload = file_get_contents('php://input');
                $usePost = true;

                switch(strtolower($this->_headers->getBase('content-type'))) {
                    case 'application/x-www-form-urlencoded':
                        try {
                            $this->_postData = core\collection\Tree::fromArrayDelimitedString($payload);
                        } catch(\Exception $e) {
                            $this->_postData = null;
                        }

                        break;

                    case 'application/json':
                        $usePost = false;
                        $this->_postData = core\collection\Tree::factory(json_decode($payload, true));
                        break;
                }

                if($usePost && $this->_postData && $this->_postData->isEmpty()) {
                    $this->_postData = null;
                }

                if(empty($this->_postData) && !empty($_POST) && $usePost) {
                    $this->_postData = new core\collection\Tree($_POST);
                }
            }
            
            if(!$this->_postData) {
                $this->_postData = new core\collection\Tree(null);
            }
        }
        
        return $this->_postData;
    }
    
    public function getPostDataString() {
        core\stub($this->_postData);
    }
    
    public function hasPostData() {
        return $this->_postData !== null;
    }
    
    
// Put
    public function setBodyData($body) {
        if(is_array($body)) {
            $body = implode("\r\n", $body);
        } else if($body instanceof core\io\IFilePointer) {
            // do nothing?
        } else if($body !== null) {
            $body = (string)$body;
        }
        
        $this->_bodyData = $body;
        return $this;
    }
    
    public function getBodyData() {
        return $this->_bodyData;
    }

    public function getBodyDataString() {
        if($this->_bodyData instanceof core\io\IFilePointer) {
            return $this->_bodyData->getContents();
        } else {
            return (string)$this->_bodyData;
        }
    }
    
    public function hasBodyData() {
        return $this->_bodyData !== null;
    }
    
    
// Cookies
    public function setCookieData($cookies) {
        if(empty($cookies)) {
            $this->_cookieData = null;
        } else {
            if(is_string($cookies)) {
                $cookies = core\collection\Tree::fromArrayDelimitedString(trim($cookies, ';'), ';');
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

    public function setCookie($key, $value) {
        $this->getCookieData()->set($key, $value);
        return $this;
    }

    public function getCookie($key, $default=null) {
        return $this->getCookieData()->get($key, $default);
    }

    public function hasCookie($key) {
        return $this->getCookieData()->has($key);
    }

    public function removeCookie($key) {
        $this->getCookieData()->remove($key);
        return $this;
    }
    
    
// Ip
    public function setIp($ip) {
        if(empty($ip)) {
            $this->_ip = null;
        } else {
            $this->_ip = link\Ip::factory($ip);
        }
        
        return $this;
    }
    
    public function getIp() {
        if($this->_ip === null) {
            if($this->_environmentMode) {
                $ip = '0.0.0.0';
            
                if(isset($_SERVER['HTTP_CLIENT_IP'])) {
                    $ip = $_SERVER['HTTP_CLIENT_IP'];
                } else if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
                } else if(isset($_SERVER['REMOTE_ADDR'])) {
                    $ip = $_SERVER['REMOTE_ADDR'];
                }

                $parts = explode(',', $ip);

                while(!empty($parts)) {
                    $ip = trim(array_shift($parts));

                    try {
                        $this->_ip = new link\Ip($ip);
                        break;
                    } catch(link\InvalidArgumentException $e) {
                        if(empty($parts)) {
                            throw $e;
                        }
                    }
                }
            } else {
                $this->_ip = $this->_url->lookupIp();
            }
        }
        
        return $this->_ip;
    }
    
    public function hasIp() {
        return $this->_ip !== null;
    }
    
    public function getSocketAddress() {
        return (string)$this->getIp().':'.$this->_url->getPort();
    }
    
    
// Sending
    public function prepareHeaders() {
        $url = $this->getUrl();
        $host = $url->getDomain();
        $port = $url->getPort();
        
        if(($url->isSecure() && $port !== 443)
        || (!$url->isSecure() && $port !== 80)) {
            $host = $host.':'.$port;
        }
        
        $headers = $this->getHeaders();
        $headers->set('host', $host);
        
        if($this->_postData && !$this->_postData->isEmpty()) {
            if(!$headers->has('content-type')) {
                $headers->set('content-type', 'application/x-www-form-urlencoded');
            }

            switch($headers->get('content-type')) {
                case 'application/x-www-form-urlencoded':
                    $postData = $this->_postData->toArrayDelimitedString();

                    if($url->shouldEncodeQueryAsRfc3986()) {
                        $postData = str_replace('%7E', '~', $postData);
                    }

                    $this->_bodyData = $postData;
                    break;

                default:
                    core\stub('Convert post data to raw body data');
            }
        }
        
        if($this->_bodyData !== null && $headers->get('Transfer-Encoding') != 'chunked') {
            if($this->_bodyData instanceof core\io\IFilePointer) {
                $headers->set('Content-Length', $this->_bodyData->getSize());

                if(!$headers->has('Content-Type')) {
                    $headers->set('Content-Type', $this->_bodyData->getContentType());
                }
            } else {
                $headers->set('Content-Length', strlen($this->_bodyData));
            }
        }
        
        if($this->_cookieData && !$this->_cookieData->isEmpty()) {
            $headers->set('cookie', $this->_cookieData->toArrayDelimitedString(';'));
        }
        
        return $this;
    }
    
    public function toString() {
        $output = $this->getHeaderString()."\r\n\r\n";
        
        if($this->hasBodyData()) {
            $output .= $this->getBodyDataString();
        }

        return $output;
    }
    
    public function getHeaderString(array $skipKeys=null) {
        $this->prepareHeaders();
        
        $headers = $this->getHeaders();
        $output = $this->getMethod().' '.$this->_url->getLocalString().' HTTP/'.$headers->getHttpVersion()."\r\n";
        $output .= $headers->toString($skipKeys);
        
        return $output;
    }
    
    
// Dump
    public function getDumpProperties() {
        $output = [
            'method' => $this->getMethod(),
            'url' => $this->getUrl()
        ];
        
        if($ip = $this->getIp()) {
            $output['ip'] = $ip;
        }
        
        $output['headers'] = $this->getHeaders();
        
        if($this->_method == 'POST') {
            $output['post'] = $this->getPostData();
        }
        
        if($this->_bodyData !== null) {
            $output['body'] = $this->getBodyData();
        }
        
        if($this->_cookieData && count($this->_cookieData)) {
            $output['cookies'] = $this->_cookieData;
        }
        
        return $output;
    }
}
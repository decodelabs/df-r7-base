<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\protocol\http\response;

use df;
use df\core;
use df\halo;

abstract class Base implements halo\protocol\http\IResponse {
    
    protected $_headers;
    protected $_cookies;
    
    public static function fromString($string) {
        $output = new String();
        $parts = preg_split('|(?:\r?\n){2}|m', $string, 2);
        
        $headers = array_shift($parts);
        $content = array_shift($parts);
        
        $lines = explode("\n", $headers);
        $http = array_shift($lines);
        $headers = $output->getHeaders()->clear();
        
        if(!preg_match("|^HTTP/([\d\.x]+) (\d+) ([^\r\n]+)|", $http, $matches)) {
            throw new halo\protocol\http\UnexpectedValueException(
                'Headers do not appear to be valid HTTP format'
            );
        }
        
        $headers->setHttpVersion($matches[1]);
        $headers->setStatusCode($matches[2]);
        $headers->setStatusMessage($matches[3]);
        
        foreach($lines as $line) {
            $headers->set(trim(strtok(trim($line), ':')), trim(strtok('')));
        }
        
        if($headers->has('transfer-encoding')) {
            switch(strtolower($headers->get('transfer-encoding'))) {
                case 'chunked':
                    $content = self::decodeChunked($content);
                    
                    if(strlen($content)) {
                        foreach(explode("\n", $content) as $line) {
                            $headers->set(trim(strtok(trim($line), ':')), trim(strtok('')));
                        }
                    }
                    
                    break;
                    
                case 'x-compress':
                case 'compress':
                case 'x-deflate':
                case 'deflate':
                    $content = self::decodeDeflate($content);
                    break;
                    
                case 'x-gzip':
                case 'gzip':
                    $content = self::decodeGzip($content);
                    break;
                    
                case 'identity':
                    break;
            }
        }
        
        
        if($headers->has('content-encoding')) {
            switch(strtolower($headers->get('content-encoding'))) {
                case 'x-compress':
                case 'compress':
                case 'x-deflate':
                case 'deflate':
                    $content = self::decodeDeflate($content);
                    break;
                    
                case 'x-gzip':
                case 'gzip':
                    $content = self::decodeGzip($content);
                    break;
                    
                case 'identity':
                    break;
                    
                default:
                    throw new halo\protocol\http\RuntimeException(
                        ucfirst($headers->get('content-encoding')).' response compression is not available'
                    );
            }
        }
        
        $output->_content = $content;
        
        return $output;
    }
    
    public static function decodeChunked(&$content) {
        $output = '';
        
        while(true) {
            $content = ltrim($content);
            
            if(!isset($content{0}) || !preg_match("/^([\da-fA-F]+)[^\r\n]*\r\n/sm", $content, $matches)) {
                throw new halo\protocol\http\UnexpectedValueException('The body does not appear to be chunked properly');
            }
            
            $length = hexdec(trim($matches[1]));
            $cut = strlen($matches[0]);
            $output .= substr($content, $cut, $length);
            $content = substr($content, $cut + $length + 2);
            
            if($length == 0) {
                break;
            }
        }
        
        $content = trim($content);
        return $output;
    }
    
    public static function encodeChunked($content) {
        $chunkSize = 16;
        $output = '';
        
        while(isset($content{0})) {
            $current = substr($content, 0, $chunkSize);
            $content = substr($content, $chunkSize);
            
            $output .= dechex(strlen($current))."\r\n";
            $output .= $current."\r\n";
        }
        
        $output .= '0'."\r\n";
        return $output;
    }
    
    public static function decodeDeflate($content) {
        if(!function_exists('gzuncompress')) {
            throw new halo\protocol\http\RuntimeException(
                'Gzip response compression is not available'
            );
        }
        
        $header = unpack('n', substr($content, 0, 2));
        
        if($header[1] % 31 == 0) {
            return gzuncompress($content);
        } else {
            return gzinflate($content);
        }
    }
    
    public static function encodeDeflate($content) {
        core\stub();
    }
    
    public static function decodeGzip($content) {
        if(!function_exists('gzinflate')) {
            throw new halo\protocol\http\RuntimeException(
                'Gzip inflate response compression is not available'
            );
        }
        
        return gzinflate(substr($body, 10));
    }
    
    public static function encodeGzip($content) {
        core\stub();
    }
    
    
    
    
    public function getHeaders() {
        if(!$this->_headers) {
            $this->_headers = new HeaderCollection();
        }
        
        return $this->_headers;
    }
    
    public function hasHeaders() {
        return $this->_headers && !$this->_headers->isEmpty();
    }
    
    public function getCookies() {
        if(!$this->_cookies) {
            $this->_cookies = new CookieCollection();
        }
        
        return $this->_cookies;
    }
    
    public function hasCookies() {
        return $this->_cookies && !$this->_cookies->isEmpty();
    }
    
    public function isOk() {
        if(!$this->_headers) {
            return true;
        }
        
        return $this->_headers->hasSuccessStatusCode();
    }
    
    public function getJsonContent() {
        $content = $this->getContent();
        
        if(!strlen($content)) {
            throw new RuntimeException(
                'Empty json response'
            );
        }
        
        $data = \json_decode($content, true);
        
        if($data === false || $data === null) {
            throw new halo\protocol\http\RuntimeException(
                'Invalid json response: '.$content
            );
        }
        
        return new core\collection\Tree($data);
    }
    
    public function getEncodedContent() {
        $content = $this->getContent();
        
        if(!$this->_headers || empty($content)) {
            return $content;
        }
        
        $contentEncoding = $this->_headers->get('content-encoding');
        $transferEncoding = $this->_headers->get('transfer-encoding');
        
        if(!$contentEncoding && !$transferEncoding) {
            return $content;
        }
        
        return self::encodeContent(
            $content, $contentEncoding, $transferEncoding
        );
    }
    
    public static function encodeContent($content, $contentEncoding, $transferEncoding) {
        if($contentEncoding !== null) {
            switch(strtolower($contentEncoding)) {
                case 'x-compress':
                case 'compress':
                case 'x-deflate':
                case 'deflate':
                    $content = self::encodeDeflate($content);
                    break;
                    
                case 'x-gzip':
                case 'gzip':
                    $content = self::encodeGzip($content);
                    break;
                    
                case 'identity':
                    break;
                    
                default:
                    throw new halo\protocol\http\RuntimeException(
                        ucfirst($contentEncoding).' response compression is not available'
                    );
            }
        }
        
        if($transferEncoding !== null) {
            switch(strtolower($transferEncoding)) {
                case 'chunked':
                    $content = self::encodeChunked($content);
                    break;
                    
                case 'x-compress':
                case 'compress':
                case 'x-deflate':
                case 'deflate':
                    $content = self::encodeDeflate($content);
                    break;
                    
                case 'x-gzip':
                case 'gzip':
                    $content = self::encodeGzip($content);
                    break;
                    
                case 'identity':
                    break;
            }
        }
        
        return $content;
    }
    
    public function onDispatchComplete() {}
    
// Info
    public function setContentType($contentType) {
        if($contentType === null) {
            $contentType = 'text/plain';
        }
        
        $this->getHeaders()->set('content-type', $contentType);
        return $this;
    }
    
    public function getContentType() {
        if(!$this->_headers) {
            return 'text/plain';
        }
        
        return $this->_headers->get('content-type');
    }
    
    public function getContentLength() {
        $headers = $this->getHeaders();
        
        if(!$headers->has('content-length')) {
            $headers->set('content-length', strlen($this->getEncodedContent()));
        }
        
        return $headers->get('content-length');
    }
    
    public function getLastModified() {
        if(!$this->_headers) {
            return new core\time\Date();
        }
        
        if(!$this->_headers->has('last-modified')) {
            $this->_headers->set('last-modified', new core\time\Date());
        }
        
        return core\time\Date::factory($this->_headers->get('last-modified'));
    }
    
// Strings
    public function getResponseString() {
        $output = $this->getHeaderString();
        $output .= $this->getEncodedContent()."\r\n";
                  
        return $output;
    }
    
    public function getHeaderString() {
        if($this->hasCookies()) {
            $this->_cookies->applyTo($this->getHeaders());
        }
        
        return self::buildHeaderString($this->_headers);
    }
    
    public static function buildHeaderString(halo\protocol\http\IResponseHeaderCollection $headers=null) {
        $output = '';
            
        if($headers) {
            $version = $headers->getHttpVersion();
            $code = $headers->getStatusCode();
            $message = $headers->getStatusMessage();
            
            if(!$headers->isEmpty()) {
                $output = $headers->toString()."\r\n";
            }
        } else {
            $version = '1.1';
            $code = '200';
            $message = 'OK';
        }
        
        return 'HTTP/'.$version.' '.$code.' '.$message."\r\n".$output."\r\n";
    }
}
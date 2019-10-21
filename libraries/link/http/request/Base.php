<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\http\request;

use df;
use df\core;
use df\link;
use df\flex;

use DecodeLabs\Atlas;
use DecodeLabs\Atlas\File;
use DecodeLabs\Atlas\Channel;

use DecodeLabs\Glitch;
use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class Base implements link\http\IRequest, Inspectable
{
    use core\TStringProvider;
    use core\lang\TChainable;

    const GET     = 'get';
    const POST    = 'post';
    const PUT     = 'put';
    const PATCH   = 'patch';
    const HEAD    = 'head';
    const DELETE  = 'delete';
    const TRACE   = 'trace';
    const OPTIONS = 'options';
    const CONNECT = 'connect';
    const MERGE   = 'merge';

    public $url;
    public $method = self::GET;
    public $headers;
    public $cookies;
    public $options;

    protected $_ip;
    protected $_postData;
    protected $_bodyData;

    public static function fromString(string $string): link\http\IRequest
    {
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

        if ($protocol !== 'HTTP') {
            throw new link\http\UnexpectedValueException(
                'Protocol '.$protocol.' is not valid HTTP'
            );
        }

        $headers->setHttpVersion(trim(strtok('')));

        foreach ($lines as $line) {
            $headers->set(trim(strtok(trim($line), ':')), trim(strtok('')));
        }

        $output->setUrl($headers->get('host').$url);

        if ($headers->has('cookie')) {
            $output->setCookieData($headers->get('cookie'));
        }

        return $output;
    }

    public static function factory($url)
    {
        if ($url instanceof link\http\IRequest) {
            return $url;
        }

        return new self($url);
    }

    public function __construct($url=null, $envMode=false)
    {
        if ($url === true || $url === false) {
            $envMode = $url;
            $url = null;
        }

        if ($envMode) {
            $this->_importEnvironment($url);
        } else {
            $this->headers = new HeaderCollection();

            if ($url === null) {
                $url = 'localhost';
            }

            $this->setUrl($url);
        }

        if (!$this->cookies) {
            $this->cookies = new core\collection\Tree();
        }

        $this->options = new Options();
    }

    protected function _importEnvironment($url)
    {
        if (isset($_SERVER['REQUEST_METHOD'])) {
            $this->setMethod($_SERVER['REQUEST_METHOD']);
        } else {
            $this->setMethod('get');
        }

        $this->headers = HeaderCollection::fromEnvironment();

        if ($this->headers->has('cookie')) {
            $this->setCookieData($this->headers->get('cookie'));
        }

        if ($url === null) {
            $url = link\http\Url::fromEnvironment();
        }

        $this->setUrl($url);

        $this->_ip = core\lang\Future::factory(function () {
            $ips = '';

            if (isset($_SERVER['REMOTE_ADDR'])) {
                $ips .= $_SERVER['REMOTE_ADDR'].',';
            }

            if (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ips .= $_SERVER['HTTP_CLIENT_IP'].',';
            }

            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ips .= $_SERVER['HTTP_X_FORWARDED_FOR'];
            }

            $parts = explode(',', rtrim($ips, ','));

            while (!empty($parts)) {
                $ip = trim(array_shift($parts));

                try {
                    return new link\Ip($ip);
                } catch (link\InvalidArgumentException $e) {
                    if (empty($parts)) {
                        return new link\Ip('0.0.0.0');
                    }
                }
            }
        });


        if ($this->method === 'post') {
            $this->_bodyData = Atlas::$fs->newMemoryFile('input');

            $this->_postData = core\lang\Future::factory(function () {
                $payload = $this->getBodyDataString();
                $usePost = true;
                $output = null;

                switch (strtolower($this->headers->getBase('content-type'))) {
                    case 'application/x-www-form-urlencoded':
                        try {
                            $output = core\collection\Tree::fromArrayDelimitedString($payload);
                        } catch (\Throwable $e) {
                            $output = null;
                        }

                        break;

                    case 'application/json':
                        $usePost = false;
                        $output = flex\Json::stringToTree($payload);
                        break;
                }

                if ($usePost && $output && $output->isEmpty()) {
                    $output = null;
                }

                if (empty($output) && !empty($_POST) && $usePost) {
                    $output = new core\collection\Tree($_POST);
                }

                return $output;
            });
        }
    }

    public function __clone()
    {
        $this->url = clone $this->url;

        if ($this->_postData) {
            $this->_postData = clone $this->_postData;
        }

        $this->headers = clone $this->headers;
        $this->_ip = null;

        return $this;
    }

    public function __get($member)
    {
        switch ($member) {
            case 'post':
                return $this->getPostData();

            case 'ip':
                return $this->getIp();
        }
    }

    public function __set($member, $value)
    {
        switch ($member) {
            case 'post':
                return $this->setPostData($value);

            case 'ip':
                return $this->setIp($value);
        }
    }


    // Method
    public function setMethod($method)
    {
        $method = strtolower($method);

        switch ($method) {
            case self::POST:
            case self::GET:
            case self::PUT:
            case self::PATCH:
            case self::HEAD:
            case self::DELETE:
            case self::TRACE:
            case self::OPTIONS:
            case self::CONNECT:
            case self::MERGE:
                $this->method = $method;
                break;

            default:
                throw new link\http\UnexpectedValueException(
                    $method.' is not a valid request method'
                );
        }

        if ($this->method === self::POST && $this->_postData === null) {
            $this->setPostData(null);
        } elseif ($this->method !== self::POST) {
            $this->_postData = null;
        }

        return $this;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function isMethod(...$methods)
    {
        foreach ($methods as $method) {
            if (strtolower($method) == $this->method) {
                return true;
            }
        }

        return false;
    }

    public function isGet()
    {
        return $this->method === self::GET;
    }

    public function isPost()
    {
        return $this->method === self::POST;
    }

    public function isPut()
    {
        return $this->method === self::PUT;
    }

    public function isHead()
    {
        return $this->method === self::HEAD;
    }

    public function isDelete()
    {
        return $this->method === self::DELETE;
    }

    public function isTrace()
    {
        return $this->method === self::TRACE;
    }

    public function isOptions()
    {
        return $this->method === self::OPTIONS;
    }

    public function isConnect()
    {
        return $this->method === self::CONNECT;
    }

    public function isMerge()
    {
        return $this->method === self::MERGE;
    }


    // Url
    public function setUrl($url)
    {
        if ($url !== null) {
            $url = link\http\Url::factory($url);
        }

        $this->url = $url;
        $this->_ip = null;

        $this->headers->remove('Host');

        return $this;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function isSecure()
    {
        return $this->getUrl()->isSecure();
    }


    // Options
    public function setOptions(link\http\IRequestOptions $options)
    {
        $this->options = $options;
        return $this;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function withOptions($callback)
    {
        core\lang\Callback::call($callback, $this->options, $this);
        return $this;
    }



    // Headers
    public function setHeaders(core\collection\IHeaderMap $headers)
    {
        if (!$headers instanceof link\http\IRequestHeaderCollection) {
            throw new link\http\InvalidArgumentException(
                'Request headers must implement IRequestHeaderCollection'
            );
        }

        $this->headers = $headers;
        return $this;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function hasHeaders()
    {
        return $this->headers && !$this->headers->isEmpty();
    }

    public function withHeaders($callback)
    {
        core\lang\Callback::call($callback, $this->headers, $this);
        return $this;
    }

    public function isCachedByClient()
    {
        switch ($this->method) {
            case self::POST:
            case self::PUT:
            case self::DELETE:
            case self::OPTIONS:
            case self::CONNECT:
                return false;
        }

        return $this->headers->has('if-modified-since')
            || $this->headers->has('if-none-match');
    }


    // Post data
    public function setPostData($post)
    {
        if ($this->method !== 'post') {
            if ($post !== null) {
                throw new link\http\UnexpectedValueException(
                    'Post data can only be set when request method is POST'
                );
            }
        } elseif ($post !== null) {
            if (is_string($post)) {
                $post = core\collection\Tree::fromArrayDelimitedString($post);
            } elseif (!$post instanceof core\collection\ITree) {
                $post = new core\collection\Tree($post);
            }
        }

        $this->_postData = $post;

        return $this;
    }

    public function getPostData()
    {
        if ($this->_postData instanceof core\lang\IFuture) {
            $this->_postData = $this->_postData->getValue();
        }

        if (!$this->_postData && $this->method === 'post') {
            $this->_postData = new core\collection\Tree(null);
        }

        return $this->_postData;
    }

    public function hasPostData()
    {
        return $this->_postData !== null;
    }


    // Body
    public function setBodyData($body)
    {
        if (is_array($body)) {
            $body = implode("\r\n", $body);
        } elseif ($body instanceof File) {
            // do nothing?
        } elseif ($body !== null) {
            $body = (string)$body;
        }

        if (is_string($body)) {
            $body = Atlas::createTempFile($body);
        }

        $this->_bodyData = $body;
        return $this;
    }

    public function getRawBodyData()
    {
        return $this->_bodyData;
    }

    public function getBodyDataString(): string
    {
        if ($this->_bodyData instanceof File) {
            return $this->_bodyData->getContents();
        } elseif ($this->_bodyData instanceof Channel) {
            return $this->_bodyData->readAll();
        } else {
            return (string)$this->_bodyData;
        }
    }

    public function getBodyDataFile(): File
    {
        if ($this->_bodyData instanceof File) {
            return $this->_bodyData;
        }

        return Atlas::$fs->createTempFile($this->getBodyDataString());
    }

    public function hasBodyData()
    {
        return $this->_bodyData !== null;
    }


    // Cookies
    public function setCookieData($cookies)
    {
        if (empty($cookies)) {
            $this->cookies = new core\collection\Tree();
        } else {
            if (is_string($cookies)) {
                $cookies = core\collection\Tree::fromArrayDelimitedString(trim($cookies, ';'), ';');
            } elseif (!$query instanceof core\collection\ITree) {
                $cookies = new core\collection\Tree($cookies);
            }

            $this->cookies = $cookies;
        }

        return $this;
    }

    public function getCookies()
    {
        return $this->cookies;
    }

    public function hasCookieData()
    {
        return !$this->cookies->isEmpty();
    }

    public function setCookie($key, $value)
    {
        $this->cookies->set($key, $value);
        return $this;
    }

    public function getCookie($key, $default=null)
    {
        return $this->cookies->get($key, $default);
    }

    public function hasCookie($key)
    {
        return $this->cookies->has($key);
    }

    public function removeCookie($key)
    {
        $this->cookies->remove($key);
        return $this;
    }


    // Ip
    public function setIp($ip)
    {
        if (empty($ip)) {
            $this->_ip = null;
        } else {
            $this->_ip = link\Ip::factory($ip);
        }

        return $this;
    }

    public function getIp()
    {
        if ($this->_ip instanceof core\lang\IFuture) {
            $this->_ip = $this->_ip->getValue();
        }

        if ($this->_ip === null) {
            $this->_ip = $this->url->lookupIp();
        }

        return $this->_ip;
    }

    public function hasIp()
    {
        return $this->_ip !== null;
    }

    public function getSocketAddress()
    {
        return (string)$this->getIp().':'.$this->url->getPort();
    }


    // Sending
    public function prepareHeaders()
    {
        $url = $this->getUrl();
        $host = $url->getDomain();
        $port = $url->getPort();

        if (($url->isSecure() && $port !== 443)
        || (!$url->isSecure() && $port !== 80)) {
            $host = $host.':'.$port;
        }

        $headers = $this->getHeaders();
        $headers->set('host', $host);

        if ($url->hasCredentials() && !$this->options->hasCredentials()) {
            $this->options->setCredentials($url->getUsername(), $url->getPassword());
        }

        if ($this->_postData && !$this->_postData->isEmpty()) {
            if (!$headers->has('content-type')) {
                $headers->set('content-type', 'application/x-www-form-urlencoded');
            }

            switch ($headers->get('content-type')) {
                case 'application/x-www-form-urlencoded':
                    $this->setBodyData($this->_postData->toArrayDelimitedString());
                    break;

                case 'application/json':
                    $this->setBodyData(flex\Json::toString($this->_postData));
                    break;

                default:
                    Glitch::incomplete('Convert post data to raw body data');
            }
        }

        if ($this->_bodyData !== null && $headers->get('Transfer-Encoding') != 'chunked') {
            if ($this->_bodyData instanceof File) {
                $headers->set('Content-Length', $this->_bodyData->getSize());

                if (!$headers->has('Content-Type')) {
                    $headers->set('Content-Type', Atlas::$mime->detect($this->_bodyData->getPath()));
                }
            } else {
                $headers->set('Content-Length', strlen($this->_bodyData));
            }
        }

        if (!$this->cookies->isEmpty()) {
            $headers->set('cookie', $this->cookies->toArrayDelimitedString(';'));
        }

        return $this;
    }

    public function toString(): string
    {
        $output = $this->getHeaderString()."\r\n\r\n";

        if ($this->hasBodyData()) {
            $output .= $this->getBodyDataString();
        }

        return $output;
    }

    public function getHeaderString(array $skipKeys=null)
    {
        $this->prepareHeaders();

        $headers = $this->getHeaders();
        $output = strtoupper($this->method).' '.$this->url->getLocalString().' HTTP/'.$headers->getHttpVersion()."\r\n";
        $output .= $headers->toString($skipKeys);

        return $output;
    }


    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity->setProperties([
            'method' => $inspector($this->method),
            'url' => $inspector($this->url)
        ]);

        if ($ip = $this->getIp()) {
            $entity->setProperty('ip', $inspector($ip));
        }

        $entity->setProperty('headers', $inspector($this->getHeaders()));

        if ($this->method === 'post') {
            $entity->setProperty('post', $inspector($this->getPostData()));
        }

        if ($this->_bodyData !== null) {
            $entity->setProperty('body', $inspector($this->_bodyData));
        }

        if (!$this->cookies->isEmpty()) {
            $entity->setProperty('cookies', $inspector($this->cookies));
        }

        $entity->setProperty('options', $inspector($this->options));
    }
}

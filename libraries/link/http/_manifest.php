<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\http;

use df;
use df\core;
use df\link;
use df\arch;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}
class UnexpectedValueException extends \UnexpectedValueException implements IException {}


// Interfaces
interface IUrl extends core\uri\IGenericUrl, core\uri\ICredentialContainer, core\uri\ISecureSchemeContainer, core\uri\IDomainPortContainer {
    public function getLocalString();

    public function setDirectoryRequest(arch\IRequest $request=null);
    public function getDirectoryRequest();
}



interface IRequest extends core\IStringProvider, core\collection\IHeaderMapProvider, core\lang\IChainable {
    // Method
    public function setMethod($method);
    public function getMethod();
    public function isGet();
    public function isPost();
    public function isPut();
    public function isHead();
    public function isDelete();
    public function isTrace();
    public function isOptions();
    public function isConnect();
    public function isMerge();
    
    // Url
    public function setUrl($url);
    public function getUrl();
    public function isSecure();

    // Options
    public function setOptions(IRequestOptions $options);
    public function getOptions();
    public function withOptions($callback);
    
    // Headers
    public function isCachedByClient();
    public function withHeaders($callback);
    
    // Post
    public function setPostData($post);
    public function getPostData();
    public function getPostDataString();
    public function hasPostData();
    
    // Body
    public function setBodyData($data);
    public function getBodyData();
    public function getBodyDataString();
    public function hasBodyData();
    
    // Cookies
    public function setCookieData($cookies);
    public function getCookies();
    public function hasCookieData();
    public function setCookie($key, $value);
    public function getCookie($key, $default=null);
    public function hasCookie($key);
    public function removeCookie($key);
    
    // Ip
    public function setIp($ip);
    public function getIp();
    public function getSocketAddress();
}


interface IRequestOptions {
    public function import(IRequestOptions $options);
    public function sanitize();

    // Secure transport
    public function setSecureTransport($transport);
    public function getSecureTransport();

    // File path
    public function setDownloadFolder($path);
    public function getDownloadFolder();
    public function setDownloadFileName($name);
    public function getDownloadFileName();
    public function setDownloadFilePath($path);
    public function getDownloadFilePath();
    public function setDownloadStream(core\io\IWriter $stream=null);
    public function getDownloadStream();

    // Redirects
    public function setMaxRedirects($max);
    public function getMaxRedirects();
    public function shouldEnforceStrictRedirects($flag=null);
    public function shouldHideRedirectReferrer($flag=null);

    // Auth
    public function setCredentials($username, $password, $type=null);
    public function setUsername($username);
    public function getUsername();
    public function setPassword($password);
    public function getPassword();
    public function setAuthType($type);
    public function getAuthType();
    public function hasCredentials();

    // Cert
    public function setCertPath($path);
    public function getCertPath();
    public function setCertPassword($password);
    public function getCertPassword();

    // Cookies
    public function setCookieJar(ICookieJar $cookieJar=null);
    public function getCookieJar();

    // SSL Key
    public function setSslKeyPath($path);
    public function getSslKeyPath();
    public function setSslKeyPassword($password);
    public function getSslKeyPassword();
    public function shouldVerifySsl($flag=null);
    public function shouldAllowSelfSigned($flag=null);
    public function setCaBundlePath($path);
    public function getCaBundlePath();

    // Timeout
    public function setTimeout($duration);
    public function getTimeout();
    public function setConnectTimeout($duration);
    public function getConnectTimeout();
}


trait THeaderCollection {
    
    protected $_httpVersion = '1.1';
        
    public function setHttpVersion($version) {
        if(!preg_match('|^\d\.\d$|', $version)) {
            throw new link\http\UnexpectedValueException(
                $version.' is not a valid http version'
            );
        }
        
        $this->_httpVersion = $version;
        return $this;
    }
    
    public function getHttpVersion() {
        return $this->_httpVersion;
    }
}

interface IRequestHeaderCollection {
    public function setHttpVersion($version);
    public function getHttpVersion();
}




interface IResponse extends core\collection\IHeaderMapProvider, core\lang\IChainable {
    // Headers
    public function withHeaders($callback);
    public function getCookies();
    public function hasCookies();
    public function isOk();
    public function isRedirect();
    public function isForbidden();
    public function isMissing();
    public function isError();
    
    // Content
    public function getContent();
    public function getEncodedContent();
    public function getContentFileStream();
    public function onDispatchComplete();
    
    // Info
    public function getContentType();
    public function setContentType($contentType);
    public function getContentLength();
    public function getLastModified();
    
    // Attachment
    public function setAttachmentFileName($fileName);
    public function getAttachmentFileName();

    // Strings
    public function getResponseString();
}

interface IStringResponse extends IResponse {
    public function setLastModified(core\time\IDate $date);
}

interface IFileResponse extends IResponse {
    public function setFile($file, $checkPath=true);
    public function isStaticFile();
    public function getStaticFilePath();
}

interface IRedirectResponse extends IResponse {
    public function setUrl($url);
    public function getUrl();
    public function isPermanent($flag=null);
    public function isTemporary($flag=null);
    public function isAlternativeContent($flag=null);
}

interface IGeneratorResponse extends IResponse, core\io\IChunkReceiver {
    public function generate(core\io\IChannel $channel);
    public function getChannel();
}


trait TStringResponse {

    protected $_headers;
    protected $_cookies;

    public function isOk() {
        return true;
    }

    public function isRedirect() {
        return false;
    }

    public function isForbidden()  {
        return false;
    }

    public function isMissing()  {
        return false;
    }

    public function isError()  {
        return false;
    }


    public function getHeaders() {
        if(!$this->_headers) {
            $this->_headers = new link\http\response\HeaderCollection();
            $this->_headers->setCacheAccess('no-cache')
                ->canStoreCache(false)
                ->shouldRevalidateCache(true);
        }
        
        return $this->_headers;
    }
    
    public function setHeaders(core\collection\IHeaderMap $headers) {
        $this->_headers = $headers;
        return $this;
    }

    public function prepareHeaders() {
        if($this->hasCookies()) {
            $this->_cookies->applyTo($this->getHeaders());
        }

        $this->getHeaders()->set('Content-Type', $this->getContentType());
        return $this;
    }

    public function hasHeaders() {
        return $this->_headers && !$this->_headers->isEmpty();
    }

    public function withHeaders($callback) {
        core\lang\Callback::callArgs($callback, [$this->headers, $this]);
        return $this;
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
        
        return link\http\response\Base::encodeContent(
            $content, $contentEncoding, $transferEncoding
        );
    }

    public function getContentFileStream() {
        return new core\fs\MemoryFile($this->getContent(), $this->getContentType());
    }
    
    public function getContentLength() {
        return strlen($this->getContent());
    }
    
    public function setLastModified(core\time\IDate $date) {
        $this->getHeaders()->set('last-modified', $date);
        return $this;
    }
    
    public function getLastModified() {
        if($this->_headers && $this->_headers->has('last-modified')) {
            return core\time\Date::factory($this->_headers->get('last-modified'));
        }
        
        return new core\time\Date();
    }
    
    public function getHeaderString(array $skipKeys=null) {
        $this->prepareHeaders();
        return link\http\response\Base::buildHeaderString($this->_headers);
    }
    
    public function getResponseString() {
        $output = $this->getHeaderString()."\r\n\r\n";
        $output .= $this->getEncodedContent()."\r\n";
                  
        return $output;
    }

    public function setAttachmentFileName($fileName) {
        $this->getHeaders()->setAttachmentFileName($fileName);
        return $this;
    }

    public function getAttachmentFileName() {
        return $this->getHeaders()->getAttachmentFileName();
    }
}


interface IResponseAugmentor {
    public function setBaseUrl(IUrl $url=null);
    public function getBaseUrl();

    public function resetAll();
    public function resetCurrent();
    public function apply(IResponse $response);

    // Status
    public function setStatusCode($code);
    public function getStatusCode();

    // Headers
    public function addHeaderForCurrentRequest($name, $value);
    public function setHeaderForCurrentRequest($name, $value);
    public function removeHeaderForCurrentRequest($name);

    public function addHeaderForAnyRequest($name, $value);
    public function setHeaderForAnyRequest($name, $value);
    public function removeHeaderAnyRequest($name);

    // Cookies
    public function newCookie($name, $value, $expiry=null, $httpOnly=null, $secure=null);
    
    public function setCookieForCurrentRequest(ICookie $cookie);
    public function removeCookieForCurrentRequest($cookie);
    
    public function setCookieForAnyRequest(ICookie $cookie);
    public function removeCookieForAnyRequest($cookie);
    
    public function getCookieCollectionForCurrentRequest();
    public function getCookieCollectionForAnyRequest();
}

interface IResponseAugmentorProvider {
    public function getResponseAugmentor();
}



// Headers
interface IResponseHeaderCollection {
    // Version
    public function setHttpVersion($version);
    public function getHttpVersion();
    
    // Status
    public function setStatusCode($code);
    public function getStatusCode();
    public function hasErrorStatusCode();
    public function hasSuccessStatusCode();
    public function hasRedirectStatusCode();
    public function hasStatusCode($code);
    public function setStatusMessage($message);
    public function getStatusMessage();
    
    // Cache
    public function getCacheControl();
    public function setCacheAccess($access='private');
    public function getCacheAccess();
    public function canStoreCache($flag=null);
    public function canTransformCache($flag=null);
    public function shouldRevalidateCache($flag=null);
    public function shouldRevalidateProxyCache($flag=null);
    public function setCacheExpiration($duration=null);
    public function getCacheExpiration();
    public function getCacheStartDate(link\http\IRequest $request);
    public function isCached(link\http\IRequest $request);
    
    // Send
    public function send();
    public function setAttachmentFileName($fileName);
    public function getAttachmentFileName();
}


interface ICacheControl extends core\IStringProvider {
    public function setAccess($access);
    public function getAccess();
    public function canStore($flag=null);
    public function canTransform($flag=null);
    public function shouldRevalidate($flag=null);
    public function shouldRevalidateProxy($flag=null);
    public function setExpiration($duration=null);
    public function getExpiration();
    public function setSharedExpiration($duration=null);
    public function getSharedExpiration();
    public function clear();
}



// Cookies
interface ICookie extends core\IStringProvider {
    public function setName($name);
    public function getName();
    public function matchesName($name);
    public function setValue($value);
    public function getValue();
    public function setMaxAge($age=null);
    public function getMaxAge();
    public function setExpiryDate($date=null);
    public function getExpiryDate();
    public function isExpired();
    public function setDomain($domain);
    public function getDomain();
    public function matchesDomain($domain);
    public function setPath($path);
    public function getPath();
    public function matchesPath($path);
    public function setBaseUrl(link\http\IUrl $url);
    public function isSecure($flag=null);
    public function isHttpOnly($flag=null);
    public function toInvalidateString();
}

interface ICookieCollection extends core\IStringProvider {
    public function applyTo(IResponseHeaderCollection $headers);
    public function sanitize(IRequest $request);
    public function getRemoved();
}

interface ICookieJar {
    public function applyTo(IRequest $request);
    public function import(IResponse $response);
    public function set(ICookie $cookie);
    public function clear($domain=null, $path=null);
    public function clearSession();
}


// Upload
interface IUploadHandler extends core\io\IAcceptTypeProcessor, \Countable, \IteratorAggregate, \ArrayAccess {

    public function setAllowedExtensions(array $extensions);
    public function addAllowedExtensions(array $extensions);
    public function getAllowedExtensions();
    public function isExtensionAllowed($extension);



    public function setMaxFileSize($size);
    public function getMaxFileSize();

    public function uploadAll($destination, core\collection\IInputTree $inputCollection, $conflictAction=IUploadFile::RENAME);
    public function tempUploadAll(core\collection\IInputTree $inputCollection);
}

interface IUploadFile {

    const RENAME = 'rename';
    const OVERWRITE = 'overwrite';
    const HALT = 'halt';

    public function setFileName($fileName);
    public function getFileName();
    public function setExtension($extension);
    public function getExtension();
    public function setBaseName($baseName);
    public function getBaseName();

    public function getFieldName();
    public function getTempPath();
    public function getDestinationPath();
    public function getSize();
    public function getContentType();
    public function getPointer();

    public function isValid();
    public function isSuccess();
    public function getErrorCode();
    public function getErrorString();

    public function upload($destination, core\collection\IInputTree $inputNode, $conflictAction=IUploadFile::RENAME);
    public function tempUpload(core\collection\IInputTree $inputNode);
}



// Client
interface IRequestHandler {
    public function getTransport();

    public function get($url, $headers=null, $cookies=null);
    public function getFile($url, $destination, $fileName=null, $headers=null, $cookies=null);
    public function post($url, $data, $headers=null, $cookies=null);
    public function put($url, $data, $headers=null, $cookies=null);
    public function delete($url, $headers=null, $cookies=null);
    public function head($url, $headers=null, $cookies=null);
    public function options($url, $headers=null, $cookies=null);
    public function patch($url, $data, $headers=null, $cookies=null);

    public function newRequest($url, $method='get', $headers=null, $cookies=null, $body=null);
    public function promiseResponse(IRequest $request);
}


interface IClient extends IRequestHandler {
    public function newPool();

    public function promise($url, $headers=null, $cookies=null);
    public function promiseFile($url, $destination, $fileName=null, $headers=null, $cookies=null);
    public function promisePost($url, $data, $headers=null, $cookies=null);
    public function promisePut($url, $data, $headers=null, $cookies=null);
    public function promiseDelete($url, $headers=null, $cookies=null);
    public function promiseHead($url, $headers=null, $cookies=null);
    public function promiseOptions($url, $headers=null, $cookies=null);
    public function promisePatch($url, $data, $headers=null, $cookies=null);
    
    public function sendRequest(IRequest $request);
    
    public function prepareRequest(IRequest $request);
    public function prepareResponse(IResponse $response, IRequest $request);

    public function getDefaultUserAgent();
    public function setDefaultOptions(IRequestOptions $options=null);
    public function getDefaultOptions();
    public function hasDefaultOptions();

    public function setDefaultCookieJar(ICookieJar $cookieJar=null);
    public function getDefaultCookieJar();

    public static function getDefaultCaBundlePath();
}

interface IRequestPool extends IRequestHandler {
    public function getClient();

    public function setBatchSize($size);
    public function getBatchSize();

    public function sync();
    public function cancel();
}

interface ITransport {
    public function promiseResponse(IRequest $request, IClient $client);
}
<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\protocol\http;

use df;
use df\core;
use df\halo;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class UnexpectedValueException extends \UnexpectedValueException implements IException {}

class DebugPayload extends \Exception {
    
    public $response;
    
    public function __construct(IResponse $response) {
        $this->response = $response;
        parent::__construct('Debugging...');
    }
}




// Interfaces
interface IUrl extends core\uri\IGenericUrl, core\uri\ICredentialContainer, core\uri\ISecureSchemeContainer, core\uri\IDomainPortContainer {
    public function getLocalString();
}



interface IRequest extends core\IStringProvider {
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
    
    // Headers
    public function setHeaders(IRequestHeaderCollection $headers);
    public function getHeaders();
    public function isCachedByClient();
    
    // Post
    public function setPostData($post);
    public function getPostData();
    public function hasPostData();
    
    // Cookies
    public function setCookieData($cookies);
    public function getCookieData();
    public function hasCookieData();
    
    // Ip
    public function setIp($ip);
    public function getIp();
    public function getSocketAddress();
    
    // Strings
    public function prepareToSend();
    public function getHeaderString();
}


trait THeaderCollection {
    
    protected $_httpVersion = '1.1';
        
    public function setHttpVersion($version) {
        if(!preg_match('|^\d\.\d$|', $version)) {
            throw new halo\protocol\http\UnexpectedValueException(
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




interface IResponse extends core\IPayload {
    // Headers
    public function getHeaders();
    public function hasHeaders();
    public function getCookies();
    public function hasCookies();
    
    // Content
    public function getContent();
    public function getEncodedContent();
    public function onDispatchComplete();
    
    // Info
    public function getContentType();
    public function setContentType($contentType);
    public function getContentLength();
    public function getLastModified();
    
    // Strings
    public function getHeaderString();
    public function getResponseString();
}



interface IStringResponse extends IResponse {
    public function setLastModified(core\time\IDate $date);
}

interface IFileResponse extends IResponse {
    public function setFile($file, $checkPath=true);
    public function getContentFileStream();
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


interface IResponseAugmentor {
    public function resetCurrent();
    public function apply(IResponse $response);
    public function newCookie($name, $value);
    
    public function setCookieForCurrentRequest(IResponseCookie $cookie);
    public function removeCookieForCurrentRequest(IResponseCookie $cookie);
    
    public function setCookieForAnyRequest(IResponseCookie $cookie);
    public function removeCookieForAnyRequest(IResponseCookie $cookie);
    
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
    public function setCacheExpiration(core\time\IDuration $duration=null);
    public function getCacheExpiration();
    public function getCacheStartDate(halo\protocol\http\IRequest $request);
    public function isCached(halo\protocol\http\IRequest $request);
    
    // Send
    public function send();
    public function setAttachmentFilename($filename);
    public function getAttachmentFilename();
}


interface IResponseCacheControl extends core\IStringProvider {
    public function setAccess($access);
    public function getAccess();
    public function canStore($flag=null);
    public function canTransform($flag=null);
    public function shouldRevalidate($flag=null);
    public function shouldRevalidateProxy($flag=null);
    public function setExpiration(core\time\IDuration $duration=null);
    public function getExpiration();
    public function setSharedExpiration(core\time\IDuration $duration=null);
    public function getSharedExpiration();
    public function clear();
}

interface IResponseCookie extends core\IStringProvider {
    public function setName($name);
    public function getName();
    public function setValue($value);
    public function getValue();
    public function setMaxAge(core\time\IDuration $age=null);
    public function getMaxAge();
    public function setExpiryDate(core\time\IDate $date=null);
    public function getExpiryDate();
    public function setDomain($domain);
    public function getDomain();
    public function setPath($path);
    public function getPath();
    public function setBaseUrl(halo\protocol\http\IUrl $url);
    public function isSecure($flag=null);
    public function isHttpOnly($flag=null);
    public function toInvalidateString();
}

interface IResponseCookieCollection extends core\IStringProvider {
    public function applyTo(IResponseHeaderCollection $headers);
    public function getRemoved();
}
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
    public function getOrigin(): string;

    public function setDirectoryRequest(arch\IRequest $request=null);
    public function getDirectoryRequest();
}



interface IRequest extends core\IStringProvider, core\collection\IHeaderMapProvider, core\lang\IChainable {
    // Method
    public function setMethod($method);
    public function getMethod();
    public function isMethod(...$methods);
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
    public function hasPostData();

    // Body
    public function setBodyData($data);
    public function getRawBodyData();
    public function getBodyDataString(): string;
    public function getBodyDataFile(): core\fs\IFile;
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
    public function shouldEnforceStrictRedirects(bool $flag=null);
    public function shouldHideRedirectReferrer(bool $flag=null);

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
    public function shouldVerifySsl(bool $flag=null);
    public function shouldAllowSelfSigned(bool $flag=null);
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

    // Disposition
    public function setFileName($fileName, $isAttachment=null);
    public function getFileName();
    public function isAttachment(bool $flag=null);
    public function setAttachmentFileName($fileName);
    public function getAttachmentFileName();

    // Strings
    public function getResponseString();
}

interface IStreamResponse extends IResponse {
    public function setLastModified(core\time\IDate $date);
    public function getContentFileStream();
}

interface IAdaptiveStreamResponse extends IStreamResponse {
    public function setContentFileStream(core\io\IChannel $content);
    public function transferContentFileStream(core\io\IChannel $content);
}

interface IFileResponse extends IResponse {
    public function setFile($file, $checkPath=true);
    public function isStaticFile();
    public function getStaticFilePath();
}

interface IRedirectResponse extends IResponse {
    public function setUrl($url);
    public function getUrl();
    public function isPermanent(bool $flag=null);
    public function isTemporary(bool $flag=null);
    public function isAlternativeContent(bool $flag=null);
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
        core\lang\Callback::call($callback, $this->headers, $this);
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

    public function setFileName($fileName, $isAttachment=null) {
        $this->getHeaders()->setFileName($fileName, $isAttachment);
        return $this;
    }

    public function getFileName() {
        return $this->getHeaders()->getFileName();
    }

    public function isAttachment(bool $flag=null) {
        $output = $this->getHeaders()->isAttachment($flag);

        if($flag !== null) {
            return $this;
        }

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
    public function setStatusCode(?int $code);
    public function getStatusCode(): ?int;

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
    public function setStatusCode(int $code);
    public function getStatusCode(): int;
    public function hasErrorStatusCode();
    public function hasSuccessStatusCode();
    public function hasRedirectStatusCode();
    public function hasStatusCode(...$codes);
    public function setStatusMessage($message);
    public function getStatusMessage();

    // Cache
    public function getCacheControl();
    public function setCacheAccess($access='private');
    public function getCacheAccess();
    public function canStoreCache(bool $flag=null);
    public function canTransformCache(bool $flag=null);
    public function shouldRevalidateCache(bool $flag=null);
    public function shouldRevalidateProxyCache(bool $flag=null);
    public function setCacheExpiration($duration=null);
    public function getCacheExpiration();
    public function getCacheStartDate(link\http\IRequest $request);
    public function isCached(link\http\IRequest $request);

    // Disposition
    public function setFileName($fileName, $isAttachment=false);
    public function getFileName();
    public function isAttachment(bool $flag=null);
    public function setAttachmentFileName($fileName);
    public function getAttachmentFileName();

    // Send
    public function send();
}


interface ICacheControl extends core\IStringProvider {
    public function setAccess($access);
    public function getAccess();
    public function canStore(bool $flag=null);
    public function canTransform(bool $flag=null);
    public function shouldRevalidate(bool $flag=null);
    public function shouldRevalidateProxy(bool $flag=null);
    public function setExpiration($duration=null);
    public function getExpiration();
    public function setSharedExpiration($duration=null);
    public function getSharedExpiration();
    public function clear();
}



// Cookies
interface ICookie extends core\IStringProvider {
    public function setName($name);
    public function getName(): string;
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
    public function isSecure(bool $flag=null);
    public function isHttpOnly(bool $flag=null);
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

    public function isValid(): bool;
    public function isSuccess();
    public function getErrorCode();
    public function getErrorString();

    public function upload($destination, core\collection\IInputTree $inputNode, $conflictAction=IUploadFile::RENAME);
    public function tempUpload(core\collection\IInputTree $inputNode);
}



// Client
interface IRequestHandler {
    public function getTransport();

    public function get($url, $callback=null);
    public function getFile($url, $destination, $fileName=null, $callback=null);
    public function post($url, $data, $callback=null);
    public function put($url, $data, $callback=null);
    public function delete($url, $callback=null);
    public function head($url, $callback=null);
    public function options($url, $callback=null);
    public function patch($url, $data, $callback=null);

    public function newRequest($url, $method='get', $callback=null, $body=null);
    public function newGetRequest($url, $callback=null);
    public function newGetFileRequest($url, $destination, $fileName=null, $callback=null);
    public function newPostRequest($url, $data, $callback=null);
    public function newPutRequest($url, $data, $callback=null);
    public function newDeleteRequest($url, $callback=null);
    public function newHeadRequest($url, $callback=null);
    public function newOptionsRequest($url, $callback=null);
    public function newPatchRequest($url, $data, $callback=null);

    public function promiseResponse(IRequest $request);
}


interface IClient extends IRequestHandler {
    public function newPool();

    public function promise($url, $callback=null);
    public function promiseFile($url, $destination, $fileName=null, $callback=null);
    public function promisePost($url, $data, $callback=null);
    public function promisePut($url, $data, $callback=null);
    public function promiseDelete($url, $callback=null);
    public function promiseHead($url, $callback=null);
    public function promiseOptions($url, $callback=null);
    public function promisePatch($url, $data, $callback=null);

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
    public function syncBatch();
    public function cancel();
}

interface ITransport {
    public function promiseResponse(IRequest $request, IClient $client);
}

interface IAsyncTransport {
    public function addBatchRequest(IRequest $request, IClient $client, core\lang\IPromise $promise);
    public function syncBatch(IClient $client);
}

<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\link\http;

use DecodeLabs\Atlas;
use DecodeLabs\Atlas\File;
use DecodeLabs\Compass\Ip;
use DecodeLabs\Deliverance\Channel;
use DecodeLabs\Deliverance\DataReceiver;
use DecodeLabs\Exceptional;
use df\arch;
use df\core;
use df\link;
use Psr\Http\Message\ResponseInterface as PsrResponse;

interface IUrl extends
    core\uri\IGenericUrl,
    core\uri\ICredentialContainer,
    core\uri\ISecureSchemeContainer,
    core\uri\IDomainPortContainer
{
    public function getLocalString();
    public function getOrigin(): string;

    public function setDirectoryRequest(arch\IRequest $request = null);
    public function getDirectoryRequest();
}



interface IRequest extends
    core\IStringProvider,
    core\collection\IHeaderMapProvider,
    core\lang\IChainable
{
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
    public function getBodyDataFile(): File;
    public function hasBodyData();

    // Cookies
    public function setCookieData($cookies);
    public function getCookies();
    public function hasCookieData();
    public function setCookie($key, $value);
    public function getCookie($key, $default = null);
    public function hasCookie($key);
    public function removeCookie($key);

    // Ip
    public function setIp(
        Ip|string|null $ip
    );
    public function getIp(): Ip;
    public function getSocketAddress();
}


interface IRequestOptions
{
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
    public function setDownloadStream(Channel $stream = null);
    public function getDownloadStream();

    // Redirects
    public function setMaxRedirects($max);
    public function getMaxRedirects();
    public function shouldEnforceStrictRedirects(bool $flag = null);
    public function shouldHideRedirectReferrer(bool $flag = null);

    // Auth
    public function setCredentials($username, $password, $type = null);
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
    public function setCookieJar(ICookieJar $cookieJar = null);
    public function getCookieJar();

    // SSL Key
    public function setSslKeyPath($path);
    public function getSslKeyPath();
    public function setSslKeyPassword($password);
    public function getSslKeyPassword();
    public function shouldVerifySsl(bool $flag = null);
    public function shouldAllowSelfSigned(bool $flag = null);
    public function setCaBundlePath($path);
    public function getCaBundlePath();

    // Timeout
    public function setTimeout($duration);
    public function getTimeout();
    public function setConnectTimeout($duration);
    public function getConnectTimeout();
}


trait THeaderCollection
{
    protected $_httpVersion = '1.1';

    public function setHttpVersion($version)
    {
        if (!preg_match('|^\d\.\d$|', (string)$version)) {
            throw Exceptional::UnexpectedValue(
                $version . ' is not a valid http version'
            );
        }

        $this->_httpVersion = $version;
        return $this;
    }

    public function getHttpVersion()
    {
        return $this->_httpVersion;
    }
}

interface IRequestHeaderCollection
{
    public function setHttpVersion($version);
    public function getHttpVersion();


    // Negotiate
    public function negotiateLanguage(string ...$priorities): ?string;
}




interface IResponse extends
    core\collection\IHeaderMapProvider,
    core\lang\IChainable
{
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
    public function getJsonContent();
    public function getContentFileStream();
    public function onDispatchComplete();

    // Info
    public function getContentType();
    public function setContentType($contentType);
    public function getContentLength();
    public function getLastModified();

    // Disposition
    public function setFileName($fileName, $isAttachment = null);
    public function getFileName();
    public function isAttachment(bool $flag = null);
    public function setAttachmentFileName($fileName);
    public function getAttachmentFileName();

    // Psr
    public function toPsrResponse(): PsrResponse;
}

interface IStreamResponse extends IResponse
{
    public function setLastModified(core\time\IDate $date);
    public function getContentFileStream();
}

interface IAdaptiveStreamResponse extends IStreamResponse
{
    public function setContentFileStream(Channel $content);
    public function transferContentFileStream(Channel $content);
}

interface IFileResponse extends IResponse
{
    public function setFile($file, $checkPath = true);
    public function isStaticFile();
    public function getStaticFilePath();
}

interface IRedirectResponse extends IResponse
{
    public function setUrl($url);
    public function getUrl();
    public function isPermanent(bool $flag = null);
    public function isTemporary(bool $flag = null);
    public function isAlternativeContent(bool $flag = null);
}

interface IGeneratorResponse extends IResponse, DataReceiver
{
    public function setWriteCallback(callable $callback);
    public function generate(DataReceiver $channel);
    public function getChannel();
}


trait TStringResponse
{
    protected $_headers;
    protected $_cookies;

    public function isOk()
    {
        return true;
    }

    public function isRedirect()
    {
        return false;
    }

    public function isForbidden()
    {
        return false;
    }

    public function isMissing()
    {
        return false;
    }

    public function isError()
    {
        return false;
    }


    public function getHeaders()
    {
        if (!$this->_headers) {
            $this->_headers = new link\http\response\HeaderCollection();
            $this->_headers->setCacheAccess('no-cache')
                ->canStoreCache(false)
                ->shouldRevalidateCache(true);
        }

        return $this->_headers;
    }

    public function setHeaders(core\collection\IHeaderMap $headers)
    {
        $this->_headers = $headers;
        return $this;
    }

    public function prepareHeaders()
    {
        if ($this->hasCookies()) {
            $this->_cookies->applyTo($this->getHeaders());
        }

        $this->getHeaders()->set('Content-Type', $this->getContentType());
        return $this;
    }

    public function hasHeaders()
    {
        return $this->_headers && !$this->_headers->isEmpty();
    }

    public function withHeaders($callback)
    {
        core\lang\Callback::call($callback, $this->_headers, $this);
        return $this;
    }

    public function getCookies()
    {
        if (!$this->_cookies) {
            $this->_cookies = new CookieCollection();
        }

        return $this->_cookies;
    }

    public function hasCookies()
    {
        return $this->_cookies && !$this->_cookies->isEmpty();
    }

    public function getContentFileStream()
    {
        return Atlas::createTempFile($this->getContent());
    }

    public function getContentLength()
    {
        return strlen((string)$this->getContent());
    }

    public function setLastModified(core\time\IDate $date)
    {
        $this->getHeaders()->set('last-modified', $date);
        return $this;
    }

    public function getLastModified()
    {
        if ($this->_headers && $this->_headers->has('last-modified')) {
            return core\time\Date::factory($this->_headers->get('last-modified'));
        }

        return new core\time\Date();
    }

    public function getHeaderString(array $skipKeys = null)
    {
        $this->prepareHeaders();
        return link\http\response\Base::buildHeaderString($this->_headers);
    }

    public function setFileName($fileName, $isAttachment = null)
    {
        $this->getHeaders()->setFileName($fileName, $isAttachment);
        return $this;
    }

    public function getFileName()
    {
        return $this->getHeaders()->getFileName();
    }

    public function isAttachment(bool $flag = null)
    {
        $output = $this->getHeaders()->isAttachment($flag);

        if ($flag !== null) {
            return $this;
        }

        return $output;
    }

    public function setAttachmentFileName($fileName)
    {
        $this->getHeaders()->setAttachmentFileName($fileName);
        return $this;
    }

    public function getAttachmentFileName()
    {
        return $this->getHeaders()->getAttachmentFileName();
    }
}


interface IResponseAugmentor
{
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
    public function newCookie($name, $value, $expiry = null, $httpOnly = null, $secure = null);

    public function setCookieForCurrentRequest(ICookie $cookie);
    public function removeCookieForCurrentRequest($cookie);

    public function setCookieForAnyRequest(ICookie $cookie);
    public function removeCookieForAnyRequest($cookie);

    public function getCookieCollectionForCurrentRequest();
    public function getCookieCollectionForAnyRequest();
}



// Headers
interface IResponseHeaderCollection extends core\collection\IHeaderMap
{
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
    public function setCacheAccess($access = 'private');
    public function getCacheAccess();
    public function canStoreCache(bool $flag = null);
    public function canTransformCache(bool $flag = null);
    public function shouldRevalidateCache(bool $flag = null);
    public function shouldRevalidateProxyCache(bool $flag = null);
    public function setCacheExpiration($duration = null);
    public function getCacheExpiration();
    public function getCacheStartDate(link\http\IRequest $request);
    public function isCached(link\http\IRequest $request);

    // Disposition
    public function setFileName($fileName, $isAttachment = false);
    public function getFileName();
    public function isAttachment(bool $flag = null);
    public function setAttachmentFileName($fileName);
    public function getAttachmentFileName();

    // Send
    public function send();
}


interface ICacheControl extends core\IStringProvider
{
    public function setAccess($access);
    public function getAccess();
    public function canStore(bool $flag = null);
    public function canTransform(bool $flag = null);
    public function shouldRevalidate(bool $flag = null);
    public function shouldRevalidateProxy(bool $flag = null);
    public function setExpiration($duration = null);
    public function getExpiration();
    public function setSharedExpiration($duration = null);
    public function getSharedExpiration();
    public function clear();
}



// Cookies
interface ICookie extends core\IStringProvider
{
    public function setName($name);
    public function getName(): string;
    public function matchesName($name);
    public function setValue($value);
    public function getValue();
    public function setMaxAge($age = null);
    public function getMaxAge();
    public function setExpiryDate($date = null);
    public function getExpiryDate();
    public function isExpired();
    public function setDomain($domain);
    public function getDomain();
    public function matchesDomain($domain);
    public function setPath($path);
    public function getPath();
    public function matchesPath($path);
    public function isSecure(bool $flag = null);
    public function isHttpOnly(bool $flag = null);
    public function toInvalidateString();
}

interface ICookieCollection extends core\IStringProvider, core\collection\IMappedCollection
{
    public function applyTo(IResponseHeaderCollection $headers);
    public function sanitize(IRequest $request);
    public function getRemoved();
}

interface ICookieJar
{
    public function applyTo(IRequest $request);
    public function import(IResponse $response);
    public function set(ICookie $cookie);
    public function clear($domain = null, $path = null);
    public function clearSession();
}


// Upload
interface IUploadHandler extends core\lang\IAcceptTypeProcessor, \Countable, \IteratorAggregate, \ArrayAccess
{
    public function setAllowedExtensions(array $extensions);
    public function addAllowedExtensions(array $extensions);
    public function getAllowedExtensions();
    public function isExtensionAllowed($extension);



    public function setMaxFileSize($size);
    public function getMaxFileSize();

    public function uploadAll($destination, core\collection\IInputTree $inputCollection, $conflictAction = IUploadFile::RENAME);
    public function tempUploadAll(core\collection\IInputTree $inputCollection);
}

interface IUploadFile
{
    public const RENAME = 'rename';
    public const OVERWRITE = 'overwrite';
    public const HALT = 'halt';

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

    public function upload($destination, core\collection\IInputTree $inputNode, $conflictAction = IUploadFile::RENAME);
    public function tempUpload(core\collection\IInputTree $inputNode);
}

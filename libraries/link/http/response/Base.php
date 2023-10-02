<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\link\http\response;

use DecodeLabs\Atlas;
use DecodeLabs\Exceptional;
use df\core;

use df\flex;
use df\link;

abstract class Base implements link\http\IResponse
{
    use core\lang\TChainable;

    public $headers;
    public $cookies;

    public function __construct(link\http\IResponseHeaderCollection $headers = null)
    {
        if (!$headers) {
            $headers = new HeaderCollection();
        }

        $this->headers = $headers;
        $this->cookies = new link\http\CookieCollection($headers);
    }

    public function setHeaders(core\collection\IHeaderMap $headers)
    {
        if (!$headers instanceof link\http\IResponseHeaderCollection) {
            throw Exceptional::InvalidArgument(
                'Request headers must implement IResponseHeaderCollection'
            );
        }

        $this->headers = $headers;
        return $this;
    }

    public function hasHeaders()
    {
        return !$this->headers->isEmpty();
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function withHeaders($callback)
    {
        core\lang\Callback::call($callback, $this->headers, $this);
        return $this;
    }

    public function getCookies()
    {
        return $this->cookies;
    }

    public function hasCookies()
    {
        return !$this->cookies->isEmpty();
    }

    public function isOk()
    {
        return $this->headers->hasSuccessStatusCode();
    }

    public function isRedirect()
    {
        return $this->headers->hasRedirectStatusCode();
    }

    public function isForbidden()
    {
        return $this->headers->hasStatusCode(403);
    }

    public function isMissing()
    {
        return $this->headers->hasStatusCode(404);
    }

    public function isError()
    {
        return $this->headers->hasErrorStatusCode();
    }

    public function getJsonContent()
    {
        $content = $this->getContent();

        if (!strlen((string)$content)) {
            throw Exceptional::Runtime(
                'Empty json response'
            );
        }

        $data = flex\Json::fromString($content);

        if ($data === false || $data === null) {
            throw Exceptional::Runtime(
                'Invalid json response: ' . $content
            );
        }

        return new core\collection\Tree($data);
    }

    public function getContentFileStream()
    {
        return Atlas::createTempFile($this->getContent());
    }

    public function onDispatchComplete()
    {
    }

    // Info
    public function setContentType($contentType)
    {
        if ($contentType === null) {
            $contentType = 'text/plain';
        }

        $this->headers->set('content-type', $contentType);
        return $this;
    }

    public function getContentType()
    {
        return $this->headers->get('content-type');
    }

    public function getContentLength()
    {
        return $this->headers->get('content-length');
    }

    public function getLastModified()
    {
        if (!$this->headers->has('last-modified')) {
            $this->headers->set('last-modified', new core\time\Date());
        }

        return core\time\Date::factory($this->headers->get('last-modified'));
    }

    // Disposition
    public function setFileName($fileName, $isAttachment = null)
    {
        $this->headers->setFileName($fileName, $isAttachment);
        return $this;
    }

    public function getFileName()
    {
        return $this->headers->getFileName();
    }

    public function isAttachment(bool $flag = null)
    {
        $output = $this->headers->isAttachment($flag);

        if ($flag !== null) {
            return $this;
        }

        return $output;
    }

    public function setAttachmentFileName($fileName)
    {
        $this->headers->setAttachmentFileName($fileName);
        return $this;
    }

    public function getAttachmentFileName()
    {
        return $this->headers->getAttachmentFileName();
    }

    // Strings
    public function getHeaderString(array $skipKeys = null)
    {
        $this->prepareHeaders();

        return self::buildHeaderString($this->headers);
    }

    public function prepareHeaders()
    {
        if (!$this->cookies->isEmpty()) {
            $this->cookies->applyTo($this->headers);
        }

        return $this;
    }

    public static function buildHeaderString(link\http\IResponseHeaderCollection $headers = null)
    {
        $headerString = '';

        if ($headers) {
            $version = $headers->getHttpVersion();
            $code = $headers->getStatusCode();
            $message = $headers->getStatusMessage();

            if (!$headers->isEmpty()) {
                $headerString = "\r\n" . $headers->toString();
            }
        } else {
            $version = '1.1';
            $code = '200';
            $message = 'OK';
        }

        return 'HTTP/' . $version . ' ' . $code . ' ' . $message . $headerString;
    }
}

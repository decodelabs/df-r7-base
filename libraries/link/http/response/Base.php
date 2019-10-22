<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\http\response;

use df;
use df\core;
use df\link;
use df\flex;

use DecodeLabs\Atlas;
use DecodeLabs\Glitch;

abstract class Base implements link\http\IResponse
{
    use core\lang\TChainable;

    public $headers;
    public $cookies;

    public static function fromString(string $string): link\http\response\Stream
    {
        $content = null;
        $output = self::fromHeaderString($string, $content);
        $headers = $output->getHeaders();

        if ($headers->has('transfer-encoding')) {
            switch (strtolower($headers->get('transfer-encoding'))) {
                case 'chunked':
                    $content = self::decodeChunked($content);
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


        if ($headers->has('content-encoding')) {
            switch (strtolower($headers->get('content-encoding'))) {
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
                    throw new link\http\RuntimeException(
                        ucfirst($headers->get('content-encoding')).' response compression is not available'
                    );
            }
        }

        $output->setContent($content);
        return $output;
    }

    public static function fromHeaderString(string $string, &$content=null): link\http\response\Stream
    {
        $output = new Stream();
        $output->headers = HeaderCollection::fromResponseString($string, $content);
        $output->cookies->import($output->headers);

        return $output;
    }

    public static function decodeChunked(&$content)
    {
        $output = '';

        while (true) {
            $content = ltrim($content);

            if (!isset($content{0}) || !preg_match("/^([\da-fA-F]+)[^\r\n]*\r\n/sm", $content, $matches)) {
                throw new link\http\UnexpectedValueException('The body does not appear to be chunked properly');
            }

            $length = hexdec(trim($matches[1]));
            $cut = strlen($matches[0]);
            $output .= substr($content, $cut, $length);
            $content = substr($content, $cut + $length + 2);

            if ($length == 0) {
                break;
            }
        }

        $content = trim($content);
        return $output;
    }

    public static function encodeChunked($content)
    {
        $chunkSize = 32;
        $output = '';

        while (isset($content{0})) {
            $current = substr($content, 0, $chunkSize);
            $content = substr($content, $chunkSize);

            $output .= dechex(strlen($current))."\r\n";
            $output .= $current."\r\n";
        }

        $output .= '0'."\r\n";
        return $output;
    }

    public static function decodeDeflate($content)
    {
        if (!function_exists('gzuncompress')) {
            throw new link\http\RuntimeException(
                'Gzip response compression is not available'
            );
        }

        $header = unpack('n', substr($content, 0, 2));

        if ($header[1] % 31 == 0) {
            return gzuncompress($content);
        } else {
            return gzinflate($content);
        }
    }

    public static function encodeDeflate($content)
    {
        Glitch::incomplete();
    }

    public static function decodeGzip($content)
    {
        if (!function_exists('gzinflate')) {
            throw new link\http\RuntimeException(
                'Gzip inflate response compression is not available'
            );
        }

        return gzinflate(substr($content, 10));
    }

    public static function encodeGzip($content)
    {
        Glitch::incomplete();
    }

    public function __construct(link\http\IResponseHeaderCollection $headers=null)
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
            throw new link\http\InvalidArgumentException(
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

        if (!strlen($content)) {
            throw new link\http\RuntimeException(
                'Empty json response'
            );
        }

        $data = flex\Json::fromString($content);

        if ($data === false || $data === null) {
            throw new link\http\RuntimeException(
                'Invalid json response: '.$content
            );
        }

        return new core\collection\Tree($data);
    }

    public function getEncodedContent()
    {
        $content = $this->getContent();

        if (empty($content)) {
            return $content;
        }

        $contentEncoding = $this->headers->get('content-encoding');
        $transferEncoding = $this->headers->get('transfer-encoding');

        if (!$contentEncoding && !$transferEncoding) {
            return $content;
        }

        return self::encodeContent(
            $content, $contentEncoding, $transferEncoding
        );
    }

    public static function encodeContent($content, $contentEncoding, $transferEncoding)
    {
        if ($contentEncoding !== null) {
            switch (strtolower($contentEncoding)) {
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
                    throw new link\http\RuntimeException(
                        ucfirst($contentEncoding).' response compression is not available'
                    );
            }
        }

        if ($transferEncoding !== null) {
            switch (strtolower($transferEncoding)) {
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

    public function getContentFileStream()
    {
        return Atlas::$fs->createTempFile($this->getContent());
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
        $headers = $this->headers;

        if (!$headers->has('content-length')) {
            $headers->set('content-length', strlen($this->getEncodedContent()));
        }

        return $headers->get('content-length');
    }

    public function getLastModified()
    {
        if (!$this->headers->has('last-modified')) {
            $this->headers->set('last-modified', new core\time\Date());
        }

        return core\time\Date::factory($this->headers->get('last-modified'));
    }

    // Disposition
    public function setFileName($fileName, $isAttachment=null)
    {
        $this->headers->setFileName($fileName, $isAttachment);
        return $this;
    }

    public function getFileName()
    {
        return $this->headers->getFileName();
    }

    public function isAttachment(bool $flag=null)
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
    public function getResponseString()
    {
        $output = $this->getHeaderString()."\r\n\r\n";
        $output .= $this->getEncodedContent()."\r\n";

        return $output;
    }

    public function getHeaderString(array $skipKeys=null)
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

    public static function buildHeaderString(link\http\IResponseHeaderCollection $headers=null)
    {
        $headerString = '';

        if ($headers) {
            $version = $headers->getHttpVersion();
            $code = $headers->getStatusCode();
            $message = $headers->getStatusMessage();

            if (!$headers->isEmpty()) {
                $headerString = "\r\n".$headers->toString();
            }
        } else {
            $version = '1.1';
            $code = '200';
            $message = 'OK';
        }

        return 'HTTP/'.$version.' '.$code.' '.$message.$headerString;
    }
}

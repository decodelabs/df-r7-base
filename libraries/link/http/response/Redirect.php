<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\link\http\response;

use DecodeLabs\Exceptional;
use DecodeLabs\Harvest;
use df\link;
use Psr\Http\Message\ResponseInterface as PsrResponse;

class Redirect extends Base implements link\http\IRedirectResponse
{
    protected $_url;

    public function __construct($url, link\http\IResponseHeaderCollection $headers = null)
    {
        parent::__construct($headers);
        $this->headers->setStatusCode(302);
        $this->setUrl($url);
        $this->setContentType('text/html');
    }

    public function setUrl($url)
    {
        $url = link\http\Url::factory($url);

        if (!$url->isAbsolute()) {
            throw Exceptional::InvalidArgument(
                'Redirect URL must include host'
            );
        }

        $this->_url = $url;
        $this->headers->set('location', $url);

        return $this;
    }

    public function getUrl()
    {
        return $this->_url;
    }

    public function isPermanent(bool $flag = null)
    {
        if ($flag !== null) {
            if ($flag) {
                $this->headers->setStatusCode(301);
            } else {
                $this->headers->setStatusCode(302);
            }

            return $this;
        }

        return $this->headers->getStatusCode() == 301;
    }

    public function isTemporary(bool $flag = null)
    {
        if ($flag !== null) {
            if ($flag) {
                $this->headers->setStatusCode(307);
            } else {
                $this->headers->setStatusCode(302);
            }

            return $this;
        }

        return $this->headers->getStatusCode() == 307;
    }

    public function isAlternativeContent(bool $flag = null)
    {
        if ($flag !== null) {
            if ($flag) {
                $this->headers->setStatusCode(303);
            } else {
                $this->headers->setStatusCode(302);
            }

            return $this;
        }

        return $this->headers->getStatusCode() == 303;
    }

    public function getContent()
    {
        $url = $this->_url->toString(false);

        return
            '<html><head><title>Redirecting...</title></head><body>' .
            '<p>Redirecting to <a href="' . $url . '">' . $url . '</a></p>' .
            '</body></html>';
    }



    public function toPsrResponse(): PsrResponse
    {
        if ($this->hasCookies()) {
            $this->getCookies()->applyTo($this->headers);
        }

        return Harvest::redirect(
            $this->_url,
            $this->headers->getStatusCode(),
            $this->headers->toArray()
        );
    }
}

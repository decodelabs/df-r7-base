<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\link\http\response;

use df\core;
use df\link;
use Psr\Http\Message\ResponseInterface as PsrResponse;

class Augmentor implements link\http\IResponseAugmentor
{
    protected $_router;
    protected $_cookiePath;
    protected $_cookieDomain;

    protected $_globalHeaders = [];
    protected $_currentHeaders = [];

    protected $_globalCookies;
    protected $_currentCookies;

    protected $_statusCode;

    public function __construct(core\app\http\Router $router = null)
    {
        $this->_router = $router;

        if ($router) {
            $rootUrl = $router->getRootUrl();

            // Domain
            if ($router->countMaps() > 1) {
                $baseUrl = $router->getBaseUrl();

                $rootDomain = $rootUrl->getDomain();
                $baseDomain = $baseUrl->getDomain();

                if (substr($rootDomain, 0, 4) == 'www.') {
                    $rootDomain = substr($rootDomain, 4);
                }
                if (substr($baseDomain, 0, 4) == 'www.') {
                    $baseDomain = substr($baseDomain, 4);
                }

                if (substr($baseDomain, -strlen($rootDomain)) == $rootDomain) {
                    $this->_cookieDomain = '.' . $rootDomain;
                }
            }


            // Path
            $path = clone $rootUrl->getPath();
            $this->_cookiePath = $path->isAbsolute(true)->toString();
        }

        $this->resetAll();
    }

    public function resetAll()
    {
        $this->_globalHeaders = [];
        $this->_globalCookies = new link\http\CookieCollection();
        $this->resetCurrent();
        return $this;
    }

    public function resetCurrent()
    {
        $this->_currentHeaders = $this->_globalHeaders;
        $this->_currentCookies = clone $this->_globalCookies;
        $this->_statusCode = null;
        return $this;
    }

    public function apply(link\http\IResponse $response)
    {
        $headers = $response->getHeaders();

        if ($this->_statusCode !== null) {
            $headers->setStatusCode($this->_statusCode);
        }

        foreach ($this->_currentHeaders as $set) {
            switch ($set[0]) {
                case '+':
                    $headers->add($set[1], $set[2]);
                    break;

                case '*':
                    $headers->set($set[1], $set[2]);
                    break;

                case '-':
                    $headers->remove($set[1]);
                    break;
            }
        }

        $response->getCookies()->import($this->_currentCookies);
        return $this;
    }

    public function applyPsr(
        PsrResponse $response,
    ): PsrResponse {
        // Status
        if ($this->_statusCode !== null) {
            $response = $response->withStatus($this->_statusCode);
        }

        // Headers
        foreach ($this->_currentHeaders as $set) {
            switch ($set[0]) {
                case '+':
                    $response = $response->withAddedHeader($set[1], $set[2]);
                    break;

                case '*':
                    $response = $response->withHeader($set[1], $set[2]);
                    break;

                case '-':
                    $response = $response->withoutHeader($set[1]);
                    break;
            }
        }

        // Cookies
        $response = $this->_currentCookies->applyToPsr($response);

        return $response;
    }


    // Status
    public function setStatusCode(?int $code)
    {
        if (!HeaderCollection::isValidStatusCode($code)) {
            $code = null;
        }

        $this->_statusCode = $code;
        return $this;
    }

    public function getStatusCode(): ?int
    {
        return $this->_statusCode;
    }


    // Headers
    public function addHeaderForCurrentRequest($name, $value)
    {
        $this->_currentHeaders[] = ['+', $name, $value];
        return $this;
    }

    public function setHeaderForCurrentRequest($name, $value)
    {
        $this->_currentHeaders[] = ['*', $name, $value];
        return $this;
    }

    public function removeHeaderForCurrentRequest($name)
    {
        $this->_currentHeaders[] = ['-', $name];
        return $this;
    }

    public function addHeaderForAnyRequest($name, $value)
    {
        $this->_currentHeaders[] = ['+', $name, $value];
        $this->_globalHeaders[] = ['+', $name, $value];
        return $this;
    }

    public function setHeaderForAnyRequest($name, $value)
    {
        $this->_currentHeaders[] = ['*', $name, $value];
        $this->_globalHeaders[] = ['*', $name, $value];
        return $this;
    }

    public function removeHeaderAnyRequest($name)
    {
        $this->_currentHeaders[] = ['-', $name];
        $this->_globalHeaders[] = ['-', $name];
        return $this;
    }


    // Cookies
    public function newCookie($name, $value, $expiry = null, $httpOnly = null, $secure = null)
    {
        $output = new link\http\Cookie($name, $value, $expiry, $httpOnly, $secure);

        if ($this->_cookieDomain !== null) {
            $output->setDomain($this->_cookieDomain);
        }

        if ($this->_cookiePath !== null) {
            $output->setPath($this->_cookiePath);
        }

        return $output;
    }



    public function setCookieForCurrentRequest(link\http\ICookie $cookie)
    {
        $this->_currentCookies->set($cookie);
        return $this;
    }

    public function removeCookieForCurrentRequest($cookie)
    {
        $this->_currentCookies->remove($cookie);
        return $this;
    }


    public function setCookieForAnyRequest(link\http\ICookie $cookie)
    {
        $this->_globalCookies->set($cookie);
        $this->_currentCookies->set($cookie);
        return $this;
    }

    public function removeCookieForAnyRequest($cookie)
    {
        $this->_globalCookies->remove($cookie);
        $this->_currentCookies->remove($cookie);
        return $this;
    }

    public function getCookieCollectionForCurrentRequest()
    {
        return $this->_currentCookies;
    }

    public function getCookieCollectionForAnyRequest()
    {
        return $this->_globalCookies;
    }
}

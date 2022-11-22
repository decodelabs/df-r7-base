<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\link\http\cookieJar;

use DecodeLabs\Exceptional;

use DecodeLabs\Glitch\Dumpable;
use df\link;

class Memory implements link\http\ICookieJar, Dumpable
{
    protected $_cookies = [];

    public function __construct(array $cookies = null)
    {
        if ($cookies) {
            foreach ($cookies as $cookie) {
                if (is_string($cookie)) {
                    $cookie = link\http\Cookie::fromString($cookie);
                } elseif (!$cookie instanceof link\http\ICookie) {
                    throw Exceptional::InvalidArgument(
                        'Invalid cookie'
                    );
                }

                $this->set($cookie);
            }
        }
    }

    public function applyTo(link\http\IRequest $request)
    {
        if (empty($this->_cookies)) {
            return $this;
        }

        $url = $request->getUrl();
        $path = (string)$url->getPath();
        $domain = (string)$url->getDomain();
        $isSecure = $url->isSecure();

        foreach ($this->_cookies as $cookie) {
            if ($cookie->matchesPath($path)
            && $cookie->matchesDomain($domain)
            && !$cookie->isExpired()
            && $cookie->isSecure() == $isSecure) {
                $request->getCookies()->set($cookie->getName(), $cookie->getValue());
            }
        }

        return $this;
    }

    public function import(link\http\IResponse $response)
    {
        $cookies = $response->getCookies();

        foreach ($cookies->toArray() as $cookie) {
            $this->set($cookie);
        }

        foreach ($cookies->getRemoved() as $cookie) {
            $this->set($cookie);
        }

        return $this;
    }

    public function set(link\http\ICookie $cookie)
    {
        $value = $cookie->getValue();

        if ($value === '' || $value === null) {
            $this->clear(
                $cookie->getDomain(),
                $cookie->getPath(),
                $cookie->getName()
            );

            return $this;
        }

        foreach ($this->_cookies as $i => $test) {
            if ($cookie->getName() != $test->getName()
            || $cookie->getDomain() != $test->getDomain()
            || $cookie->getPath() != $test->getPath()) {
                continue;
            }

            $newExpiry = $cookie->getExpiryDate();
            $oldExpiry = $test->getExpiryDate();

            if (
                $newExpiry &&
                (
                    !$oldExpiry ||
                    $newExpiry->gt($oldExpiry)
                )
            ) {
                unset($this->_cookies[$i]);
                continue;
            }

            if ($cookie->getValue() != $test->getValue()) {
                unset($this->_cookies[$i]);
                continue;
            }

            return $this;
        }

        $this->_cookies[] = clone $cookie;
        return $this;
    }

    public function clear($domain = null, $path = null, $name = null)
    {
        if (!$domain) {
            $this->_cookies = [];
            return $this;
        }

        $this->_cookies = array_filter(
            $this->_cookies,
            function ($cookie) use ($domain, $path, $name) {
                return !($cookie->matchesDomain($domain)
                    && $cookie->matchesPath($path)
                    && $cookie->matchesName($name));
            }
        );

        return $this;
    }

    public function clearSession()
    {
        $this->_cookies = array_filter(
            $this->_cookies,
            function ($cookie) {
                return (bool)$cookie->getExpiryDate();
            }
        );

        return $this;
    }

    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        foreach ($this->_cookies as $cookie) {
            yield 'value:' . $cookie->getName() => $cookie->toString();
        }
    }
}

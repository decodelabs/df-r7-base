<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\link\http;

use DecodeLabs\Genesis;
use DecodeLabs\R7\Legacy;

use df\arch;
use df\core;

class Url extends core\uri\Url implements IUrl
{
    use core\uri\TUrl_CredentialContainer;
    use core\uri\TUrl_DomainPortContainer;

    protected $_directoryRequest;

    public static function fromDirectoryRequest(arch\IRequest $request, $scheme, core\app\http\Router_Map $map = null, arch\IRequest $routedRequest = null)
    {
        if ($request->isJustFragment()) {
            $output = new self('#' . $request->getFragment());
        } else {
            $path = null;
            $area = 'front';

            $output = new static();
            $output->_scheme = $scheme;

            if ($request->_path) {
                $path = clone $request->_path;
                $area = $path->get(0);
                $mappedArea = null;

                if ($map && $map->area != '*') {
                    if ($routedRequest) {
                        $mappedArea = $routedRequest->getArea();
                    } else {
                        $mappedArea = $area;
                    }
                }

                if ($area == $request::AREA_MARKER . $request::DEFAULT_AREA
                || ($mappedArea && $area == $request::AREA_MARKER . $mappedArea)) {
                    $path->shift();
                }

                if ($path->getBasename() == 'index') {
                    $path->shouldAddTrailingSlash(true)->pop();
                }

                if ($map && !empty($map->path)) {
                    $path->unshift($map->path);
                }
            } elseif ($map && !empty($map->path)) {
                $path = new core\uri\Path($map->path);
                $path->shouldAddTrailingSlash(true);
            }

            if ($map) {
                $domain = $map->domain;

                if (
                    $map->isWild &&
                    $map->area != '*'
                ) {
                    $wildArea = $map->area;

                    $sub = $request->query[$wildArea];
                    unset($request->query->{$wildArea});

                    if (strlen((string)$sub)) {
                        $domain = $sub . '.' . $domain;
                    } elseif (isset($_SERVER['HTTP_HOST']) && stristr($_SERVER['HTTP_HOST'], '.' . $domain)) {
                        $domain = $_SERVER['HTTP_HOST'];
                    }

                    $parts = explode(':', $domain);
                    $domain = array_shift($parts);
                }

                $output->_domain = $domain;
                $output->_port = $map->port;
            }


            if (!empty($path)) {
                $output->_path = $path;
            }

            if (!empty($request->_query)) {
                $output->_query = $request->_query;

                if (
                    isset($output->_query->cts) &&
                    $output->_query->cts->getValue() == null &&
                    Genesis::$build->shouldCacheBust()
                ) {
                    $output->query->cts = Genesis::$build->getCacheBuster();
                }
            }

            if (!empty($request->_fragment)) {
                $output->_fragment = $request->_fragment;
            }
        }

        $output->_directoryRequest = $routedRequest ?? $request;
        return $output;
    }

    public static function fromEnvironment()
    {
        if (isset($_SERVER['HTTPS']) && !strcasecmp($_SERVER['HTTPS'], 'on')) {
            $url = 'https';
        } else {
            $url = 'http';
        }

        if (isset($_SERVER['HTTP_X_ORIGINAL_HOST'])) {
            $url .= '://' . $_SERVER['HTTP_X_ORIGINAL_HOST'] . ':' . $_SERVER['SERVER_PORT'];
        } elseif (isset($_SERVER['HTTP_HOST'])) {
            $url .= '://' . $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'];
        } else {
            $url .= '://' . gethostname();
        }

        if (isset($_SERVER['REQUEST_URI'])) {
            $req = ltrim((string)$_SERVER['REQUEST_URI'], '/');
        } elseif (isset($_SERVER['argv'][2])) {
            $req = $_SERVER['argv'][2];
        } else {
            $req = '';
        }

        $req = explode('?', $req, 2);
        $req[0] = urldecode($req[0]);

        $url .= '/' . implode('?', $req);
        return new static($url);
    }

    public static function factory($url)
    {
        if ($url instanceof IUrl) {
            return $url;
        }

        return new static($url);
    }

    public function import($url = '')
    {
        if (empty($url)) {
            return $this;
        }

        $this->reset();

        if ($url instanceof self) {
            $this->_scheme = $url->_scheme;
            $this->_username = $url->_username;
            $this->_password = $url->_password;
            $this->_domain = $url->_domain;
            $this->_port = $url->_port;

            if ($url->_path !== null) {
                $this->_path = clone $url->_path;
            }

            if ($url->_query !== null) {
                $this->_query = clone $url->_query;
            }

            $this->_fragment = $url->_fragment;

            return $this;
        }


        // Fragment
        $parts = explode('#', $url, 2);
        $url = (string)array_shift($parts);
        $this->setFragment(array_shift($parts));

        // Query
        $parts = explode('?', $url, 2);
        $url = (string)array_shift($parts);
        $this->setQuery(array_shift($parts));

        // Scheme
        if (substr($url, 0, 2) == '//') {
            $url = ltrim((string)$url, '/');
            $this->_scheme = null;
        } else {
            $parts = explode('://', $url, 2);
            $url = (string)array_pop($parts);
            $this->setScheme(array_shift($parts));
        }

        $url = urldecode($url);
        $path = explode('/', $url);

        if (substr($url, 0, 1) == '/') {
            unset($path[0]);

            if (Genesis::$kernel->getMode() === 'Http') {
                $requestUrl = Legacy::$http->getRequest()->getUrl();

                $this->_scheme = $requestUrl->getScheme();
                $this->_username = $requestUrl->getUsername();
                $this->_password = $requestUrl->getPassword();
                $this->_domain = $requestUrl->getDomain();
                $this->_port = $requestUrl->getPort();
            } elseif (isset($_SERVER['HTTP_HOST'])) {
                $this->_domain = $_SERVER['HTTP_HOST'];
                $this->_port = $_SERVER['SERVER_PORT'];
            }
        } else {
            $domain = (string)array_shift($path);

            // Credentials
            $credentials = explode('@', $domain, 2);
            $domain = (string)array_pop($credentials);
            $credentials = (string)array_shift($credentials);

            if (!empty($credentials)) {
                $credentials = explode(':', $credentials, 2);
                $this->setUsername(array_shift($credentials));
                $this->setPassword(array_shift($credentials));
            }

            // Host + port
            $port = explode(':', $domain, 2);
            $this->setDomain(array_shift($port));
            $this->setPort(array_shift($port));

            if (!empty($path) && empty($path[0])) {
                array_shift($path);
            }
        }

        if (!empty($path)) {
            $this->setPath($path);
        }

        return $this;
    }


    public function __get($member)
    {
        switch ($member) {
            case 'username':
                return $this->getUsername();

            case 'password':
                return $this->getPassword();

            case 'domain':
                return $this->getDomain();

            case 'port':
                return $this->getPort();

            case 'path':
                return $this->getPath();

            case 'query':
                return $this->getQuery();
        }
    }

    public function __set($member, $value)
    {
        switch ($member) {
            case 'username':
                return $this->setUsername($value);

            case 'password':
                return $this->setPassword($value);

            case 'domain':
                return $this->setDomain($value);

            case 'port':
                return $this->setPort($value);

            case 'path':
                return $this->setPath($value);

            case 'query':
                return $this->setQuery($value);
        }
    }

    // Scheme
    public function setScheme($scheme)
    {
        $scheme = strtolower((string)$scheme);

        if ($scheme !== 'http' && $scheme !== 'https') {
            $scheme = 'http';
        }

        $this->_scheme = $scheme;

        return $this;
    }

    public function isSecure(bool $flag = null)
    {
        if ($flag !== null) {
            if ($flag) {
                $this->_scheme = 'https';
            } else {
                $this->_scheme = 'http';
            }

            return $this;
        }

        return $this->_scheme == 'https';
    }


    // Port
    public function getPort()
    {
        if ($this->_port === null) {
            if ($this->_scheme == 'https') {
                return 443;
            }

            return 80;
        }

        return $this->_port;
    }


    // Arch request
    public function setDirectoryRequest(arch\IRequest $request = null)
    {
        $this->_directoryRequest = $request;
        return $this;
    }

    public function getDirectoryRequest()
    {
        return $this->_directoryRequest;
    }


    // Strings
    public function toString(): string
    {
        if ($this->isJustFragment()) {
            return $this->_getFragmentString();
        }

        if ($this->_scheme === null) {
            $output = '//';
        } else {
            $output = $this->getScheme() . '://';
        }

        $output .= $this->_getCredentialString();
        $output .= $this->_domain;

        $defaultPort = 80;

        if ($this->_scheme == 'https') {
            $defaultPort = 443;
        }

        $output .= $this->_getPortString($defaultPort);
        $output .= $this->getLocalString();

        return $output;
    }

    public function getLocalString()
    {
        if ($this->isJustFragment()) {
            return $this->_getFragmentString();
        }

        $output = $this->_getPathString(true);
        $output .= $this->_getQueryString();
        $output .= $this->_getFragmentString();

        return $output;
    }

    public function getOrigin(): string
    {
        if ($this->_scheme === null) {
            $output = 'http://';
        } else {
            $output = $this->getScheme() . '://';
        }

        $output .= $this->_domain;
        $defaultPort = 80;

        if ($this->_scheme == 'https') {
            $defaultPort = 443;
        }

        $output .= $this->_getPortString($defaultPort);
        return $output;
    }

    public function toReadableString()
    {
        if ($this->isJustFragment()) {
            return $this->_getFragmentString();
        }

        $local = $this->getLocalString();

        if ($local == '/') {
            $local = '';
        }

        $output = $this->_domain;
        $output .= $local;

        return $output;
    }
}

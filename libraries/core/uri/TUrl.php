<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\uri;

use DecodeLabs\Compass\Ip;

use DecodeLabs\Exceptional;
use df\core;

trait TUrl_TransientScheme
{
    protected $_scheme;

    public function setScheme($scheme)
    {
        if (!empty($scheme)) {
            $this->_scheme = (string)$scheme;
        } else {
            $this->_scheme = null;
        }

        return $this;
    }

    public function getScheme()
    {
        return $this->_scheme;
    }

    public function hasScheme()
    {
        return $this->_scheme !== null;
    }

    protected function _resetScheme()
    {
        $this->_scheme = null;
    }

    protected function _getSchemeString()
    {
        if ($this->_scheme !== null) {
            return $this->_scheme . '://';
        }
    }
}



// Credentials
trait TUrl_UsernameContainer
{
    protected $_username;

    public function setUsername($username)
    {
        if (strlen((string)$username)) {
            $this->_username = (string)$username;
        } else {
            $this->_username = null;
        }

        return $this;
    }

    public function getUsername()
    {
        return $this->_username;
    }

    public function hasUsername(...$usernames)
    {
        if (empty($usernames)) {
            return $this->_username !== null;
        }

        return in_array($this->_username, $usernames, true);
    }

    protected function _resetUsername()
    {
        $this->_username = null;
    }
}

trait TUrl_PasswordContainer
{
    protected $_password;

    public function setPassword($password)
    {
        if ($password !== null) {
            $this->_password = (string)$password;
        } else {
            $this->_password = null;
        }

        return $this;
    }

    public function getPassword()
    {
        return $this->_password;
    }

    public function hasPassword(...$passwords)
    {
        if (empty($passwords)) {
            return $this->_password !== null;
        }

        return in_array($this->_password, $passwords, true);
    }

    protected function _resetPassword()
    {
        $this->_password = null;
    }
}

trait TUrl_CredentialContainer
{
    use TUrl_UsernameContainer;
    use TUrl_PasswordContainer;

    public function setCredentials($username, $password)
    {
        return $this->setUsername($username)
            ->setPassword($password);
    }

    public function hasCredentials()
    {
        return $this->_username !== null || $this->_password !== null;
    }

    protected function _resetCredentials()
    {
        $this->_resetUsername();
        $this->_resetPassword();
    }

    protected function _getCredentialString()
    {
        if ($this->_username === null && $this->_password === null) {
            return null;
        }

        $output = $this->_username;

        if ($this->_password !== null) {
            $output .= ':' . $this->_password;
        }

        return $output . '@';
    }
}


// Domain
trait TUrl_DomainContainer
{
    protected $_domain;

    public function setDomain($domain)
    {
        $this->_domain = (string)$domain;
        return $this;
    }

    public function getDomain()
    {
        return $this->_domain;
    }

    public function hasDomain()
    {
        return $this->_domain !== null;
    }

    public function isAbsolute()
    {
        return (bool)strlen((string)$this->_domain);
    }

    protected function _resetDomain()
    {
        $this->_domain = null;
    }

    public function lookupIp(): Ip
    {
        if (empty($this->_domain)) {
            $ip = '127.0.0.1';
        } elseif (($ip = gethostbyname($this->_domain)) == $this->_domain) {
            throw Exceptional::Runtime(
                'Could not lookup IP for ' . $this->_domain
            );
        }

        return Ip::parse($ip);
    }
}



// Port
trait TUrl_PortContainer
{
    protected $_port;

    public function setPort($port)
    {
        if (!empty($port)) {
            $this->_port = (int)$port;
        } else {
            $this->_port = null;
        }

        return $this;
    }

    public function getPort()
    {
        return $this->_port;
    }

    public function hasPort(...$ports)
    {
        if (empty($ports)) {
            return $this->_port !== null;
        }

        return in_array($this->_port, $ports, true);
    }

    protected function _resetPort()
    {
        $this->_port = null;
    }

    protected function _getPortString($skip = null): string
    {
        if ($this->_port !== null && $this->_port !== $skip) {
            return ':' . $this->_port;
        }

        return '';
    }
}


trait TUrl_DomainPortContainer
{
    use TUrl_DomainContainer;
    use TUrl_PortContainer;

    public function getHost(): string
    {
        return (string)$this->getDomain() . $this->_getPortString();
    }
}


// Path
trait TUrl_PathContainer
{
    protected $_path;

    public function setPath($path)
    {
        if (empty($path)) {
            $this->_path = null;
        } else {
            $this->_path = Path::factory($path);
        }

        return $this;
    }

    public function getPath()
    {
        if (!$this->_path) {
            $this->_path = new Path();
        }

        return $this->_path;
    }

    public function getPathString()
    {
        if ($this->_path) {
            return $this->_path->toUrlEncodedString();
        } else {
            return '/';
        }
    }

    public function hasPath()
    {
        return $this->_path !== null;
    }

    protected function _clonePath()
    {
        if ($this->_path) {
            $this->_path = clone $this->_path;
        }
    }

    protected function _resetPath()
    {
        $this->_path = null;
    }

    protected function _getPathString($absolute = false)
    {
        if ($this->_path !== null) {
            $output = $this->_path->toUrlEncodedString();

            if ($absolute) {
                $output = '/' . ltrim((string)$output, '/.');
            }

            return $output;
        } elseif ($absolute) {
            return '/';
        }
    }
}


// Query
trait TUrl_QueryContainer
{
    protected $_query;

    public function setQuery($query)
    {
        if (empty($query)) {
            $this->_query = null;
        } else {
            if (is_string($query)) {
                $query = core\collection\Tree::fromArrayDelimitedString($query);
            } elseif (!$query instanceof core\collection\ITree) {
                $query = new core\collection\Tree($query);
            }

            $this->_query = $query;
        }

        return $this;
    }

    public function importQuery($query, array $filter = null)
    {
        if (empty($query)) {
            return $this;
        }

        if (is_string($query)) {
            $query = core\collection\Tree::fromArrayDelimitedString($query);
        } elseif (!$query instanceof core\collection\ITree) {
            $query = new core\collection\Tree($query);
        }

        $currentQuery = $this->getQuery();

        foreach ($query as $key => $node) {
            if ($filter && in_array($key, $filter)) {
                continue;
            }

            $currentQuery->{$key} = clone $node;
        }

        return $this;
    }

    public function getQuery(): core\collection\ITree
    {
        if (!$this->_query) {
            $this->_query = new core\collection\Tree();
        }

        return $this->_query;
    }

    public function getQueryString(): string
    {
        if ($this->_query) {
            return $this->_query->toArrayDelimitedString();
        } else {
            return '';
        }
    }

    public function getQueryTerm($key, $default = null)
    {
        if (!$this->_query) {
            return $default;
        }

        if (!$this->_query->has($key)) {
            return $default;
        }

        $output = trim((string)$this->_query[$key]);

        if (empty($output)) {
            return $default;
        }

        return $output;
    }

    public function hasQuery(): bool
    {
        return $this->_query !== null;
    }

    protected function _cloneQuery()
    {
        if ($this->_query) {
            $this->_query = clone $this->_query;
        }
    }

    protected function _resetQuery()
    {
        $this->_query = null;
    }

    protected function _getQueryString()
    {
        if ($this->_query !== null) {
            $queryString = $this->getQueryString();

            if (!empty($queryString)) {
                return '?' . $queryString;
            }
        }
    }
}



// Fragment
trait TUrl_FragmentContainer
{
    protected $_fragment;

    public function getFragment()
    {
        return $this->_fragment;
    }

    public function setFragment($fragment)
    {
        if ($fragment !== null) {
            $fragment = (string)$fragment;
        }

        $this->_fragment = $fragment;
        return $this;
    }

    public function hasFragment(...$fragments)
    {
        if (empty($fragments)) {
            return $this->_fragment !== null;
        }

        return in_array($this->_fragment, $fragments, true);
    }

    public function isJustFragment()
    {
        return ($this->_path === null || ($this->_path->isEmpty() && !$this->_path->shouldAddTrailingSlash()))
            && ($this->_query === null || $this->_query->isEmpty())
            && $this->_fragment !== null;
    }

    protected function _resetFragment()
    {
        $this->_fragment = null;
    }

    protected function _getFragmentString()
    {
        if ($this->_fragment !== null) {
            return '#' . $this->_fragment;
        }
    }
}

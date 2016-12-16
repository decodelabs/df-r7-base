<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\ftp;

use df;
use df\core;
use df\link;

class Url extends core\uri\Url implements IUrl {

    use core\uri\TUrl_CredentialContainer;
    use core\uri\TUrl_DomainPortContainer;

    public function import($url='') {
        if($url !== null) {
            $this->reset();
        }

        if($url == '' || $url === null) {
            return $this;
        }

        if($url instanceof self) {
            $this->_scheme = $url->_scheme;
            $this->_username = $url->_username;
            $this->_password = $url->_password;
            $this->_domain = $url->_domain;
            $this->_port = $url->_port;

            if($url->_path !== null) {
                $this->_path = clone $url->_path;
            }

            if($url->_query !== null) {
                $this->_query = clone $url->_query;
            }

            $this->_fragment = $url->_fragment;

            return $this;
        }

        // Fragment
        $parts = explode('#', $url, 2);
        $url = array_shift($parts);
        $this->setFragment(array_shift($parts));

        // Query
        $parts = explode('?', $url, 2);
        $url = array_shift($parts);
        $this->setQuery(array_shift($parts));

        // Scheme
        $parts = explode('://', $url, 2);
        $url = array_pop($parts);
        $this->setScheme(array_shift($parts));

        $this->setPath($url);
        $domain = $this->getPath()->shift();

        // Credentials
        $credentials = explode('@', $domain, 2);
        $domain = array_pop($credentials);
        $credentials = array_shift($credentials);

        if(!empty($credentials)) {
            $credentials = explode(':', $credentials, 2);
            $this->setUsername(array_shift($credentials));
            $this->setPassword(array_shift($credentials));
        }

        // Host + port
        $port = explode(':', $domain, 2);
        $this->setDomain(array_shift($port));
        $this->setPort(array_shift($port));

        return $this;
    }


// Scheme
    public function setScheme($scheme) {
        $scheme = strtolower($scheme);

        if($scheme !== 'ftp' && $scheme !== 'ftps') {
            $scheme = 'ftp';
        }

        $this->_scheme = $scheme;

        return $this;
    }

    public function isSecure(bool $flag=null) {
        if($flag !== null) {
            if($flag) {
                $this->_scheme = 'ftps';
            } else {
                $this->_schemea = 'ftp';
            }

            return $this;
        }

        return $this->_scheme == 'ftps';
    }





// Strings
    public function toString(): string {
        if($this->isJustFragment()) {
            return $this->_getFragmentString();
        }

        $output = $this->getScheme().'://';
        $output .= $this->_getCredentialString();
        $output .= $this->_domain;
        $output .= $this->_getPortString();
        $output .= $this->_getPathString(true);
        $output .= $this->_getQueryString();
        $output .= $this->_getFragmentString();

        return $output;
    }
}
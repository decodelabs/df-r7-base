<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\link\http\request;

use DecodeLabs\Deliverance\Channel;

use DecodeLabs\Exceptional;
use df\link;

class Options implements link\http\IRequestOptions
{
    public $downloadFolder;
    public $downloadFileName;
    public $downloadStream;

    public $maxRedirects = null;
    public $strictRedirects = null;
    public $hideRedirectReferrer = null;

    public $authType = null;
    public $username = null;
    public $password = null;

    public $certPath;
    public $certPassword;

    public $cookieJar;

    public $secureTransport = null;
    public $sslKeyPath;
    public $sslKeyPassword;
    public $verifySsl = null;
    public $allowSelfSigned = null;
    public $caBundlePath;

    public $timeout = null;
    public $connectTimeout = null;


    public function import(link\http\IRequestOptions $options)
    {
        $keys = [
            'downloadFolder', 'downloadFileName', 'maxRedirects', 'strictRedirects',
            'hideRedirectReferrer', 'authType', 'username', 'password',
            'certPath', 'certPassword', 'cookieJar', 'secureTransport',
            'sslKeyPath', 'sslKeyPassword', 'verifySsl', 'allowSelfSigned',
            'caBundlePath', 'timeout', 'connectTimeout'
        ];

        foreach ($keys as $key) {
            $val = $options->{'get' . ucfirst($key)}();

            if ($val !== null) {
                $this->{$key} = $val;
            }
        }

        return $this;
    }

    public function sanitize()
    {
        if ($this->maxRedirects === null) {
            $this->maxRedirects = 10;
        }

        if ($this->strictRedirects === null) {
            $this->strictRedirects = false;
        }

        if ($this->hideRedirectReferrer === null) {
            $this->hideRedirectReferrer = false;
        }

        if ($this->authType === null) {
            $this->authType = 'basic';
        }

        if ($this->secureTransport === null) {
            $this->secureTransport = 'tls';
        }

        if ($this->verifySsl === null) {
            $this->verifySsl = true;
        }

        if ($this->allowSelfSigned === null) {
            $this->allowSelfSigned = false;
        }

        if ($this->timeout === null) {
            $this->timeout = 0;
        }

        if ($this->connectTimeout === null) {
            $this->connectTimeout = 0;
        }
    }


    // File path
    public function setDownloadFolder($path)
    {
        $this->downloadFolder = $path;
        return $this;
    }

    public function getDownloadFolder()
    {
        return $this->downloadFolder;
    }

    public function setDownloadFileName($name)
    {
        $this->downloadFileName = $name;
        return $this;
    }

    public function getDownloadFileName()
    {
        return $this->downloadFileName;
    }

    public function setDownloadFilePath($path)
    {
        $this->setDownloadFolder(dirname($path));
        $this->setDownloadFileName(basename($path));
        return $this;
    }

    public function getDownloadFilePath()
    {
        if (!$this->downloadFolder) {
            return null;
        }

        return rtrim((string)$this->downloadFolder, '/') . '/' . $this->downloadFileName;
    }

    public function setDownloadStream(Channel $stream = null)
    {
        $this->downloadStream = $stream;
        return $this;
    }

    public function getDownloadStream()
    {
        return $this->downloadStream;
    }


    // Redirects
    public function setMaxRedirects($max)
    {
        $this->maxRedirects = (int)$max;

        if ($this->maxRedirects < 0) {
            $this->maxRedirects = 0;
        }

        return $this;
    }

    public function getMaxRedirects()
    {
        return $this->maxRedirects;
    }

    public function shouldEnforceStrictRedirects(bool $flag = null)
    {
        if ($flag !== null) {
            $this->strictRedirects = $flag;
            return $this;
        }

        return (bool)$this->strictRedirects;
    }

    public function shouldHideRedirectReferrer(bool $flag = null)
    {
        if ($flag !== null) {
            $this->hideRedirectReferrer = $flag;
            return $this;
        }

        return (bool)$this->hideRedirectReferrer;
    }

    // Auth
    public function setCredentials($username, $password, $type = null)
    {
        $this->setUsername($username)->setPassword($password);

        if ($type !== null) {
            $this->setAuthType($type);
        }

        return $this;
    }

    public function setUsername($username)
    {
        $this->username = (string)$username;
        return $this;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setPassword($password)
    {
        $this->password = (string)$password;
        return $this;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setAuthType($type)
    {
        $type = strtolower((string)$type);

        switch ($type) {
            case 'basic':
            case 'digest':
            case 'ntlm':
                $this->authType = $type;
                break;

            default:
                throw Exceptional::InvalidArgument(
                    'Auth type ' . $type . ' is not supported'
                );
        }

        return $this;
    }

    public function getAuthType()
    {
        return $this->authType;
    }

    public function hasCredentials()
    {
        return $this->username !== null || $this->password !== null;
    }


    // Cert
    public function setCertPath($path)
    {
        $this->certPath = $path;
        return $this;
    }

    public function getCertPath()
    {
        return $this->certPath;
    }

    public function setCertPassword($password)
    {
        $this->certPassword = $password;
        return $this;
    }

    public function getCertPassword()
    {
        return $this->certPassword;
    }


    // Cookie jar
    public function setCookieJar(link\http\ICookieJar $cookieJar = null)
    {
        $this->cookieJar = $cookieJar;
        return $this;
    }

    public function getCookieJar()
    {
        return $this->cookieJar;
    }

    // Secure transport
    public function setSecureTransport($transport)
    {
        $transport = strtolower((string)$transport);

        switch ($transport) {
            case 'ssl':
            case 'sslv2':
            case 'sslv3':
            case 'tls':
                $this->secureTransport = $transport;
                break;

            default:
                $this->secureTransport = 'tls';
                break;
        }

        return $this;
    }

    public function getSecureTransport()
    {
        return $this->secureTransport;
    }

    // SSL Key
    public function setSslKeyPath($path)
    {
        $this->sslKeyPath = $path;
        return $this;
    }

    public function getSslKeyPath()
    {
        return $this->sslKeyPath;
    }

    public function setSslKeyPassword($password)
    {
        $this->sslKeyPassword = $password;
        return $this;
    }

    public function getSslKeyPassword()
    {
        return $this->sslKeyPassword;
    }

    public function shouldVerifySsl(bool $flag = null)
    {
        if ($flag !== null) {
            $this->verifySsl = $flag;
            return $this;
        }

        return (bool)$this->verifySsl;
    }

    public function shouldAllowSelfSigned(bool $flag = null)
    {
        if ($flag !== null) {
            $this->allowSelfSigned = $flag;
            return $this;
        }

        return (bool)$this->allowSelfSigned;
    }

    public function setCaBundlePath($path)
    {
        $this->caBundlePath = $path;
        return $this;
    }

    public function getCaBundlePath()
    {
        return $this->caBundlePath;
    }


    // Timeout
    public function setTimeout($duration)
    {
        $this->timeout = (float)$duration;

        if ($this->timeout < 0) {
            $this->timeout = 0;
        }

        return $this;
    }

    public function getTimeout()
    {
        return $this->timeout;
    }

    public function setConnectTimeout($duration)
    {
        $this->connectTimeout = (float)$duration;

        if ($this->connectTimeout < 0) {
            $this->connectTimeout = 0;
        }

        return $this;
    }

    public function getConnectTimeout()
    {
        return $this->connectTimeout;
    }
}

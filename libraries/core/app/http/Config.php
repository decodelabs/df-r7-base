<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\app\http;

use DecodeLabs\Compass\Range;
use DecodeLabs\Genesis;

use df\core;
use df\link;

class Config extends core\Config
{
    public const ID = 'http';
    public const STORE_IN_MEMORY = true;
    public const USE_ENVIRONMENT_ID_BY_DEFAULT = true;

    public function getDefaultValues(): array
    {
        return [
            'baseUrl' => $this->_generateRootUrlList(),
            'sendFileHeader' => 'X-Sendfile',
            'secure' => false,
            'ipRanges' => null,
            'ipRangeAreas' => null,
            'credentials' => []
        ];
    }

    // Base url
    public function setRootUrl($url, $envMode = null)
    {
        if ($envMode === null) {
            $envMode = Genesis::$environment->getMode();
        }

        if ($url !== null) {
            $url = link\http\Url::factory($url);
            $url->getPath()->shouldAddTrailingSlash(true)->isAbsolute(true);
            $domain = $url->getDomain();
            $port = $url->getPort();

            if (!empty($port) && $port != '80') {
                $domain = $domain . ':' . $port;
            }

            $url = $domain . $url->getPathString();
        }

        if (!count($this->values->baseUrl->{$envMode})) {
            $this->values->baseUrl->{$envMode} = $url;
        } else {
            $this->values->baseUrl->{$envMode}->{'*'} = $url;
        }

        return $this;
    }

    public function getRootUrl($envMode = null)
    {
        if (!isset($this->values->baseUrl)) {
            $this->values->baseUrl = $this->_generateRootUrlList();
            $this->save();
        }

        if ($envMode === null) {
            $envMode = Genesis::$environment->getMode();
        }

        $output = null;

        if (isset($this->values->baseUrl[$envMode])) {
            $output = $this->values->baseUrl[$envMode];
        } elseif (isset($this->values->baseUrl->{$envMode}->{'*'})) {
            $output = $this->values->baseUrl->{$envMode}['*'];
        } elseif (isset($this->values->baseUrl->{$envMode}->{0})) {
            $output = $this->values->baseUrl->{$envMode}[0];
        } elseif (isset($this->values->baseUrl->{$envMode}->{'front'})) {
            $output = $this->values->baseUrl->{$envMode}['front'];
        }

        if ($output === null && isset($_SERVER['HTTP_HOST'])) {
            if (null !== ($rootUrl = $this->_generateRootUrl())) {
                $this->setRootUrl($rootUrl)->save();
            }
        }

        return trim((string)$output, '/');
    }

    public function getBaseUrlMap($envMode = null)
    {
        if ($envMode === null) {
            $envMode = Genesis::$environment->getMode();
        }

        if (!isset($this->values->baseUrl->{$envMode}) && isset($_SERVER['HTTP_HOST'])) {
            $this->values->baseUrl->{$envMode} = $this->_generateRootUrl();
            $this->save();
        }

        $node = $this->values->baseUrl->{$envMode};
        $output = [];

        if ($node->hasValue()) {
            $output['*'] = trim((string)$node->getValue(), '/');
        }

        foreach ($node as $key => $value) {
            $output[$key] = trim((string)$value, '/');
        }

        return $output;
    }

    protected function _generateRootUrlList()
    {
        if (!isset($_SERVER['HTTP_HOST'])) {
            return null;
        }

        $baseUrl = $this->_generateRootUrl();
        $envMode = Genesis::$environment->getMode();

        $output = [
            'development' => null,
            'testing' => null,
            'production' => null
        ];

        $output[$envMode] = $baseUrl;

        if (substr($baseUrl, 0, strlen($envMode) + 1) == $envMode . '.') {
            $baseUrl = substr($baseUrl, strlen($envMode) + 1);
        }

        foreach ($output as $key => $val) {
            if ($val === null) {
                $output[$key] = ['*' => $key . '.' . $baseUrl];
            }
        }

        return $output;
    }

    protected function _generateRootUrl()
    {
        $baseUrl = null;
        $request = new link\http\request\Base(true);
        $host = $request->getUrl()->getHost();
        $path = $request->getUrl()->getPathString();

        $baseUrl = $host . '/' . trim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/';
        $currentUrl = $host . '/' . $path;

        if (substr($currentUrl, 0, strlen($baseUrl)) != $baseUrl) {
            $parts = explode('/', $currentUrl);
            array_pop($parts);
            $baseUrl = implode('/', $parts) . '/';
        }

        return $baseUrl;
    }


    // Send file header
    public function setSendFileHeader($header)
    {
        $this->values->sendFileHeader = $header;
        return $this;
    }

    public function getSendFileHeader()
    {
        $output = null;

        if (isset($this->values['sendFileHeader'])) {
            $output = $this->values['sendFileHeader'];
        }

        if (empty($output)) {
            //$output = 'X-Sendfile';
            $output = null;
        }

        return $output;
    }


    // Https
    public function isSecure(bool $flag = null)
    {
        if ($flag !== null) {
            $this->values->secure = $flag;
            return $this;
        }

        return (bool)$this->values['secure'];
    }


    // IP Ranges
    public function setIpRanges(array $ranges = null)
    {
        if ($ranges !== null) {
            foreach ($ranges as $i => $range) {
                $ranges = (string)Range::parse($range);
            }
        }

        $this->values->ipRanges = $ranges;
        return $this;
    }

    public function getIpRanges()
    {
        $output = [];

        foreach ($this->values->ipRanges as $range) {
            $output[] = Range::parse((string)$range);
        }

        return $output;
    }

    public function getIpRangesForArea($area)
    {
        if (isset($this->values->ipRangeAreas)
        && !$this->values->ipRangeAreas->isEmpty()
        && !$this->values->ipRangeAreas->contains($area)) {
            return [];
        } else {
            return $this->getIpRanges();
        }
    }


    // Credentials
    public function getCredentials($mode = null)
    {
        if ($mode === null) {
            $mode = Genesis::$environment->getMode();
        }

        if (isset($this->values->credentials->{$mode}['username'])) {
            $set = $this->values->credentials->{$mode};
        } elseif (isset($this->values->credentials['username'])) {
            $set = $this->values->credentials;
        } else {
            return null;
        }

        return [
            'username' => $set['username'],
            'password' => $set['password']
        ];
    }
}

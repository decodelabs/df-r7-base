<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\application\http;

use df;
use df\core;
use df\link;

class Config extends core\Config {

    const ID = 'http';
    const STORE_IN_MEMORY = true;
    const USE_ENVIRONMENT_ID_BY_DEFAULT = true;

    public function getDefaultValues() {
        return [
            'baseUrl' => $this->_generateRootUrlList(),
            'sendFileHeader' => 'X-Sendfile',
            'secure' => false,
            'manualChunk' => false,
            'ipRanges' => null,
            'credentials' => []
        ];
    }

    // Base url
    public function setRootUrl($url, $environmentMode=null) {
        if($environmentMode === null) {
            $environmentMode = df\Launchpad::getEnvironmentMode();
        }

        if($url !== null) {
            $url = link\http\Url::factory($url);
            $url->getPath()->shouldAddTrailingSlash(true)->isAbsolute(true);
            $domain = $url->getDomain();
            $port = $url->getPort();

            if(!empty($port) && $port != '80') {
                $domain = $domain.':'.$port;
            }

            $url = $domain.$url->getPathString();
        }

        if(!count($this->values->baseUrl->{$environmentMode})) {
            $this->values->baseUrl->{$environmentMode} = $url;
        } else {
            $this->values->baseUrl->{$environmentMode}->{'*'} = $url;
        }

        return $this;
    }

    public function getRootUrl($environmentMode=null) {
        if(!isset($this->values->baseUrl)) {
            $this->values->baseUrl = $this->_generateRootUrlList();
            $this->save();
        }

        if($environmentMode === null) {
            $environmentMode = df\Launchpad::getEnvironmentMode();
        }

        $output = null;

        if(isset($this->values->baseUrl[$environmentMode])) {
            $output = $this->values->baseUrl[$environmentMode];
        } else if(isset($this->values->baseUrl->{$environmentMode}->{'*'})) {
            $output = $this->values->baseUrl->{$environmentMode}['*'];
        } else if(isset($this->values->baseUrl->{$environmentMode}->{'front'})) {
            $output = $this->values->baseUrl->{$environmentMode}['front'];
        }

        if($output === null && isset($_SERVER['HTTP_HOST'])) {
            if(null !== ($rootUrl = $this->_generateRootUrl())) {
                $this->setRootUrl($rootUrl)->save();
            }
        }

        return trim($output, '/');
    }

    public function getBaseUrlMap($environmentMode=null) {
        if($environmentMode === null) {
            $environmentMode = df\Launchpad::getEnvironmentMode();
        }

        if(!isset($this->values->baseUrl->{$environmentMode}) && isset($_SERVER['HTTP_HOST'])) {
            $this->values->baseUrl->{$environmentMode} = $this->_generateRootUrl();
            $this->save();
        }

        $node = $this->values->baseUrl->{$environmentMode};
        $output = [];

        if($node->hasValue()) {
            $output['*'] = trim($node->getValue(), '/');
        }

        foreach($node as $key => $value) {
            $output[$key] = trim($value, '/');
        }

        return $output;
    }

    protected function _generateRootUrlList() {
        if(!isset($_SERVER['HTTP_HOST'])) {
            return null;
        }

        $baseUrl = $this->_generateRootUrl();
        $envMode = df\Launchpad::getEnvironmentMode();

        $output = [
            'development' => null,
            'testing' => null,
            'production' => null
        ];

        $output[$envMode] = $baseUrl;

        if(substr($baseUrl, 0, strlen($envMode) + 1) == $envMode.'.') {
            $baseUrl = substr($host, strlen($envMode) + 1);
        }

        foreach($output as $key => $val) {
            if($val === null) {
                $output[$key] = ['*' => $key.'.'.$baseUrl];
            }
        }

        return $output;
    }

    protected function _generateRootUrl() {
        $baseUrl = null;
        $request = new link\http\request\Base(true);
        $host = $request->getUrl()->getDomain();
        $path = $request->getUrl()->getPathString();

        $baseUrl = $host.'/'.trim(dirname($_SERVER['SCRIPT_NAME']), '/').'/';
        $currentUrl = $host.'/'.$path;

        if(substr($currentUrl, 0, strlen($baseUrl)) != $baseUrl) {
            $parts = explode('/', $currentUrl);
            array_pop($parts);
            $baseUrl = implode('/', $parts).'/';
        }

        return $baseUrl;
    }


// Send file header
    public function setSendFileHeader($header) {
        $this->values->sendFileHeader = $header;
        return $this;
    }

    public function getSendFileHeader() {
        $output = null;

        if(isset($this->values['sendFileHeader'])) {
            $output = $this->values['sendFileHeader'];
        }

        if(empty($output)) {
            //$output = 'X-Sendfile';
            $output = null;
        }

        return $output;
    }


// Https
    public function isSecure(bool $flag=null) {
        if($flag !== null) {
            $this->values->secure = $flag;
            return $this;
        }

        return (bool)$this->values['secure'];
    }

// Chunk
    public function shouldChunkManually(bool $flag=null) {
        if($flag !== null) {
            $this->values->manualChunk = $flag;
            return $this;
        }

        return (bool)$this->values['manualChunk'];
    }

// IP Ranges
    public function setIpRanges(array $ranges=null) {
        if($ranges !== null) {
            foreach($ranges as $i => $range) {
                $ranges = (string)link\IpRange::factory($range);
            }
        }

        $this->values->ipRanges = $ranges;
        return $this;
    }

    public function getIpRanges() {
        $output = [];

        foreach($this->values->ipRanges as $range) {
            $output[] = link\IpRange::factory((string)$range);
        }

        return $output;
    }


// Credentials
    public function getCredentials($mode=null) {
        if($mode === null) {
            $mode = df\Launchpad::getEnvironmentMode();
        }

        if(isset($this->values->credentials->{$mode}['username'])) {
            $set = $this->values->credentials->{$mode};
        } else if(isset($this->values->credentials['username'])) {
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
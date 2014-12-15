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
            'baseUrl' => $this->_generateBaseUrlList(),
            'areaDomainMap' => [],
            'sendFileHeader' => 'X-Sendfile',
            'secure' => false,
            'manualChunk' => false,
            'ipRanges' => null,
            'credentials' => [
                'development' => [
                    'username' => null,
                    'password' => null
                ],
                'testing' => [
                    'username' => null,
                    'password' => null
                ],
                'production' => [
                    'username' => null,
                    'password' => null
                ]
            ]
        ];
    }

    // Base url
    public function setBaseUrl($url, $environmentMode=null) {
        if($environmentMode === null) {
            $environmentMode = df\Launchpad::getEnvironmentMode();
        }

        $this->_fixBaseUrlEntry();
        
        if($url === null) {
            $this->values['baseUrl'][$environmentMode] = null;
        } else {
            $url = link\http\Url::factory($url);
            $url->getPath()->shouldAddTrailingSlash(true)->isAbsolute(true);
            
            $this->values['baseUrl'][$environmentMode] = $url->getDomain().$url->getPathString();
        }
        
        return $this;
    }
    
    public function getBaseUrl($environmentMode=null) {
        $this->_fixBaseUrlEntry();

        if(!isset($this->values['baseUrl'])) {
            $this->values['baseUrl'] = $this->_generateBaseUrlList();
            $this->save();
        }

        if($environmentMode === null) {
            $environmentMode = df\Launchpad::getEnvironmentMode();
        }
        
        if(!isset($this->values['baseUrl'][$environmentMode]) && isset($_SERVER['HTTP_HOST'])) {
            if(null !== ($baseUrl = $this->_generateBaseUrl())) {
                $this->setBaseUrl($baseUrl)->save();
            }
        }
        
        return trim($this->values['baseUrl'][$environmentMode], '/');
    }

    protected function _fixBaseUrlEntry() {
        if(isset($this->values['httpBaseUrl'])) {
            $this->values['baseUrl'] = $this->values['httpBaseUrl'];
            unset($this->values['httpBaseUrl']);
            $this->save();
        }
    }
    
    protected function _generateBaseUrlList() {
        if(!isset($_SERVER['HTTP_HOST'])) {
            return null;
        }

        $baseUrl = $this->_generateBaseUrl();
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
                $output[$key] = $key.'.'.$baseUrl;
            }
        }
            
        return $output;
    }
    
    protected function _generateBaseUrl() {
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


// Area domain map
    public function setAreaDomainMap(array $map) {
        $this->values['areaDomainMap'] = $map;
        return $this;
    }

    public function getAreaDomainMap() {
        if(!isset($this->values['areaDomainMap']) || !is_array($this->values['areaDomainMap'])) {
            return [];
        }

        return $this->values['areaDomainMap'];
    }
    

// Send file header
    public function setSendFileHeader($header) {
        $this->values['sendFileHeader'] = $header;
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
    public function isSecure($flag=null) {
        if($flag !== null) {
            $this->values['secure'] = (bool)$flag;
            return $this;
        }

        if(!isset($this->values['secure'])) {
            return false;
        }

        return (bool)$this->values['secure'];
    }

// Chunk
    public function shouldChunkManually($flag=null) {
        if($flag !== null) {
            $this->values['manualChunk'] = (bool)$flag;
            return $this;
        }

        if(!isset($this->values['manualChunk'])) {
            return false;
        }

        return (bool)$this->values['manualChunk'];
    }

// IP Ranges
    public function setIpRanges(array $ranges=null) {
        if($ranges !== null) {
            foreach($ranges as $i => $range) {
                $ranges = link\IpRange::factory($range);
            }

            core\dump($ranges);
        }

        $this->values['ipRanges'] = $ranges;
        return $this;
    }

    public function getIpRanges() {
        if(isset($this->values['ipRanges']) && is_array($this->values['ipRanges'])) {
            $output = [];

            foreach($this->values['ipRanges'] as $range) {
                $output[] = link\IpRange::factory($range);
            }

            return $output;
        }

        return [];
    }


// Credentials
    public function getCredentials($mode=null) {
        if($mode === null) {
            $mode = df\Launchpad::getEnvironmentMode();
        }

        if(!isset($this->values['credentials'][$mode]['username'])) {
            return null;
        }

        $set = $this->values['credentials'][$mode];

        return [
            'username' => $set['username'],
            'password' => isset($set['password']) ? $set['password'] : ''
        ];
    }
}
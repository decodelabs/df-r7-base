<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core;

use df;
use df\core;
use df\halo;

class Environment extends Config {
    
    const ID = 'environment';
    const IS_DISTRIBUTED = false;
    const STORE_IN_MEMORY = true;
    const USE_ENVIRONMENT_ID_BY_DEFAULT = true;
    
    public function getDefaultValues() {
        return [
            'httpBaseUrl' => $this->_generateHttpBaseUrl(),
            'phpBinaryPath' => 'php',
            'distributed' => false
        ];
    }
        
    
// Http base url
    public function setHttpBaseUrl($url) {
        if($url === null) {
            $this->_values['httpBaseUrl'] = null;
        } else {
            $url = halo\protocol\http\Url::factory($url);
            $url->getPath()->shouldAddTrailingSlash(true)->isAbsolute(true);
            
            $this->_values['httpBaseUrl'] = $url->getDomain().$url->getPathString();
        }
        
        return $this;
    }
    
    public function getHttpBaseUrl() {
        if(!isset($this->_values['httpBaseUrl']) && isset($_SERVER['HTTP_HOST'])) {
            if(null !== ($baseUrl = $this->_generateHttpBaseUrl())) {
                $this->setHttpBaseUrl($baseUrl)->save();
            }
        }
        
        return trim($this->_values['httpBaseUrl'], '/');
    }
    
    protected function _generateHttpBaseUrl() {
        $baseUrl = null;
        $request = new halo\protocol\http\request\Base(true);
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
    

// PHP Binary path
    public function setPhpBinaryPath($path) {
        $this->_values['phpBinaryPath'] = $path;
        return $this;
    }
    
    public function getPhpBinaryPath() {
        if(isset($this->_values['phpBinaryPath'])) {
            return $this->_values['phpBinaryPath'];
        }
        
        return 'php';
    }
    
    
// Load balancing
    public function isDistributed($flag=null) {
        if($flag !== null) {
            $this->_values['distributed'] = (bool)$flag;
            return $this;
        }
        
        if(!isset($this->_values['distributed'])) {
            return false;
        }
        
        return (bool)$this->_values['distributed'];
    }
}
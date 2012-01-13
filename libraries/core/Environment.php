<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core;

use df;
use df\core;

class Environment extends Config {
    
    const ID = 'environment';
    const IS_DISTRIBUTED = false;
    const STORE_IN_MEMORY = true;
    const USE_ENVIRONMENT_ID_BY_DEFAULT = true;
    
    const DEVELOPMENT = 'development';
    const TESTING = 'testing';
    const PRODUCTION = 'production';
    
    public function getDefaultValues() {
        return array(
            'environmentMode' => self::DEVELOPMENT,
            'httpBaseUrl' => $this->_generateHttpBaseUrl(),
            'phpBinaryPath' => 'php',
            'distributed' => false
        );
    }
    
// Environment mode
    public function setEnvironmentMode($mode) {
        switch($mode = strtolower($mode)) {
            case self::DEVELOPMENT:
            case self::TESTING:
            case self::PRODUCTION:
                break;
                
            default:
                $mode = self::DEVELOPMENT;
                break;
        } 
        
        $this->_values['environmentMode'] = $mode;
        return $this;
    }
    
    public function getEnvironmentMode() {
        if(isset($this->_values['environmentMode'])) {
            return $this->_values['environmentMode'];
        }
        
        return self::DEVELOPMENT;
    }
    
    
// Http base url
    public function setHttpBaseUrl($url) {
        $url = halo\protocol\http\Url::factory($url);
        $url->getPath()->shouldAddTrailingSlash(true)->isAbsolute(true);
        
        $this->_values['httpBaseUrl'] = $url->getDomain().$url->getPathString();
        
        return $this;
    }
    
    public function getHttpBaseUrl() {
        if(!isset($this->_values['httpBaseUrl'])) {
            if(null !== ($baseUrl = $this->_generateHttpBaseUrl())) {
                $this->setHttpBaseUrl($baseUrl)->save();
            }
        }
        
        return trim($this->_values['httpBaseUrl'], '/');
    }
    
    protected function _generateHttpBaseUrl() {
        core\stub();
        
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
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
    const STORE_IN_MEMORY = true;
    const USE_ENVIRONMENT_ID_BY_DEFAULT = true;
    
    public function getDefaultValues() {
        return [
            'httpBaseUrl' => $this->_generateHttpBaseUrlList(),
            'sendFileHeader' => 'X-Sendfile',
            'phpBinaryPath' => 'php',
            'distributed' => false,
            'activeLocations' => [],
            'daemonUser' => $this->_extrapolateDaemonUser(),
            'daemonGroup' => $this->_extrapolateDaemonGroup(),
            'devUser' => null,
            'devPassword' => null
        ];
    }

    protected function _sanitizeValuesOnCreate() {
        try {
            halo\process\Base::launchTask('git/init-gitignore');
        } catch(\Exception $e) {}
    }
        
    
// Http base url
    public function setHttpBaseUrl($url, $environmentMode=null) {
        if($environmentMode === null) {
            $environmentMode = df\Launchpad::getEnvironmentMode();
        }
        
        if($url === null) {
            $this->values['httpBaseUrl'][$environmentMode] = null;
        } else {
            $url = halo\protocol\http\Url::factory($url);
            $url->getPath()->shouldAddTrailingSlash(true)->isAbsolute(true);
            
            $this->values['httpBaseUrl'][$environmentMode] = $url->getDomain().$url->getPathString();
        }
        
        return $this;
    }
    
    public function getHttpBaseUrl($environmentMode=null) {
        if(!isset($this->values['httpBaseUrl'])) {
            $this->values['httpBaseUrl'] = $this->_generateHttpBaseUrlList();
            $this->save();
        }

        if($environmentMode === null) {
            $environmentMode = df\Launchpad::getEnvironmentMode();
        }
        
        if(!isset($this->values['httpBaseUrl'][$environmentMode]) && isset($_SERVER['HTTP_HOST'])) {
            if(null !== ($baseUrl = $this->_generateHttpBaseUrl())) {
                $this->setHttpBaseUrl($baseUrl)->save();
            }
        }
        
        return trim($this->values['httpBaseUrl'][$environmentMode], '/');
    }
    
    protected function _generateHttpBaseUrlList() {
        if(!isset($_SERVER['HTTP_HOST'])) {
            return null;
        }

        $baseUrl = $this->_generateHttpBaseUrl();
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

// PHP Binary path
    public function setPhpBinaryPath($path) {
        $this->values['phpBinaryPath'] = $path;
        return $this;
    }
    
    public function getPhpBinaryPath() {
        if(isset($this->values['phpBinaryPath'])) {
            $output = $this->values['phpBinaryPath'];
        } else {
            $output = 'php';
        }
        
        if(false === strpos($output, '/')
        && false === strpos($output, '\\')) {
            $output = halo\system\Base::getInstance()->which($output);
            $this->values['phpBinaryPath'] = $output;
            $this->save();
        }

        return $output;
    }
    
    
// Load balancing
    public function isDistributed($flag=null) {
        if($flag !== null) {
            $this->values['distributed'] = (bool)$flag;
            return $this;
        }
        
        if(!isset($this->values['distributed'])) {
            return false;
        }
        
        return (bool)$this->values['distributed'];
    }

// Locations
    public function getActiveLocations() {
        if(!isset($this->values['activeLocations'])) {
            return array();
        }

        $output = $this->values['activeLocations'];

        if(!is_array($output)) {
            $output = ['default' => $output];
        }

        return $output;
    }


// Daemons
    public function setDaemonUser($user) {
        if(is_numeric($user)) {
            $system = halo\system\Base::getInstance();
            $user = $system->userIdToUserName($user);
        }

        if(empty($user)) {
            throw new InvalidArgumentException(
                'Invalid username detected'
            );
        }

        $this->values['daemonUser'] = $user;
        return $this;
    }

    public function getDaemonUser() {
        $output = null;
        $save = false;

        if(!isset($this->values['daemonUser'])) {
            $output = $this->_extrapolateDaemonUser();
            $save = true;
        } else {
            $output = $this->values['daemonUser'];
        }

        if(empty($output)) {
            $output = $this->_extrapolateDaemonUser();
            $save = true;
        }

        if($save && !empty($output)) {
            $this->setDaemonUser($output);
            $this->save();
        }

        return $output;
    }

    protected function _extrapolateDaemonUser() {
        $process = halo\process\Base::getCurrent();
        return $process->getOwnerName();
    }

    public function setDaemonGroup($group) {
        if(is_numeric($group)) {
            $system = halo\system\Base::getInstance();
            $group = $system->groupIdToGroupName($group);
        }

        if(empty($group)) {
            throw new InvalidArgumentException(
                'Invalid group name detected'
            );
        }

        $this->values['daemonGroup'] = $group;
        return $this;
    }

    public function getDaemonGroup() {
        $output = null;
        $save = false;

        if(!isset($this->values['daemonGroup'])) {
            $output = $this->_extrapolateDaemonGroup();
            $save = true;
        } else {
            $output = $this->values['daemonGroup'];
        }

        if(empty($output)) {
            $output = $this->_extrapolateDaemonGroup();
            $save = true;
        }

        if($save && !empty($output)) {
            $this->setDaemonGroup($output);
            $this->save();
        }

        return $output;
    }

    protected function _extrapolateDaemonGroup() {
        $process = halo\process\Base::getCurrent();
        return $process->getGroupName();
    }


// Dev credentials
    public function setDeveloperCredentials($username, $password) {
        $this->values['devUser'] = $username;
        $this->values['devPassword'] = $password;

        return $this;
    }

    public function getDeveloperCredentials() {
        if(isset($this->values['devUser'], $this->values['devPassword'])) {
            return ['user' => $this->values['devUser'], 'password' => $this->values['devPassword']];
        }

        return null;
    }
}
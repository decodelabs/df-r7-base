<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core;

use df;
use df\core;
use df\arch;
use df\halo;

class Environment extends Config {
    
    const ID = 'environment';
    const STORE_IN_MEMORY = true;
    const USE_ENVIRONMENT_ID_BY_DEFAULT = true;
    
    public function getDefaultValues() {
        return [
            'phpBinaryPath' => 'php',
            'vendorBinaryPaths' => [],
            'distributed' => false,
            'activeLocations' => [],
            'deamonsEnabled' => null,
            'daemonUser' => $this->_extrapolateDaemonUser(),
            'daemonGroup' => $this->_extrapolateDaemonGroup(),
            'devUser' => null,
            'devPassword' => null
        ];
    }

    protected function _sanitizeValuesOnCreate() {
        try {
            arch\task\Manager::getInstance()->invoke('git/init-gitignore');
        } catch(\Exception $e) {}
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
        
        /*
        if(false === strpos($output, '/')
        && false === strpos($output, '\\')) {
            $output = halo\system\Base::getInstance()->which('php');
            $this->values['phpBinaryPath'] = $output;
            $this->save();
        }
        */

        return $output;
    }
    
// Vendor binary paths
    public function getVendorBinaryPath($id) {
        if(isset($this->values['vendorBinaryPaths'][$id])) {
            return $this->values['vendorBinaryPaths'][$id];
        } else {
            return $id;
        }
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
            return [];
        }

        $output = $this->values['activeLocations'];

        if(!is_array($output)) {
            $output = ['default' => $output];
        }

        return $output;
    }


// Daemons
    public function canUseDaemons($flag=null) {
        if($flag !== null) {
            $this->values['deamonsEnabled'] = (bool)$flag;
            return $this;
        }

        return false; // DELETE ME!

        if(!isset($this->values['deamonsEnabled'])) {
            $this->values['deamonsEnabled'] = extension_loaded('pcntl');
            $this->save();
        }

        return (bool)$this->values['deamonsEnabled'];
    }

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
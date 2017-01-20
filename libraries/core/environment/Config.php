<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\environment;

use df;
use df\core;
use df\arch;
use df\halo;

class Config extends core\Config {

    const ID = 'environment';
    const STORE_IN_MEMORY = true;
    const USE_ENVIRONMENT_ID_BY_DEFAULT = true;

    public function getDefaultValues() {
        return [
            'mode' => 'development',
            'binaryPaths' => [],
            'distributed' => false,
            'activeLocations' => [],
            'maintenanceMode' => false,
            'daemonsEnabled' => false,
            'daemonUser' => $this->_extrapolateDaemonUser(),
            'daemonGroup' => $this->_extrapolateDaemonGroup()
        ];
    }

    protected function _sanitizeValuesOnCreate() {
        try {
            arch\node\task\Manager::getInstance()->invoke('git/init-gitignore');
        } catch(\Exception $e) {}
    }


// Mode
    public function setMode($mode) {
        $this->values->mode = Mode::normalize($mode);
        return $this;
    }

    public function getMode() {
        return $this->values->get('mode', 'testing');
    }



// Vendor binary paths
    public function setBinaryPath($id, $path) {
        $this->values->binaryPaths->{$id} = $path;
        return $this;
    }

    public function getBinaryPath($id) {
        return $this->values->binaryPaths->get($id, $id);
    }

// Load balancing
    public function isDistributed(bool $flag=null) {
        if($flag !== null) {
            $this->values->distributed = $flag;
            return $this;
        }

        return (bool)$this->values['distributed'];
    }

// Locations
    public function getActiveLocations() {
        if(!isset($this->values->activeLocations)) {
            return [];
        }

        return $this->values->activeLocations->toArray();
    }


// Maintenance
    public function isMaintenanceMode(bool $flag=null) {
        if($flag !== null) {
            $this->values->maintenanceMode = $flag;
            return $this;
        }

        return $this->values['maintenanceMode'];
    }


// Daemons
    public function canUseDaemons(bool $flag=null) {
        if($flag !== null) {
            $this->values->daemonsEnabled = $flag;
            return $this;
        }

        return (bool)$this->values['daemonsEnabled'];
    }

    public function setDaemonUser($user) {
        if(is_numeric($user)) {
            $system = halo\system\Base::getInstance();
            $user = $system->userIdToUserName($user);
        }

        if(empty($user)) {
            throw new core\InvalidArgumentException(
                'Invalid username detected'
            );
        }

        $this->values->daemonUser = $user;
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
            throw new core\InvalidArgumentException(
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
}
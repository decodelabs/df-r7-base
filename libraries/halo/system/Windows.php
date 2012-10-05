<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\system;

use df;
use df\core;
use df\halo;

class Windows extends Base {
    
    protected static $_wmi;
    
    protected $_platformType = 'Windows';
    protected $_osDistribution;
    
    protected function __construct($osName) {
        parent::__construct($osName);
        
        self::$_wmi = new \COM("winmgmts:{impersonationLevel=impersonate}!\\\\.\\root\\cimv2"); 
    }
    
    public function getWMI() {
        return self::$_wmi;
    }
    
    public function getOSDistribution() {
        if($this->_osDistribution === null) {
            $this->_osDistribution = $this->_lookupOSDistribution();
        }
        
        return $this->_osDistribution;
    }
    
    private function _lookupOSDistribution() {
        $timer = new core\time\Timer();
        $res = self::$_wmi->ExecQuery('SELECT * FROM Win32_OperatingSystem');
        
        foreach($res as $os) {
            return $os->Caption;
        }
        
        return 'Windows NT';
    }


// Users
    public function userIdToUserName($id) {
        return $id;
    }

    public function userNameToUserId($name) {
        return $name;
    }

    public function groupIdToGroupName($id) {
        return $id;
    }

    public function groupNameToGroupId($name) {
        return $name;
    }
}
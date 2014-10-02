<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\process;

use df;
use df\core;
use df\halo;

class Windows extends Base {
    
    const EXIT_SUCCESS = 0;
    const EXIT_ACCESS_DENIED = 2;
    const EXIT_PRIVILEGES = 3;
    const EXIT_UNKNOWN_FAILURE = 8;
    const EXIT_PATH_NOT_FOUND = 9;
    const EXIT_INVALID_PARAMETER = 21;
    
    public static function getCurrentProcessId() {
        return getmypid();
    }
    
    public function isAlive() {
        $wmi = $this->_getWmi();
        $procs = $wmi->ExecQuery('SELECT * FROM Win32_Process WHERE ProcessId=\''.$this->getProcessId().'\'');
        
        foreach($procs as $process) {
            return true;
        }
        
        return false;
    }
    
    public function kill() {
        $wmi = $this->_getWmi();
        $procs = $wmi->ExecQuery('SELECT * FROM Win32_Process WHERE ProcessId=\''.$this->getProcessId().'\'');
        $output = 0;
        
        foreach($procs as $process) {
            $output = $process->Terminate();
            break;
        }
        
        return $output == 0;
    }

    public function sendSignal($signal) {
        return false;
    }

    public function isPrivileged() {
        return true;
    }
    
    protected function _getWmi() {
        $system = halo\system\Base::getInstance();
        
        if(!$system instanceof halo\system\Windows) {
            throw new RuntimeException(
                'System doesn\'t appear to be Windows afterall. This is bad!'
            );
        }
        
        return $system->getWmi();
    }
}
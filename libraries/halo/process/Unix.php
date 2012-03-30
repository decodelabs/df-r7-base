<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\process;

use df;
use df\core;
use df\halo;

class Unix extends Base {
    
    public static function getCurrentProcessId() {
        if(extension_loaded('posix')) {
            return posix_getpid();
        } else {
            return getmypid();
        }
    }
    
    public function isAlive() {
        if(extension_loaded('posix')) {
            return posix_kill($this->_processId, 0);
        } else {
            exec('ps -o pid --no-heading --pid '.escapeshellarg($this->_processId), $output);
            return isset($output[0]);
        }
    }
    
    public function kill() {
        if(extension_loaded('posix')) {
            return posix_kill($this->_processId, SIGTERM);
        } else {
            exec('kill -'.SIGTERM.' '.$this->_processId);
            return true;
        }
    }
    
    public function isPrivileged() {
        return $this->getUserId() == 0;
    }
}
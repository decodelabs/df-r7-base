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
    
    public static function isProcessIdLive($pid) {
        if(extension_loaded('posix')) {
            return posix_kill($pid, 0);
        } else {
            exec('ps -o pid --no-heading --pid '.escapeshellarg($pid), $output);
            return isset($output[0]);
        }
    }

    public static function getCurrentProcessId() {
        if(extension_loaded('posix')) {
            return posix_getpid();
        } else {
            return getmypid();
        }
    }
    
    public function isAlive() {
        return self::isProcessIdLive($this->_processId);
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
        if($this instanceof IManagedProcess) {
            $uid = $this->getOwnerId();
        } else if(extension_loaded('posix')) {
            $uid = posix_geteuid();
        } else {
            $uid = getmyuid();
        }

        return $uid == 0;
    }
}
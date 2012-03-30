<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\process;

use df;
use df\core;
use df\halo;

class UnixManaged extends Unix implements IManagedProcess {
    
    protected $_parentProcessId;
    
    public function getParentProcessId() {
        if($this->_parentProcessId === null) {
            if(extension_loaded('posix')) {
                $this->_parentProcessId = posix_getppid();
            } else {
                exec('ps -o ppid --no-heading --pid '.escapeshellarg($this->_processId), $output);
                
                if(isset($output[0])) {
                    $this->_parentProcessId = (int)$output[0];
                } else {
                    throw new RuntimeException(
                        'Unable to extract parent process id'
                    );
                }
            }
        }
        
        return $this->_parentProcessId;
    }
    
    
    public function setPriority($priority) {
        core\stub();
    }
    
    public function getPriority() {
        core\stub();
    }
    
    public function getOwnerId() {
        if(extension_loaded('posix')) {
            return posix_geteuid();
        } else {
            exec('ps -o euid --no-heading --pid '.escapeshellarg($this->_processId), $output);
            
            if(isset($output[0])) {
                return (int)trim($output[0]);
            } else {
                throw new RuntimeException(
                    'Unable to extract process owner id'
                );
            }
        }
    }
    
    public function getOwnerName() {
        if(extension_loaded('posix')) {
            $output = posix_getpwuid($this->getOwnerId());
            return $output['name'];
        } else {
            exec('getent passwd '.escapeshellarg($this->getOwnerId()), $output);
            
            if(isset($output[0])) {
                $parts = explode(':', $output[0]);
                return array_shift($parts);
            } else {
                throw new RuntimeException(
                    'Unable to extract process owner name'
                );
            }
        }
    }
    
    
    public function getGroupId() {
        if(extension_loaded('posix')) {
            return posix_getegid();
        } else {
            exec('ps -o egid --no-heading --pid '.escapeshellarg($this->_processId), $output);
            
            if(isset($output[0])) {
                return (int)trim($output[0]);
            } else {
                throw new RuntimeException(
                    'Unable to extract process owner id'
                );
            }
        }
    }
    
    public function getGroupName() {
        if(extension_loaded('posix')) {
            $output = posix_getgrgid($this->getGroupId());
            return $output['name'];
        } else {
            exec('getent group '.escapeshellarg($this->getGroupId()), $output);
            
            if(isset($output[0])) {
                $parts = explode(':', $output[0]);
                return array_shift($parts);
            } else {
                throw new RuntimeException(
                    'Unable to extract process group name'
                );
            }
        }
    }
    
    
    public function canFork() {
        return extension_loaded('pcntl');
    }
    
    public function fork() {
        core\stub();
    }
    
    public function delegate() {
        core\stub();
    }
}
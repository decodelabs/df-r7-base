<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\process;

use df;
use df\core;
use df\halo;

class WindowsManaged extends Windows implements IManagedProcess {
    
    protected $_parentProcessId;
    
    public function getParentProcessId() {
        if($this->_parentProcessId === null) {
            $wmi = $this->_getWmi();
            $procs = $wmi->ExecQuery('SELECT * FROM Win32_Process WHERE ProcessId=\''.$this->getProcessId().'\'');
            
            foreach($procs as $process) {
                $this->_parentProcessId = $process->ParentProcessId;
                break;
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
        $wmi = $this->_getWmi();
        $procs = $wmi->ExecQuery('SELECT * FROM Win32_Process WHERE ProcessId=\''.$this->getProcessId().'\'');
        
        foreach($procs as $process) {
            $owner = new \Variant(null);
            $process->GetOwner($owner);
            return (string)$owner;
        }
        
        return null;
    }
    
    public function getOwnerName() {
        return $this->getOwnerId();
    }
    
    public function canFork() {
        return false;
    }
    
    public function fork() {
        throw new RuntimeException(
            'PHP on windows is currently not able to fork processes'
        );
    }
    
    public function delegate() {
        core\stub();
    }
}
<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\process;

use df;
use df\core;
use df\halo;

class WindowsManaged extends Windows implements IManagedProcess
{
    use TPidFileProvider;

    protected $_parentProcessId;

    public function getParentProcessId()
    {
        if ($this->_parentProcessId === null) {
            $wmi = $this->_getWmi();
            $procs = $wmi->ExecQuery('SELECT * FROM Win32_Process WHERE ProcessId=\''.$this->getProcessId().'\'');

            foreach ($procs as $process) {
                $this->_parentProcessId = $process->ParentProcessId;
                break;
            }
        }

        return $this->_parentProcessId;
    }


    // Title
    public function setTitle(?string $title)
    {
        $this->_title = $title;
        return $this;
    }

    // Priority
    public function setPriority($priority)
    {
        Glitch::incomplete();
    }

    public function getPriority()
    {
        Glitch::incomplete();
    }


    // Identity
    public function setIdentity($uid, $gid)
    {
        return $this->setOwnerId($uid)->setGroupId($gid);
    }

    // Owner
    public function setOwnerId($id)
    {
        Glitch::incomplete($id);
    }

    public function getOwnerId()
    {
        $wmi = $this->_getWmi();
        $procs = $wmi->ExecQuery('SELECT * FROM Win32_Process WHERE ProcessId=\''.$this->getProcessId().'\'');

        foreach ($procs as $process) {
            $owner = new \Variant(null);
            $process->GetOwner($owner);
            return (string)$owner;
        }

        return null;
    }

    public function setOwnerName($name)
    {
        Glitch::incomplete($name);
    }

    public function getOwnerName()
    {
        return $this->getOwnerId();
    }


    // Group
    public function setGroupId($id)
    {
        Glitch::incomplete($id);
    }

    public function getGroupId()
    {
        Glitch::incomplete();
    }

    public function setGroupName($name)
    {
        Glitch::incomplete($name);
    }

    public function getGroupName()
    {
        Glitch::incomplete();
    }


    // Fork
    public function canFork()
    {
        return false;
    }

    public function fork()
    {
        throw new RuntimeException(
            'PHP on windows is currently not able to fork processes'
        );
    }

    public function delegate()
    {
        Glitch::incomplete();
    }
}

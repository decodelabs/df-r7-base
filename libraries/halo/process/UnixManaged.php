<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\process;

use df;
use df\core;
use df\halo;

class UnixManaged extends Unix implements IManagedProcess
{
    use TPidFileProvider;

    protected $_parentProcessId;

    public function kill()
    {
        if (($output = parent::kill()) && $this->_pidFile) {
            @unlink($this->_pidFile);
        }

        return $output;
    }

    public function getParentProcessId()
    {
        if ($this->_parentProcessId === null) {
            if (extension_loaded('posix')) {
                $this->_parentProcessId = posix_getppid();
            } else {
                exec('ps -o ppid --no-heading --pid '.escapeshellarg($this->_processId), $output);

                if (isset($output[0])) {
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

    // Title
    public function setTitle(?string $title)
    {
        $this->_title = $title;

        if ($title && extension_loaded('proctitle')) {
            setproctitle($title);
        }

        return $this;
    }

    // Priority
    public function setPriority($priority)
    {
        if (extension_loaded('pcntl')) {
            @pcntl_setpriority($priority, $this->_processId);
        }
    }

    public function getPriority()
    {
        if (extension_loaded('pcntl')) {
            return (int)@pcntl_getpriority($this->_processId);
        }

        return 0;
    }


    // Identity
    public function setIdentity($uid, $gid)
    {
        if (!is_numeric($uid)) {
            $uid = halo\system\Base::getInstance()->userNameToUserId($uid);
        }

        if (!is_numeric($gid)) {
            $gid = halo\system\Base::getInstance()->groupNameToGroupId($gid);
        }

        if (!extension_loaded('posix')) {
            throw new RuntimeException(
                'Unable to set process identity - posix not available'
            );
        }

        $doUid = $uid != $this->getOwnerId();
        $doGid = $gid != $this->getGroupId();
        $doPidFile = $this->_pidFile && is_file($this->_pidFile);

        if ($doGid && $doPidFile) {
            chgrp($this->_pidFile, $gid);
        }

        if ($doUid && $doPidFile) {
            chown($this->_pidFile, $uid);
        }

        if ($doGid) {
            if (!posix_setgid($gid)) {
                throw new RuntimeException('Set group failed');
            }
        }

        if ($doUid) {
            if (!posix_setuid($uid)) {
                throw new RuntimeException('Set owner failed');
            }
        }

        return $this;
    }

    // Owner
    public function setOwnerId($id)
    {
        if (!is_numeric($id)) {
            return $this->setOwnerName($id);
        }

        if (extension_loaded('posix')) {
            if ($id != $this->getOwnerId()) {
                if ($this->_pidFile && is_file($this->_pidFile)) {
                    chown($this->_pidFile, $id);
                }

                try {
                    posix_setuid($id);
                } catch (\Throwable $e) {
                    throw new RuntimeException('Set owner failed', 0, $e);
                }
            }
        } else {
            throw new RuntimeException(
                'Unable to set owner id - posix not available'
            );
        }

        return $this;
    }

    public function getOwnerId()
    {
        if (extension_loaded('posix')) {
            return posix_geteuid();
        } else {
            exec('ps -o euid --no-heading --pid '.escapeshellarg($this->_processId), $output);

            if (isset($output[0])) {
                return (int)trim($output[0]);
            } else {
                throw new RuntimeException(
                    'Unable to extract process owner id'
                );
            }
        }
    }

    public function setOwnerName($name)
    {
        return $this->setOwnerId(halo\system\Base::getInstance()->userNameToUserId($name));
    }

    public function getOwnerName()
    {
        if (extension_loaded('posix')) {
            $output = posix_getpwuid($this->getOwnerId());
            return $output['name'];
        } else {
            exec('getent passwd '.escapeshellarg($this->getOwnerId()), $output);

            if (isset($output[0])) {
                $parts = explode(':', $output[0]);
                return array_shift($parts);
            } else {
                throw new RuntimeException(
                    'Unable to extract process owner name'
                );
            }
        }
    }


    // Group
    public function setGroupId($id)
    {
        if (!is_numeric($id)) {
            return $this->setGroupName($id);
        }

        if (extension_loaded('posix')) {
            if ($id != $this->getGroupId()) {
                if ($this->_pidFile && is_file($this->_pidFile)) {
                    chgrp($this->_pidFile, $id);
                }

                try {
                    posix_setgid($id);
                } catch (\Throwable $e) {
                    throw new RuntimeException('Set group failed', 0, $e);
                }
            }
        } else {
            throw new RuntimeException(
                'Unable to set group id - posix not available'
            );
        }

        return $this;
    }

    public function getGroupId()
    {
        if (extension_loaded('posix')) {
            return posix_getegid();
        } else {
            exec('ps -o egid --no-heading --pid '.escapeshellarg($this->_processId), $output);

            if (isset($output[0])) {
                return (int)trim($output[0]);
            } else {
                throw new RuntimeException(
                    'Unable to extract process owner id'
                );
            }
        }
    }

    public function setGroupName($name)
    {
        return $this->setGroupId(halo\system\Base::getInstance()->groupNameToGroupId($name));
    }

    public function getGroupName()
    {
        if (extension_loaded('posix')) {
            $output = posix_getgrgid($this->getGroupId());
            return $output['name'];
        } else {
            exec('getent group '.escapeshellarg($this->getGroupId()), $output);

            if (isset($output[0])) {
                $parts = explode(':', $output[0]);
                return array_shift($parts);
            } else {
                throw new RuntimeException(
                    'Unable to extract process group name'
                );
            }
        }
    }



    // Fork
    public function canFork()
    {
        return extension_loaded('pcntl');
    }

    public function fork()
    {
        if (!$this->canFork()) {
            throw new RuntimeException(
                'This process is not capable of forking'
            );
        }

        $pid = pcntl_fork();

        if ($pid === -1) {
            throw new RuntimeException(
                'The process did not fork successfully'
            );
        } elseif ($pid) {
            // Parent
            $output = clone $this;
            $output->_processId = $pid;

            return $output;
        } else {
            // Child
            $this->_processId = self::getCurrentProcessId();
            return null;
        }
    }

    public function delegate()
    {
        Glitch::incomplete();
    }
}

<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\process;

use df;
use df\core;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}
class LogicException extends \LogicException implements IException {}


// Interfaces
interface IProcess {
    public static function getCurrentProcessId();
    public function getTitle();
    public function getProcessId();
    
    public function isAlive();
    public function kill();
    
    public function isPrivileged();
}


interface IManagedProcess extends IProcess {
    public function setTitle($title);
    public function getParentProcessId();
    public function setPriority($priority);
    public function getPriority();

    public function setIdentity($uid, $gid);
    
    public function setOwnerId($id);
    public function getOwnerId();

    public function setOwnerName($name);
    public function getOwnerName();

    public function setGroupId($id);
    public function getGroupId();

    public function setGroupName($name);
    public function getGroupName();

    public function hasPidFile();
    public function setPidFilePath($path);
    public function getPidFilePath();
    
    public function canFork();
    public function fork();
    public function delegate();
}

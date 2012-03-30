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
    public function getParentProcessId();
    public function setPriority($priority);
    public function getPriority();
    
    public function getOwnerId();
    public function getOwnerName();
    public function getGroupId();
    public function getGroupName();
    
    public function canFork();
    public function fork();
    public function delegate();
}
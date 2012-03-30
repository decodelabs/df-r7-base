<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\process\launcher;

use df;
use df\core;
use df\halo;

// Interfaces
interface ILauncher {
    public function setProcessName($name);
    public function getProcessName();
    public function setArgs($args);
    public function getArgs();
    public function setPath($path);
    public function getPath();
    public function isPrivileged();
    public function setTitle($title);
    public function getTitle();
    public function setPriority($priority);
    public function getPriority();
    
    public function launchBlocking();
    public function launchBackground();
    public function launchManaged();
}
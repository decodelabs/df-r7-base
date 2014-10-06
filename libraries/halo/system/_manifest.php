<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\system;

use df;
use df\core;
use df\halo;


// Exceptions
interface IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}


// Interfaces
interface ISystem {
    public function getPlatformType();
    public function getOSName();
    public function getOSDistribution();
    
    public function getOSVersion();
    public function getOSRelease();
    public function getHostName();
    
    public function getProcess();

    public function userIdToUserName($id);
    public function userNameToUserId($name);
    public function groupIdToGroupName($id);
    public function groupNameToGroupId($name);

    public function which($binaryName);
}
<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\system;

use df;
use df\core;
use df\halo;

class Unix extends Base {
    
    protected $_platformType = 'Unix';


// Users
    public function userIdToUserName($id) {
        if(extension_loaded('posix')) {
            if(!$output = posix_getpwuid($id)) {
                throw new InvalidArgumentException(
                    $id.' is not a valid user id'
                );
            }

            return $output['name'];
        } else {
            exec('getent passwd '.escapeshellarg($id), $output);
            
            if(isset($output[0])) {
                $parts = explode(':', $output[0]);
                return array_shift($parts);
            } else {
                throw new RuntimeException(
                    'Unable to extract owner name'
                );
            }
        }
    }

    public function userNameToUserId($name) {
        if(extension_loaded('posix')) {
            if(!$output = posix_getpwnam($name)) {
                throw new InvalidArgumentException(
                    $name.' is not a valid user name'
                );
            }

            return $output['uid'];
        } else {
            core\stub('no posix');
        }
    }

    public function groupIdToGroupName($id) {
        if(extension_loaded('posix')) {
            if(!$output = posix_getgrgid($id)) {
                throw new InvalidArgumentException(
                    $id.' is not a valid group id'
                );
            }

            return $output['name'];
        } else {
            exec('getent group '.escapeshellarg($id), $output);
            
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

    public function groupNameToGroupId($name) {
        if(extension_loaded('posix')) {
            if(!$output = posix_getgrnam($name)) {
                throw new InvalidArgumentException(
                    $name.' is not a valid group name'
                );
            }
            return $output['gid'];
        } else {
            core\stub('no posix');
        }
    }

    public function which($binaryName) {
        $result = halo\process\Base::launch('which', $binaryName)->getOutput();
        $result = trim($result);

        if(empty($result)) {
            return $binaryName;
        }
        
        return $result;
    }
}
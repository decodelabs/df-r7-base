<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\user\access\lock;

use df;
use df\core;
use df\user;
    
class Boolean implements user\IAccessLock {

	protected $_value = true;

	public function __construct($value) {
		$this->_value = (bool)$value;
	}

    public function getAccessLockDomain() {
    	return 'dynamic';
    }

    public function lookupAccessKey(array $keys) {
    	return null;
    }

    public function getDefaultAccess() {
    	return !$this->_value;
    }
}
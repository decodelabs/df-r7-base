<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\user\access\lock;

use df;
use df\core;
use df\user;

    
class Action implements user\IAccessLock {

    protected $_parentLock;
    protected $_action;

    public function __construct(user\IAccessLock $parentLock, $action) {
    	$this->_parentLock = $parentLock;
    	$this->_action = $action;
    }

    public function getParentLock() {
    	return $this->_parentLock;
    }

    public function getAction() {
    	return $this->_action;
    }

    public function getAccessLockDomain() {
        return $this->_parentLock->getAccessLockDomain();
    }

    public function lookupAccessKey(array $keys, $action=null) {
        if($action === null) {
        	$action = $this->_action;
        }

        return $this->_parentLock->lookupAccessKey($keys, $action);
    }

    public function getDefaultAccess($action=null) {
    	if($action === null) {
        	$action = $this->_action;
        }

    	return $this->_parentLock->getDefaultAccess($action);
    }

    public function getActionLock($action) {
    	$output = clone $this;
    	$output->_action = $action;

    	return $output;
    }

    public function getAccessLockId() {
        return $this->_parentLock->getAccessLockId().'#'.$this->_action;
    }
}
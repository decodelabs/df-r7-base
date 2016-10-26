<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flow\mailingList;

use df;
use df\core;
use df\flow;

class SubscribeResult implements ISubscribeResult {

    protected $_isSuccessful = false;
    protected $_isSubscribed = false;
    protected $_requiresManualInput = false;
    protected $_manualInputUrl = '';
    protected $_emailAddress;

    public function isSuccessful(bool $flag=null) {
        if($flag !== null) {
            $this->_isSuccessful = $flag;
            return $this;
        }

        return $this->_isSuccessful;
    }

    public function isSubscribed(bool $flag=null) {
        if($flag !== null) {
            $this->_isSubscribed = $flag;
            return $this;
        }

        return $this->_isSubscribed;
    }

    public function requiresManualInput(bool $flag=null) {
        if($flag !== null) {
            $this->_requiresManualInput = $flag;
            return $this;
        }

        return $this->_requiresManualInput;
    }

    public function setManualInputUrl(string $url=null) {
        $this->_manualInputUrl = $url;
        return $this;
    }

    public function getManualInputUrl() {
        return $this->_manualInputUrl;
    }

    public function setEmailAddress($address, $name=null) {
        if($address === null) {
            $this->_emailAddress = null;
        } else {
            $this->_emailAddress = flow\mail\Address::factory($address, $name);
        }

        return $this;
    }

    public function getEmailAddress() {
        return $this->_emailAddress;
    }
}
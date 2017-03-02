<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\mint\charge;

use df;
use df\core;
use df\mint;

class Result implements mint\IChargeResult {

    protected $_successful = false;
    protected $_cardAccepted = false;
    protected $_cardExpired = false;
    protected $_cardUnavailable = false;
    protected $_apiFailure = false;
    protected $_message;
    protected $_invalidFields = [];

    public function isSuccessful(bool $flag=null) {
        if($flag !== null) {
            $this->_successful = $flag;
            return $this;
        }

        return $this->_successful;
    }

    public function isCardAccepted(bool $flag=null) {
        if($flag !== null) {
            $this->_cardAccepted = $flag;
            return $this;
        }

        return $this->_cardAccepted;
    }

    public function isCardExpired(bool $flag=null) {
        if($flag !== null) {
            $this->_cardExpired = $flag;
            return $this;
        }

        return $this->_cardExpired;
    }

    public function isCardUnavailable(bool $flag=null) {
        if($flag !== null) {
            $this->_cardUnavailable = $flag;
            return $this;
        }

        return $this->_cardUnavailable;
    }

    public function isApiFailure(bool $flag=null) {
        if($flag !== null) {
            $this->_apiFailure = $flag;
            return $this;
        }

        return $this->_apiFailure;
    }

    public function setMessage(/*?string*/ $message) {
        $this->_message = $message;
        return $this;
    }

    public function getMessage()/*: ?string*/ {
        return $this->_message;
    }

    public function setInvalidFields(string ...$fields) {
        $this->_invalidFields = [];
        return $this->addInvalidFields(...$fields);
    }

    public function addInvalidFields(string ...$fields) {
        $this->_invalidFields = array_unique(array_merge($this->_invalidFields, $fields));
        return $this;
    }

    public function getInvalidFields(): array {
        return $this->_invalidFields;
    }
}
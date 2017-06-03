<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\payment\stripe2\filter;

use df;
use df\core;
use df\spur;
use df\mint;


// Availability
trait TFilter_Availability {

    protected $_availability;

    public function whereAvailableOn(?array $availability) {
        $this->_availability = $this->_normalizeDateFilter($availability);
        return $this;
    }

    public function getAvailability(): ?array {
        return $this->_availability;
    }

    protected function _applyAvailability(array &$output) {
        if($this->_availability !== null) {
            $output['available_on'] = $this->_availability;
        }
    }
}

// Created
trait TFilter_Created {

    protected $_created;

    public function whereCreated(?array $created) {
        $this->_created = $this->_normalizeDateFilter($created);
        return $this;
    }

    public function getCreated(): ?array {
        return $this->_created;
    }

    protected function _applyCreated(array &$output) {
        if($this->_created !== null) {
            $output['created'] = $this->_created;
        }
    }
}


// Currency
trait TFilter_Currency {

    protected $_currency;

    public function setCurrency(?string $currency) {
        if(!mint\Currency::isRecognizedCode($currency)) {
            throw core\Error::EArgument([
                'message' => 'Unsupported currency',
                'data' => $currency
            ]);
        }

        $this->_currency = $currency;
        return $this;
    }

    public function getCurrency(): ?string {
        return $this->_currency;
    }

    protected function _applyCurrency(array &$output) {
        if($this->_currency !== null) {
            $output['currency'] = $this->_currency;
        }
    }
}


// Customer
trait TFilter_Customer {

    protected $_customer;

    public function setCustomerId(?string $customerId) {
        $this->_currency = $customerId;
        return $this;
    }

    public function getCustomerId(): ?string {
        return $this->_customer;
    }

    protected function _applyCustomer(array &$output) {
        if($this->_customer !== null) {
            $output['customer'] = $this->_customer;
        }
    }
}


// Source
trait TFilter_Source {

    protected $_source = 'all';

    public function setSource(string $source) {
        switch($source) {
            case 'all':
            case 'alipay_account':
            case 'bank_account':
            case 'bitcoin_receiver':
            case 'card':
                break;

            default:
                throw core\Error::EArgument([
                    'message' => 'Invalid charge source',
                    'data' => $source
                ]);
        }

        $this->_source = $source;
        return $this;
    }

    public function getSource(): string {
        return $this->_source;
    }

    protected function _applySource(array &$output) {
        if($this->_source !== 'all') {
            $output['source'] = ['object' => $this->_source];
        }
    }
}
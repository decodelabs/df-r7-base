<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\payment\stripe;

use df;
use df\core;
use df\spur;
use df\mint;
    
class AccountDetails implements IAccountDetails, core\IDumpable {

    protected $_id;
    protected $_emailAddress;
    protected $_supportedCurrencies = [];
    protected $_canCharge = true;
    protected $_hasSubmittedDetails = true;
    protected $_isTransferEnabled = true;
    protected $_statementDescriptor;

    public function __construct(core\collection\ITree $data) {
        $this->_id = $data['id'];
        $this->_emailAddress = $data['email'];
        $this->_supportedCurrencies = array_map('strtoupper', $data->currencies_supported->toArray());
        $this->_canCharge = (bool)$data['charge_enabled'];
        $this->_hasSubmittedDetails = (bool)$data['details_submitted'];
        $this->_isTransferEnabled = (bool)$data['transfer_enabled'];
        $this->_statementDescriptor = $data['statement_descriptor'];
    }

    public function getId() {
        return $this->_id;
    }

    public function getEmailAddress() {
        return $this->_emailAddress;
    }

    public function getSupportedCurrencies() {
        return $this->_supportedCurrencies;
    }

    public function canCharge() {
        return $this->_canCharge;
    }

    public function hasSubmittedDetails() {
        return $this->_hasSubmittedDetails;
    }

    public function isTransferEnabled() {
        return $this->_isTransferEnabled;
    }

    public function getStatementDescriptor() {
        return $this->_statementDescriptor;
    }


// Dump
    public function getDumpProperties() {
        return [
            'id' => $this->_id,
            'emailAddress' => $this->_emailAddress,
            'supportedCurrencies' => implode(', ', $this->_supportedCurrencies),
            'canCharge' => $this->_canCharge,
            'hasSubmittedDetails' => $this->_hasSubmittedDetails,
            'isTransferEnabled' => $this->_isTransferEnabled,
            'statementDescriptor' => $this->_statementDescriptor
        ];
    }
}
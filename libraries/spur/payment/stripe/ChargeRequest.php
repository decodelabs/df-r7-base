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

class ChargeRequest implements IChargeRequest {

    use TApiObjectRequest;

    protected $_amount;
    protected $_customerId;
    protected $_card;
    protected $_description;
    protected $_emailAddress;
    protected $_shouldCapture = true;
    protected $_applicationFee;

    public function __construct(IMediator $mediator, $amount, mint\ICreditCardReference $card=null, $description=null, $emailAddress=null) {
        $this->_mediator = $mediator;
        $this->setAmount($amount);
        $this->setCard($card);
        $this->setDescription($description);
        $this->setEmailAddress($emailAddress);
    }

    public function setAmount($amount) {
        $this->_amount = mint\Currency::factory($amount, $this->_mediator->getDefaultCurrency());
        return $this;
    }

    public function getAmount() {
        return $this->_amount;
    }

    public function setCustomerId($id) {
        $this->_customerId = $id;
        return $this;
    }

    public function getCustomerId() {
        return $this->_customerId;
    }

    public function setCard(mint\ICreditCardReference $card=null) {
        $this->_card = $card;
        return $this;
    }

    public function getCard() {
        return $this->_card;
    }

    public function setDescription($description) {
        $this->_description = $description;
        return $this;
    }

    public function getDescription() {
        return $this->_description;
    }

    public function setEmailAddress($email) {
        $this->_emailAddress = $email;
        return $this;
    }

    public function getEmailAddress() {
        return $this->_emailAddress;
    }

    public function shouldCapture(bool $flag=null) {
        if($flag !== null) {
            $this->_shouldCapture = $flag;
            return $this;
        }

        return $this->_shouldCapture;
    }

    public function setApplicationFee($amount) {
        $this->_applicationFee = $amount;
        return $this;
    }

    public function getApplicationFee() {
        return $this->_applicationFee;
    }

    public function getSubmitArray() {
        $output = [
            'amount' => $this->_amount->getIntegerAmount(),
            'currency' => $this->_amount->getCode()
        ];

        if($this->_customerId !== null) {
            $output['customer'] = $this->_customerId;
        }

        if($this->_card) {
            $output['card'] = Mediator::cardReferenceToArray($this->_card);
        }

        if($this->_description !== null) {
            $output['description'] = $this->_description;
        }

        if($this->_emailAddress) {
            $output['receipt_email'] = $this->_emailAddress;
        }

        $output['capture'] = $this->_shouldCapture ? 'true' : 'false';

        if($this->_applicationFee > 0) {
            $output['application_fee'] = $this->_applicationFee;
        }

        return $output;
    }

    public function submit() {
        return $this->_mediator->createCharge($this);
    }
}
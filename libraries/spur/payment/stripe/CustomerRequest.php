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
    
class CustomerRequest implements ICustomerRequest {

    protected $_id;
    protected $_emailAddress;
    protected $_description;
    protected $_balance;
    protected $_card;
    protected $_couponCode;
    protected $_planId;
    protected $_quantity = 1;

    protected $_action = 'create';
    protected $_mediator;

    public function __construct(IMediator $mediator, $emailAddress=null, mint\ICreditCardReference $card=null, $description=null, $balance=null) {
        $this->_mediator = $mediator;
        $this->setEmailAddress($emailAddress);
        $this->setCard($card);
        $this->setDescription($description);
        $this->setBalance($balance);
    }

    public function getMediator() {
        return $this->_mediator;
    }

    public function setId($id) {
        $this->_id = $id;
        return $this;
    }

    public function getId() {
        return $this->_id;
    }

    public function setEmailAddress($email) {
        $this->_emailAddress = $email;
        return $this;
    }

    public function getEmailAddress() {
        return $this->_emailAddress;
    }

    public function setDescription($description) {
        $this->_description = $description;
        return $this;
    }

    public function getDescription() {
        return $this->_description;
    }

    public function setBalance($amount) {
        $this->_balance = mint\Currency::factory($amount);
        return $this;
    }

    public function getBalance() {
        return $this->_balance;
    }

    public function setCard(mint\ICreditCardReference $card=null) {
        $this->_card = $card;
        return $this;
    }

    public function getCard() {
        return $this->_card;
    }

    public function setCouponCode($code) {
        $this->_couponCode = $code;
        return $this;
    }

    public function getCouponCode() {
        return $this->_couponCode;
    }

    public function setPlanId($id) {
        $this->_planId = $id;
        return $this;
    }

    public function getPlanId() {
        return $this->_planId;
    }

    public function setQuantity($quantity) {
        $quantity = (int)$quantity;

        if($quantity <= 1) {
            $quantity = 1;
        }

        $this->_quantity = $quantity;
        return $this;
    }

    public function getQuantity() {
        return $this->_quantity;
    }


    public function setSubmitAction($action) {
        $this->_action = $action;
        return $this;
    }

    public function getSubmitAction() {
        return $this->_action;
    }

    public function getSubmitArray() {
        $output = [];

        if($this->_action == 'create' && $this->_id !== null) {
            $output['id'] = $this->_id;
        }

        if($this->_emailAddress !== null) {
            $output['email'] = $this->_emailAddress;
        }

        if($this->_description !== null) {
            $output['description'] = $this->_description;
        }

        if($this->_balance) {
            $output['account_balance'] = $this->_balance->getIntegerAmount();
        }

        if($this->_card) {
            $output['card'] = Mediator::cardReferenceToArray($this->_card);
        }

        if($this->_couponCode !== null) {
            $output['coupon'] = $this->_couponCode;
        }

        if($this->_planId !== null) {
            $output['plan'] = $this->_planId;
        }

        if($this->_quantity > 1) {
            $output['quantity'] = $this->_quantity;
        }

        return $output;
    }

    public function submit() {
        switch($this->_action) {
            case 'update':
                return $this->_mediator->updateCustomer($this);

            case 'create':
            default:
                return $this->_mediator->submitCustomer($this);
        }
    }
}

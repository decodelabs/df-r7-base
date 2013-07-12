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
    
class Charge implements ICharge {

    protected $_amount;
    protected $_customerId;
    protected $_card;
    protected $_description;
    protected $_shouldCapture = true;
    protected $_applicationFee;

    public function __construct($amount, mint\ICreditCardReference $card, $description=null) {
        $this->setAmount($amount);
        $this->setCard($card);
        $this->setDescription($description);
    }

    public function setAmount($amount) {
        $this->_amount = mint\Currency::factory($amount);
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

    public function setCard(mint\ICreditCardReference $card) {
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

    public function shouldCapture($flag=null) {
        if($flag !== null) {
            $this->_shouldCapture = (bool)$flag;
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
}
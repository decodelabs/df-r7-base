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

class SubscriptionRequest implements ISubscriptionRequest, core\IDumpable {

    use TApiObjectRequest;

    protected $_customerId;
    protected $_planId;
    protected $_couponCode;
    protected $_shouldProrate = true;
    protected $_trialEndDate;
    protected $_card;
    protected $_quantity = 1;

    public function __construct(IMediator $mediator, $customerId, $planId, mint\ICreditCardReference $card=null, $quantity=1) {
        $this->_mediator = $mediator;
        $this->_submitAction = 'update';

        $this->setCustomerId($customerId);
        $this->setPlanId($planId);
        $this->setCard($card);
        $this->setQuantity($quantity);
    }

// Customer
    public function setCustomerId($customer) {
        $this->_customerId = $customer;
        return $this;
    }

    public function getCustomerId() {
        return $this->_customerId;
    }


// Plan
    public function setPlanId($plan) {
        $this->_planId = $plan;
        return $this;
    }

    public function getPlanId() {
        return $this->_planId;
    }


// Coupon
    public function setCouponCode($code) {
        $this->_couponCode = $code;
        return $this;
    }

    public function getCouponCode() {
        return $this->_couponCode;
    }


// Prorate
    public function shouldProrate(bool $flag=null) {
        if($flag !== null) {
            $this->_shouldProrate = $flag;
            return $this;
        }

        return $this->_shouldProrate;
    }


// Trial
    public function setTrialEndDate($date) {
        if($date) {
            $date = core\time\Date::factory($date);
        }

        $this->_trialEndDate = $date;
        return $this;
    }

    public function getTrialEndDate() {
        return $this->_trialEndDate;
    }


// Card
    public function setCard(mint\ICreditCardReference $card=null) {
        $this->_card = $card;
        return $this;
    }

    public function getCard() {
        return $this->_card;
    }


// Quantity
    public function setQuantity($quantity) {
        $this->_quantity = (int)$quantity;

        if($this->_quantity <= 1) {
            $this->_quantity = 1;
        }

        return $this;
    }

    public function getQuantity() {
        return $this->_quantity;
    }


// Submit
    public function getSubmitArray() {
        $output = ['plan' => $this->_planId];

        if($this->_couponCode) {
            $output['coupon'] = $this->_couponCode;
        }

        $output['prorate'] = $this->_shouldProrate ? 'true' : 'false';

        if($this->_trialEndDate) {
            $output['trial_end'] = $this->_trialEndDate->toTimestamp();
        }

        if($this->_card) {
            $output['card'] = $this->_mediator->cardReferenceToArray($this->_card);
        }

        if($this->_quantity > 1) {
            $output['quantity'] = $this->_quantity;
        }

        return $output;
    }

    public function submit() {
        return $this->_mediator->updateSubscription($this);
    }


// Dump
    public function getDumpProperties() {
        return [
            'customerId' => $this->_customerId,
            'planId' => $this->_planId,
            'couponCode' => $this->_couponCode,
            'prorate' => $this->_shouldProrate,
            'trialEnd' => $this->_trialEndDate,
            'card' => $this->_card,
            'quantity' => $this->_quantity
        ];
    }
}
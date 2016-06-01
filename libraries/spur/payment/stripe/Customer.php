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
use df\user;

class Customer implements ICustomer, core\IDumpable {

    use TMediatorProvider;

    protected $_id;
    protected $_isLive = true;
    protected $_isDelinquent = false;
    protected $_creationDate;
    protected $_emailAddress;
    protected $_description;

    protected $_balance;
    protected $_coupon;
    protected $_discountStartDate;
    protected $_discountEndDate;
    protected $_subscription;

    protected $_cards = [];
    protected $_defaultCard;

    public function __construct(IMediator $mediator, core\collection\ITree $data) {
        $this->_mediator = $mediator;

        $this->_id = $data['id'];
        $this->_isLive = (bool)$data['livemode'];
        $this->_isDelinquent = (bool)$data['delinquent'];
        $this->_creationDate = core\time\Date::factory($data['created']);
        $this->_emailAddress = $data['email'];
        $this->_description = $data['description'];

        $this->_balance = mint\Currency::fromIntegerAmount($data['account_balance'], $mediator->getDefaultCurrency());

        if(!$data->discount->isEmpty()) {
            $this->_coupon = new Coupon($mediator, $data->discount->coupon);
            $this->_discountStartDate = new core\time\Date($data->discount['start']);

            if($data->discount['end']) {
                $this->_discountEndDate = new core\time\Date($data->discount['end']);
            }
        }

        if(!$data->subscriptions->isEmpty()) {
            $data->subscriptions->data->{0}->customer = $this->_id;
            $this->_subscription = new Subscription($mediator, $data->subscriptions->data->{0});
        }

        foreach($data->cards->data as $row) {
            $card = new CreditCard($mediator, $row);
            $this->_cards[$row['id']] = $card;
        }

        $cardId = $data['default_card'];

        if(isset($this->_cards[$cardId])) {
            $this->_defaultCard = $this->_cards[$cardId];
        }
    }


// Details
    public function getId() {
        return $this->_id;
    }

    public function isLive() {
        return $this->_isLive;
    }

    public function isDelinquent() {
        return $this->_isDelinquent;
    }

    public function getCreationDate() {
        return $this->_creationDate;
    }

    public function getEmailAddress() {
        return $this->_emailAddress;
    }

    public function getDescription() {
        return $this->_description;
    }


// Balance
    public function getBalance() {
        return $this->_balance;
    }

    public function isInCredit() {
        return $this->_balance->getAmount() < 0;
    }

    public function owesPayment() {
        return $this->_balance->getAmount > 0;
    }


// Discount
    public function hasDiscount() {
        return $this->_coupon !== null;
    }

    public function getCoupon() {
        return $this->_coupon;
    }

    public function getDiscountStartDate() {
        return $this->_discountStartDate;
    }

    public function getDiscountEndDate() {
        return $this->_discountEndDate;
    }

    public function endDiscount() {
        $this->_mediator->endDiscount($this);
        $this->_coupon = null;
        $this->_discountStartDate = null;
        $this->_discountEndDate = null;

        return $this;
    }


// Subscription
    public function hasSubscription() {
        return $this->_subscription !== null;
    }

    public function getSubscription() {
        return $this->_subscription;
    }

    public function newSubscriptionRequest($planId, mint\ICreditCardReference $card=null, $quantity=1) {
        return $this->_mediator->newSubscriptionRequest($this->_id, $planId, $card, $quantity);
    }

    public function updateSubscription(ISubscriptionRequest $request) {
        $request->setCustomerId($this->_id);
        $this->_subscription = $this->_mediator->updateSubscription($request);
        return $this;
    }

    public function cancelSubscription($atPeriodEnd=false) {
        if($this->_subscription) {
            $this->_subscription->cancel($atPeriodEnd);

            if(!$atPeriodEnd) {
                $this->_subscription = null;
            }
        }

        return $this;
    }


// Cards
    public function getCards() {
        return $this->_cards;
    }

    public function getCard($id) {
        if(isset($this->_cards[$id])) {
            return $this->_cards[$id];
        }
    }

    public function countCards() {
        return count($this->_cards);
    }

    public function getDefaultCard() {
        return $this->_defaultCard;
    }



// Submit
    public function update() {
        return $this->_mediator->newCustomerRequest()
            ->setTargetCustomer($this)
            ->setSubmitAction('update')
            ->setId($this->_id);
    }

    public function delete() {
        return $this->_mediator->deleteCustomer($this);
    }

    public function createCard(mint\ICreditCard $card) {
        return $this->_mediator->createCard($this, $card);
    }

    public function updateCard($id, mint\ICreditCard $card) {
        if($card instanceof ICreditCard) {
            $data = $this->_mediator->updateCard($this, $id, $card, true);
            $card->__construct($this->_mediator, $data);
            return $card;
        } else {
            return $this->_mediator->updateCard($this, $id, $card);
        }
    }

    public function deleteCard($id) {
        $this->_mediator->deleteCard($this, $id);
        return $this;
    }



// Dump
    public function getDumpProperties() {
        $output = [
            'id' => $this->_id,
            'isLive' => $this->_isLive,
            'isDelinquent' => $this->_isDelinquent,
            'creationDate' => $this->_creationDate,
            'emailAddress' => $this->_emailAddress,
            'description' => $this->_description,
            'balance' => $this->_balance,
            'coupon' => $this->_coupon,
            'discountStart' => $this->_discountStartDate,
            'discountEnd' => $this->_discountEndDate,
            'subscription' => $this->_subscription,
            'cards' => []
        ];

        foreach($this->_cards as $key => $card) {
            if($card === $this->_defaultCard) {
                $key = '* '.$key;
            }

            $output['cards'][$key] = $card;
        }

        return $output;
    }
}
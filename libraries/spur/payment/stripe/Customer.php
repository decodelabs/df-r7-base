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
    
class Customer implements ICustomer {

    protected $_id;
    protected $_isLive = true;
    protected $_isDelinquent = false;
    protected $_creationDate;
    protected $_emailAddress;
    protected $_description;

    protected $_balance;
    protected $_discount;
    protected $_subscription;

    protected $_cards = [];
    protected $_defaultCard;

    protected $_mediator;

    public function __construct(IMediator $mediator, core\collection\ITree $data) {
        $this->_mediator = $mediator;

        $this->_id = $data['id'];
        $this->_isLive = (bool)$data['livemode'];
        $this->_isDelinquent = (bool)$data['delinquent'];
        $this->_creationDate = core\time\Date::factory($data['created']);
        $this->_emailAddress = $data['email'];
        $this->_description = $data['description'];

        $this->_balance = mint\Currency::fromIntegerAmount($data['account_balance'], $mediator->getDefaultCurrencyCode());

        if(!$data->discount->isEmpty()) {
            $this->_discount = new Discount($this, $data->discount);
        }

        if(!$data->subscription->isEmpty()) {
            $this->_subscription = new Subscription($this, $data->subscription);
        }

        foreach($data->cards->data as $row) {
            $card = $mediator->cardDataToCardObject($row);
            $this->_cards[$row['id']] = $card;
        }

        $cardId = $data['default_card'];

        if(isset($this->_cards[$cardId])) {
            $this->_defaultCard = $this->_cards[$cardId];
        }
    }

    public function getMediator() {
        return $this->_mediator;
    }

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


    public function getBalance() {
        return $this->_balance;
    }

    public function isInCredit() {
        return $this->_balance->getAmount() < 0;
    }

    public function owesPayment() {
        return $this->_balance->getAmount > 0;
    }


    public function hasDiscount() {
        return $this->_discount !== null;
    }

    public function getDiscount() {
        return $this->_discount;
    }


    public function hasSubscription() {
        return $this->_subscription !== null;
    }

    public function getSubscription() {
        return $this->_subscription;
    }


    public function getCards() {
        return $this->_cards;
    }

    public function countCards() {
        return count($this->_cards);
    }

    public function getDefaultCard() {
        return $this->_defaultCard;
    }


    public function update() {
        return $this->_mediator->newCustomerRequest()
            ->setSubmitAction('update')
            ->setId($this->_id);
    }

    public function delete() {
        return $this->_mediator->deleteCustomer($this);
    }
}


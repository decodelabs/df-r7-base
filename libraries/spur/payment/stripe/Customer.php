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

    public function getId();
    public function isLive();
    public function isDelinquent();
    public function getCreationDate();
    public function getEmailAddress();
    public function getDescription();

    public function getBalance();
    public function isInCredit();
    public function owesPayment();

    public function hasDiscount();
    public function getDiscount();

    public function hasSubscription();
    public function getSubscription();

    public function getCards();
    public function countCards();
    public function getDefaultCard();

    public function __construct(IMediator $mediator, core\collection\ITree $data) {
        $this->_mediator = $mediator;

        core\dump($data);
    }
}


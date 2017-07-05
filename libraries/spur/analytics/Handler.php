<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\analytics;

use df;
use df\core;
use df\spur;
use df\aura;
use df\user;
use df\mint;

class Handler implements IHandler {

    const AVAILABLE_USER_ATTRIBUTES = [
        'id', 'email', 'fullName', 'nickName', 'joinDate', 'loginDate',
        'isLoggedIn', 'status', 'country', 'language', 'timezone'
    ];

    protected $_url;
    protected $_title;
    protected $_userAttributes = [];
    protected $_events = [];
    protected $_eCommerceTransactions = [];
    protected $_adapters = [];

    public static function getAvailableUserAttributes() {
        return self::AVAILABLE_USER_ATTRIBUTES;
    }

    public static function factory() {
        return new self(true);
    }

    public function __construct($addConfigAdapters=false) {
        $this->addConfigAdapters();
    }

    public function apply(aura\view\IHtmlView $view) {
        foreach($this->_adapters as $adapter) {
            $options = new core\collection\InputTree($adapter->getOptions());
            $adapter->validateOptions($options);

            if($options->isValid()) {
                $adapter->apply($this, $view);
            }
        }

        return $this;
    }


// Adapters
    public function addConfigAdapters() {
        foreach(spur\analytics\adapter\Base::loadAllFromConfig(true) as $adapter) {
            $this->addAdapter($adapter);
        }

        return $this;
    }

    public function addAdapter(IAdapter $adapter) {
        $this->_adapters[$adapter->getName()] = $adapter;
        return $this;
    }

    public function getAdapter($name) {
        if(isset($this->_adapters[$name])) {
            return $this->_adapters[$name];
        }

        return null;
    }

    public function getAdapters() {
        return $this->_adapters;
    }


// Url
    public function setUrl($url) {
        $this->_url = $url;
        return $this;
    }

    public function getUrl() {
        return $this->_url;
    }


// Title
    public function setTitle(?string $title) {
        $this->_title = $title;
        return $this;
    }

    public function getTitle(): ?string {
        return $this->_title;
    }

// User attributes
    public function setUserAttributes(array $attributes) {
        return $this->clearUserAttributes()->addUserAttributes($attributes);
    }

    public function addUserAttributes(array $attributes) {
        foreach($attributes as $key => $value) {
            $this->setUserAttribute($key, $value);
        }

        return $this;
    }

    public function getDefinedUserAttributes(array $attributes, $includeCustom=true) {
        $output = $includeCustom ? $this->_userAttributes : [];

        foreach($attributes as $attribute) {
            if(!in_array($attribute, self::AVAILABLE_USER_ATTRIBUTES)) {
                continue;
            }

            if(isset($this->_userAttributes[$attribute])) {
                $output[$attribute] = $this->_userAttributes[$attribute];
            } else {
                $client = user\Manager::getInstance()->getClient();

                switch($attribute) {
                    case 'id':
                        $output[$attribute] = $client->getId();
                        break;

                    case 'email':
                        $output[$attribute] = $client->getEmail();
                        break;

                    case 'fullName':
                        $output[$attribute] = $client->getFullName();
                        break;

                    case 'nickName':
                        $output[$attribute] = $client->getNickName();
                        break;

                    case 'joinDate':
                        $date = $client->getJoinDate();
                        $output[$attribute] = $date ? $date->format(core\time\Date::DBDATE) : null;
                        break;

                    case 'loginDate':
                        $date = $client->getLoginDate();
                        $output[$attribute] = $date ? $date->format(core\time\Date::DB) : null;
                        break;

                    case 'isLoggedIn':
                        $output[$attribute] = $client->isLoggedIn() ? 'true' : 'false';
                        break;

                    case 'status':
                        $output[$attribute] = $client->stateIdToName($client->getStatus());
                        break;

                    case 'country':
                        $output[$attribute] = $client->getCountry();
                        break;

                    case 'language':
                        $output[$attribute] = $client->getLanguage();
                        break;

                    case 'timezone':
                        $output[$attribute] = $client->getTimezone();
                        break;
                }
            }
        }

        return $output;
    }

    public function setUserAttribute($key, $value) {
        $this->_userAttributes[$key] = $value;
        return $this;
    }

    public function getUserAttribute($key) {
        if(isset($this->_userAttributes[$key])) {
            return $this->_userAttributes[$key];
        }

        return null;
    }

    public function getUserAttributes() {
        return $this->_userAttributes;
    }

    public function removeUserAttributes($key) {
        unset($this->_userAttributes[$key]);
        return $this;
    }

    public function clearUserAttributes() {
        $this->_userAttributes = [];
        return $this;
    }


// Events
    public function setEvents(array $events) {
        return $this->clearEvents()->addEvents($events);
    }

    public function addEvents(array $events) {
        foreach($events as $event) {
            $this->addEvent($event);
        }

        return $this;
    }

    public function addEvent($event, $name=null, $label=null, array $attributes=null) {
        if(!$event instanceof IEvent) {
            $event = new Event($event, $name, $label, $attributes);
        }

        $this->_events[$event->getUniqueId()] = $event;
        return $event;
    }

    public function getEvents() {
        return $this->_events;
    }

    public function clearEvents() {
        $this->_events = [];
        return $this;
    }


// ECommerce
    public function setECommerceTransactions(array $transactions) {
        return $this->clearECommerceTransactions()->addECommerceTransactions($transactions);
    }

    public function addECommerceTransactions(array $transactions) {
        foreach($transactions as $transaction) {
            $this->addECommerceTransaction($transaction);
        }

        return $this;
    }

    public function addECommerceTransaction($transaction, mint\ICurrency $amount=null, $affiliation=null, mint\ICurrency $shipping=null, mint\ICurrency $tax=null) {
        if(!$transaction instanceof IECommerceTransaction) {
            if(!$amount) {
                throw new InvalidArgumentException('ECommerce transaction amount cannot be empty');
            }

            $transaction = new ECommerceTransaction(
                $transaction, $amount, $affiliation, $shipping, $tax
            );
        }

        $this->_eCommerceTransactions[$transaction->getId()] = $transaction;
        return $transaction;
    }

    public function getECommerceTransactions() {
        return $this->_eCommerceTransactions;
    }

    public function clearECommerceTransactions() {
        $this->_eCommerceTransactions = [];
        return $this;
    }
}

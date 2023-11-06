<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\spur\analytics;

use DecodeLabs\Dictum;
use DecodeLabs\Disciple;
use DecodeLabs\Exceptional;
use DecodeLabs\R7\Mint\Currency as MintCurrency;
use df\aura;
use df\core;
use df\spur;
use df\user;

class Handler implements IHandler
{
    public const AVAILABLE_USER_ATTRIBUTES = [
        'id', 'email', 'fullName', 'nickName', 'joinDate', 'loginDate',
        'isLoggedIn', 'status', 'country', 'language', 'timezone'
    ];

    protected $_url;
    protected $_title;
    protected $_userAttributes = [];
    protected $_events = [];
    protected $_eCommerceTransactions = [];
    protected $_adapters = [];

    public static function getAvailableUserAttributes()
    {
        return self::AVAILABLE_USER_ATTRIBUTES;
    }

    public static function factory()
    {
        return new self(true);
    }

    public function __construct($addConfigAdapters = false)
    {
        if ($addConfigAdapters) {
            $this->addConfigAdapters();
        }
    }

    public function apply(aura\view\IHtmlView $view)
    {
        foreach ($this->_adapters as $adapter) {
            $options = new core\collection\InputTree($adapter->getOptions());
            $adapter->validateOptions($options);

            if ($options->isValid()) {
                try {
                    $adapter->apply($this, $view);
                } catch (\Throwable $e) {
                    core\logException($e);
                }
            }
        }

        return $this;
    }


    // Adapters
    public function addConfigAdapters()
    {
        foreach (spur\analytics\adapter\Base::loadAllFromConfig(true) as $adapter) {
            $this->addAdapter($adapter);
        }

        return $this;
    }

    public function addAdapter(IAdapter $adapter)
    {
        $this->_adapters[$adapter->getName()] = $adapter;
        return $this;
    }

    public function getAdapter($name)
    {
        if (isset($this->_adapters[$name])) {
            return $this->_adapters[$name];
        }

        return null;
    }

    public function getAdapters()
    {
        return $this->_adapters;
    }


    // Url
    public function setUrl($url)
    {
        $this->_url = $url;
        return $this;
    }

    public function getUrl()
    {
        return $this->_url;
    }


    // Title
    public function setTitle(?string $title)
    {
        $this->_title = $title;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->_title;
    }

    // User attributes
    public function setUserAttributes(array $attributes)
    {
        return $this->clearUserAttributes()->addUserAttributes($attributes);
    }

    public function addUserAttributes(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->setUserAttribute($key, $value);
        }

        return $this;
    }

    public function getDefinedUserAttributes(array $attributes, $includeCustom = true)
    {
        $output = $includeCustom ? $this->_userAttributes : [];

        foreach ($attributes as $attribute) {
            if (!in_array($attribute, self::AVAILABLE_USER_ATTRIBUTES)) {
                continue;
            }

            if (isset($this->_userAttributes[$attribute])) {
                $output[$attribute] = $this->_userAttributes[$attribute];
            } else {
                switch ($attribute) {
                    case 'id':
                        $output[$attribute] = Disciple::getId();
                        break;

                    case 'joinDate':
                        $output[$attribute] = Dictum::$time->format(Disciple::getRegistrationDate(), core\time\Date::DBDATE, 'UTC');
                        break;

                    case 'loginDate':
                        $output[$attribute] = Dictum::$time->format(Disciple::getLastLoginDate(), core\time\Date::DB, 'UTC');
                        break;

                    case 'isLoggedIn':
                        $output[$attribute] = Disciple::isLoggedIn() ? 'true' : 'false';
                        break;
                }
            }
        }

        return $output;
    }

    public function setUserAttribute($key, $value)
    {
        $this->_userAttributes[$key] = $value;
        return $this;
    }

    public function getUserAttribute($key)
    {
        if (isset($this->_userAttributes[$key])) {
            return $this->_userAttributes[$key];
        }

        return null;
    }

    public function getUserAttributes()
    {
        return $this->_userAttributes;
    }

    public function removeUserAttributes($key)
    {
        unset($this->_userAttributes[$key]);
        return $this;
    }

    public function clearUserAttributes()
    {
        $this->_userAttributes = [];
        return $this;
    }


    // Events
    public function setEvents(array $events)
    {
        return $this->clearEvents()->addEvents($events);
    }

    public function addEvents(array $events)
    {
        foreach ($events as $event) {
            $this->addEvent($event);
        }

        return $this;
    }

    public function addEvent($event, $name = null, $label = null, array $attributes = null)
    {
        if (!$event instanceof IEvent) {
            $event = new Event($event, $name, $label, $attributes);
        }

        $this->_events[$event->getUniqueId()] = $event;
        return $event;
    }

    public function getEvents()
    {
        return $this->_events;
    }

    public function clearEvents()
    {
        $this->_events = [];
        return $this;
    }


    // ECommerce
    public function setECommerceTransactions(array $transactions)
    {
        return $this->clearECommerceTransactions()->addECommerceTransactions($transactions);
    }

    public function addECommerceTransactions(array $transactions)
    {
        foreach ($transactions as $transaction) {
            $this->addECommerceTransaction($transaction);
        }

        return $this;
    }

    public function addECommerceTransaction($transaction, MintCurrency $amount = null, $affiliation = null, MintCurrency $shipping = null, MintCurrency $tax = null)
    {
        if (!$transaction instanceof IECommerceTransaction) {
            if (!$amount) {
                throw Exceptional::InvalidArgument(
                    'ECommerce transaction amount cannot be empty'
                );
            }

            $transaction = new ECommerceTransaction(
                $transaction,
                $amount,
                $affiliation,
                $shipping,
                $tax
            );
        }

        $this->_eCommerceTransactions[$transaction->getId()] = $transaction;
        return $transaction;
    }

    public function getECommerceTransactions()
    {
        return $this->_eCommerceTransactions;
    }

    public function clearECommerceTransactions()
    {
        $this->_eCommerceTransactions = [];
        return $this;
    }
}

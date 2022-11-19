<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\mint;

use df\mint;

class Customer implements mint\ICustomer
{
    protected $_id;
    protected $_localId;
    protected $_email;
    protected $_description;
    protected $_card;
    protected $_userId;
    protected $_delinquent = false;
    protected $_cachedSubscriptions;

    public function __construct(string $id = null, string $email = null, string $description = null, mint\ICreditCard $card = null)
    {
        $this->setId($id);
        $this->setEmailAddress($email);
        $this->setDescription($description);
        $this->setCard($card);
    }

    public function setId(?string $id)
    {
        $this->_id = $id;
        return $this;
    }

    public function getId(): ?string
    {
        return $this->_id;
    }

    public function setLocalId(?string $id)
    {
        $this->_localId = $id;
        return $this;
    }

    public function getLocalId(): ?string
    {
        return $this->_localId;
    }


    public function setEmailAddress(?string $email)
    {
        $this->_email = $email;
        return $this;
    }

    public function getEmailAddress(): ?string
    {
        return $this->_email;
    }

    public function setDescription(?string $description)
    {
        $this->_description = $description;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->_description;
    }


    // shipping

    public function setCard(?mint\ICreditCard $card)
    {
        $this->_card = $card;
        return $this;
    }

    public function getCard(): ?mint\ICreditCard
    {
        return $this->_card;
    }

    public function setUserId(?string $userId)
    {
        $this->_userId = $userId;
        return $this;
    }

    public function getUserId(): ?string
    {
        return $this->_userId;
    }

    public function isDelinquent(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_delinquent = $flag;
            return $this;
        }

        return $this->_delinquent;
    }


    public function setCachedSubscriptions(?array $subscriptions)
    {
        $this->_cachedSubscriptions = $subscriptions;
        return $this;
    }

    public function getCachedSubscriptions(): ?array
    {
        return $this->_cachedSubscriptions;
    }

    public function hasSubscriptionCache(): bool
    {
        return $this->_cachedSubscriptions !== null;
    }
}

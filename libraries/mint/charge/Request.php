<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\mint\charge;

use df\mint;

class Request implements mint\IChargeRequest
{
    protected $_amount;
    protected $_card;
    protected $_email;
    protected $_description;

    public function __construct(mint\ICurrency $amount, mint\ICreditCardReference $card, string $description = null, string $email = null)
    {
        $this->setAmount($amount)
            ->setCard($card)
            ->setDescription($description)
            ->setEmailAddress($email);
    }

    public function setAmount(mint\ICurrency $amount)
    {
        $this->_amount = $amount;
        return $this;
    }

    public function getAmount(): mint\ICurrency
    {
        return $this->_amount;
    }

    public function setCard(mint\ICreditCardReference $card)
    {
        $this->_card = $card;
        return $this;
    }

    public function getCard(): mint\ICreditCardReference
    {
        return $this->_card;
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

    public function setEmailAddress(?string $email)
    {
        $this->_email = $email;
        return $this;
    }

    public function getEmailAddress(): ?string
    {
        return $this->_email;
    }
}

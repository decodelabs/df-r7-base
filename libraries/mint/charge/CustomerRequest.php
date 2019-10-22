<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\mint\charge;

use df;
use df\core;
use df\mint;

class CustomerRequest extends Request implements mint\ICustomerChargeRequest
{
    protected $_customerId;

    public function __construct(mint\ICurrency $amount, mint\ICreditCardReference $card, string $customerId, string $description=null)
    {
        parent::__construct($amount, $card, $description);
        $this->setCustomerId($customerId);
    }

    public function setCustomerId(string $id)
    {
        $this->_customerId = $id;
        return $this;
    }

    public function getCustomerId(): string
    {
        return $this->_customerId;
    }
}

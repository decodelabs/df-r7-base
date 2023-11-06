<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\spur\analytics;

use DecodeLabs\R7\Mint\Currency as MintCurrency;
use df\core;

class ECommerceTransaction implements IECommerceTransaction
{
    use core\collection\TAttributeContainer;

    protected $_id;
    protected $_affiliation;
    protected $_amount;
    protected $_shippingAmount;
    protected $_taxAmount;

    public function __construct(string $id, MintCurrency $amount, $affiliation = null, MintCurrency $shipping = null, MintCurrency $tax = null)
    {
        $this->setId($id);
        $this->setAmount($amount);
        $this->setAffiliation($affiliation);
        $this->setShippingAmount($shipping);
        $this->setTaxAmount($tax);
    }

    public function setId(string $id)
    {
        $this->_id = $id;
        return $this;
    }

    public function getId(): string
    {
        return $this->_id;
    }

    public function setAffiliation($affiliation)
    {
        if (!strlen((string)$affiliation)) {
            $affiliation = null;
        }

        $this->_affiliation = $affiliation;
        return $this;
    }

    public function getAffiliation()
    {
        return $this->_affiliation;
    }

    public function setAmount(MintCurrency $amount)
    {
        $this->_amount = $amount;
        return $this;
    }

    public function getAmount()
    {
        return $this->_amount;
    }

    public function setShippingAmount(MintCurrency $shipping = null)
    {
        $this->_shippingAmount = $shipping;
        return $this;
    }

    public function getShippingAmount()
    {
        return $this->_shippingAmount;
    }

    public function setTaxAmount(MintCurrency $tax = null)
    {
        $this->_taxAmount = $tax;
        return $this;
    }

    public function getTaxAmount()
    {
        return $this->_taxAmount;
    }
}

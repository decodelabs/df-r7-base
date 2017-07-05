<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\analytics;

use df;
use df\core;
use df\spur;
use df\mint;

class ECommerceTransaction implements IECommerceTransaction {

    use core\collection\TAttributeContainer;

    protected $_id;
    protected $_affiliation;
    protected $_amount;
    protected $_shippingAmount;
    protected $_taxAmount;

    public function __construct(string $id, mint\ICurrency $amount, $affiliation=null, mint\ICurrency $shipping=null, mint\ICurrency $tax=null) {
        $this->setId($id);
        $this->setAmount($amount);
        $this->setAffiliation($affiliation);
        $this->setShippingAmount($shipping);
        $this->setTaxAmount($tax);
    }

    public function setId(string $id) {
        $this->_id = $id;
        return $this;
    }

    public function getId(): string {
        return $this->_id;
    }

    public function setAffiliation($affiliation) {
        if(!strlen($affiliation)) {
            $affiliation = null;
        }

        $this->_affiliation = $affiliation;
        return $this;
    }

    public function getAffiliation() {
        return $this->_affiliation;
    }

    public function setAmount(mint\ICurrency $amount) {
        $this->_amount = $amount;
        return $this;
    }

    public function getAmount() {
        return $this->_amount;
    }

    public function setShippingAmount(mint\ICurrency $shipping=null) {
        $this->_shippingAmount = $shipping;
        return $this;
    }

    public function getShippingAmount() {
        return $this->_shippingAmount;
    }

    public function setTaxAmount(mint\ICurrency $tax=null) {
        $this->_taxAmount = $tax;
        return $this;
    }

    public function getTaxAmount() {
        return $this->_taxAmount;
    }
}

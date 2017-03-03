<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\mint\charge;

use df;
use df\core;
use df\mint;

class Refund implements mint\IChargeRefund {

    protected $_id;
    protected $_amount;

    public function __construct(string $id, mint\ICurrency $amount=null) {
        $this->setId($id);
        $this->setAmount($amount);
    }

    public function setId(string $id) {
        $this->_id = $id;
        return $this;
    }

    public function getId(): string {
        return $this->_id;
    }

    public function setAmount(/*?mint\ICurrency*/ $amount) {
        $this->_amount = $amount;
        return $this;
    }

    public function getAmount()/*: ?mint\ICurrency*/ {
        return $this->_amount;
    }
}
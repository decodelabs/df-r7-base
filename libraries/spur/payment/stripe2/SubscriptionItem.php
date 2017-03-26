<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\payment\stripe2;

use df;
use df\core;
use df\spur;
use df\mint;

class SubscriptionItem implements ISubscriptionItem {

    protected $_itemId;
    protected $_planId;
    protected $_quantity = 1;
    protected $_delete = false;

    public function __construct(string $itemId=null, string $planId=null, int $quantity=1) {
        $this->setItemId($itemId);
        $this->setPlanId($planId);
        $this->setQuantity($quantity);
    }

    public function setItemId(?string $id) {
        $this->_itemId = $id;
        return $this;
    }

    public function getItemId(): ?string {
        return $this->_itemId;
    }


    public function setPlanId(?string $id) {
        $this->_planId = $id;
        return $this;
    }

    public function getPlanId(): ?string {
        return $this->_planId;
    }

    public function getKey(): string {
        return $this->_itemId.':'.$this->_planId;
    }


    public function setQuantity(int $quantity) {
        $this->_quantity = $quantity;
        return $this;
    }

    public function getQuantity(): int {
        return $this->_quantity;
    }


    public function shouldDelete(bool $flag=null) {
        if($flag !== null) {
            $this->_delete = $flag;
            return $this;
        }

        return $this->_delete;
    }
}
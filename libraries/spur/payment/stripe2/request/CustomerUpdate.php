<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\payment\stripe2\request;

use df;
use df\core;
use df\spur;
use df\mint;

class CustomerUpdate extends CustomerCreate implements spur\payment\stripe2\ICustomerUpdateRequest {

/*
    ?account_balance
    ?business_vat_id
    ?coupon
    ?description
    ?email
    ?metadata
    ?shipping
    ?source
*/

    protected $_customerId;

    public function __construct(string $customerId) {
        $this->setCustomerId($customerId);
    }


    public function setCustomerId(string $id) {
        $this->_customerId = $id;
        return $this;
    }

    public function getCustomerId(): string {
        return $this->_customerId;
    }
}
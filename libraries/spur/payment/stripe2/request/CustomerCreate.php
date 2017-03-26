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

class CustomerCreate implements spur\payment\stripe2\ICustomerCreateRequest {

    use TRequest_Description;
    use TRequest_Email;
    use TRequest_Metadata;
    use TRequest_Shipping;
    use TRequest_Source;

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

    protected $_balance;
    protected $_vatId;
    protected $_coupon;

    public function __construct(string $emailAddress=null, string $description=null) {
        $this->setEmailAddress($emailAddress);
        $this->setDescription($description);
    }

    public function setBalance(?mint\ICurrency $balance) {
        $this->_balance = $balance;
        return $this;
    }

    public function getBalance(): ?mint\ICurrency {
        return $this->_balance;
    }


    public function setVatId(?string $id) {
        $this->_vatId = $id;
        return $this;
    }

    public function getVatId(): ?string {
        return $this->_vatId;
    }


    public function setCouponId(?string $id) {
        $this->_coupon = $id;
        return $this;
    }

    public function getCouponId(): ?string {
        return $this->_coupon;
    }


    public function toArray(): array {
        $output = [];

        if($this->_balance !== null) {
            $output['account_balance'] = $this->_balance->getIntegerAmount();
        }

        if($this->_vatId !== null) {
            $output['business_vat_id'] = $this->_vatId;
        }

        if($this->_coupon !== null) {
            $output['coupon'] = $this->_coupon;
        }

        $this->_applyDescription($output);
        $this->_applyEmail($output);
        $this->_applyMetadata($output);
        $this->_applyShipping($output);
        $this->_applySource($output);

        return $output;
    }
}
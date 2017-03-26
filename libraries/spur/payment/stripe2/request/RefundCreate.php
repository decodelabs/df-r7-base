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

class RefundCreate implements spur\payment\stripe2\IRefundCreateRequest {

    use TRequest_ChargeId;
    use TRequest_Metadata;

/*
    charge
    ?amount
    ?metadata
    ?reason
    ?refund_application_fee
    ?reverse_transfer
*/

    protected $_amount;
    protected $_reason;
    protected $_includeApplicationFee = false;
    protected $_reverseTransfer = false;

    public function __construct(string $chargeId, string $reason=null) {
        $this->setChargeId($chargeId);
    }

    public function setAmount(?mint\ICurrency $amount) {
        $this->_amount = $amount;
        return $this;
    }

    public function getAmount(): ?mint\ICurrency {
        return $this->_amount;
    }


    public function setReason(?string $reason) {
        $this->_reason = $reason;
        return $this;
    }

    public function getReason(): ?string {
        return $this->_reason;
    }

    public function shouldIncludeApplicationFee(bool $flag=null) {
        if($flag !== null) {
            $this->_includeApplicationFee = $flag;
            return $this;
        }

        return $this->_includeApplicationFee;
    }

    public function shouldReverseTransfer(bool $flag=null) {
        if($flag !== null) {
            $this->_reverseTransfer = $flag;
            return $this;
        }

        return $this->_reverseTransfer;
    }


    public function toArray(): array {
        $output = [
            'charge' => $this->_chargeId
        ];

        if($this->_amount !== null) {
            $output['amount'] = $this->_amount->getIntegerAmount();
        }

        if($this->_reason !== null) {
            $output['reason'] = $this->_reason;
        }

        if($this->_includeApplicationFee) {
            $output['refund_application_fee'] = 'true';
        }

        if($this->_reverseTransfer) {
            $output['reverse_transfer'] = 'true';
        }

        $this->_applyMetadata($output);

        return $output;
    }
}
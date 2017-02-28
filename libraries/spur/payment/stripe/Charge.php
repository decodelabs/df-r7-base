<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\payment\stripe;

use df;
use df\core;
use df\spur;
use df\mint;
use df\user;

class Charge implements ICharge {

    use TMediatorProvider;

    protected $_id;
    protected $_amount;
    protected $_creationDate;
    protected $_description;

    protected $_isLive = true;
    protected $_isPaid = true;
    protected $_failureException;
    protected $_isRefunded = true;
    protected $_isCaptured = true;
    protected $_refundAmount;

    protected $_card;
    protected $_customerId;

    protected $_fees = [];
    protected $_invoiceId;
    protected $_dispute;

    public function __construct(IMediator $mediator, core\collection\ITree $data) {
        $this->_mediator = $mediator;

        $this->_id = $data['id'];
        $this->_amount = mint\Currency::fromIntegerAmount($data['amount'], $data['currency']);
        $this->_creationDate = new core\time\Date($data['created']);
        $this->_description = $data['description'];

        $this->_isLive = (bool)$data['livemode'];
        $this->_isPaid = (bool)$data['paid'];

        if($data->has('failure_message')) {
            $this->_failureException = core\Error::{'EApi,ECard'}([
                'message' => $data['failure_message'],
                'code' => $data['failure_code']
            ]);
        }

        $this->_isRefunded = (bool)$data['refunded'];
        $this->_isCaptured = (bool)$data['captured'];

        if($data['amount_refunded'] > 0) {
            $this->_refundAmount = mint\Currency::fromIntegerAmount($data['amount_refunded'], $this->_amount->getCode());
        }

        $this->_card = new CreditCard($this->_mediator, $data->card);
        $this->_customerId = $data['customer'];

        foreach($data->fee_details as $feeData) {
            $this->_fees[] = new Fee($feeData);
        }

        $this->_invoiceId = $data['invoice'];

        if($data->dispute->count()) {
            $this->_dispute = new Dispute($mediator, $data->dispute);
        }
    }

// Basic details
    public function getId() {
        return $this->_id;
    }

    public function getAmount() {
        return $this->_amount;
    }

    public function getCreationDate() {
        return $this->_creationDate;
    }

    public function getDescription() {
        return $this->_description;
    }


// Success
    public function isLive() {
        return $this->_isLive;
    }

    public function isPaid() {
        return $this->_isPaid;
    }

    public function getFailureException() {
        return $this->_failureException;
    }


// Refund
    public function isRefunded() {
        return $this->_isRefunded;
    }

    public function getRefundAmount() {
        return $this->_refundAmount;
    }

    public function canRefund($amount=null) {
        if(!$this->_isPaid || $this->_isRefunded) {
            return false;
        }

        if($amount !== null) {
            $amount = mint\Currency::factory($amount, $this->_mediator->getDefaultCurrency());
            return $amount->getAmount() <= $this->getRemainingRefundAmount();
        }

        return true;
    }

    public function getRemainingRefundAmount() {
        if(!$this->_isPaid || $this->_isRefunded) {
            return 0;
        }

        $total = $this->_amount->getAmount();
        $refunded = $this->_refundAmount ? $this->_refundAmount->getAmount() : 0;

        return $total - $refunded;
    }

    public function refund($amount=null, $refundApplicationFee=null) {
        $data = $this->_mediator->refundCharge($this->_id, $amount, $refundApplicationFee, true);
        $this->__construct($this->_mediator, $data);

        return $this;
    }


// Capture
    public function isCaptured() {
        return $this->_isCaptured;
    }

    public function canCapture() {
        return !$this->_isCaptured && !$this->_isRefunded;
    }

    public function capture($amount=null, $applicationFee=null) {
        $data = $this->_mediator->captureCharge($this->_id, $amount, $applicationFee, true);
        $this->__construct($this->_mediator, $data);

        return $this;
    }


// Card
    public function getCard() {
        return $this->_card;
    }


// Customer
    public function hasCustomer() {
        return $this->_customerId !== null;
    }

    public function getCustomerId() {
        return $this->_customerId;
    }

    public function fetchCustomer() {
        if($this->_customerId) {
            return $this->_mediator->fetchCustomer($this->_customerId);
        }
    }


// Fees
    public function getFees() {
        return $this->_fees;
    }

    public function getTotalFeeAmount() {
        $output = 0;
        $code = null;

        foreach($this->_fees as $fee) {
            $output += $fee->getAmount()->getAmount();
            $code = $fee->getAmount()->getCode();
        }

        if(!$code) {
            $code = $this->_amount->getCode();
        }

        return new mint\Currency($amount, $code);
    }


// Invoice
    public function hasInvoice() {
        return $this->_invoiceId !== null;
    }

    public function getInvoiceId() {
        return $this->_invoiceId;
    }

    public function fetchInvoice() {
        core\stub($this->_invoiceId);
    }


// Dispute
    public function hasDispute() {
        return $this->_dispute !== null;
    }

    public function getDispute() {
        return $this->_dispute;
    }
}
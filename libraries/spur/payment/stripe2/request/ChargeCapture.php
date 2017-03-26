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

class ChargeCapture implements spur\payment\stripe2\IChargeCaptureRequest {

    use TRequest_ChargeId;
    use TRequest_ApplicationFee;
    use TRequest_Email;
    use TRequest_StatementDescriptor;

/*
    charge
    ?amount
    ?application_fee
    ?receipt_email
    ?statement_descriptor
*/

    protected $_amount;

    public function __construct(string $chargeId, mint\ICurrency $amount=null) {
        $this->setChargeId($chargeId);
    }

    public function setAmount(?mint\ICurrency $amount) {
        $this->_amount = $amount;
        return $this;
    }

    public function getAmount(): ?mint\ICurrency {
        return $this->_amount;
    }


    public function toArray(): array {
        $output = [];

        if($this->_amount !== null) {
            $output['amount'] = $this->_amount->getIntegerAmount();
        }

        //$this->_applyChargeId($output);
        $this->_applyApplicationFee($output);
        $this->_applyEmail($output, 'receipt_email');
        $this->_applyStatementDescriptor($output);

        return $output;
    }
}
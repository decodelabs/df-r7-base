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


// Create
class Charge_Create implements spur\payment\stripe2\IChargeCreateRequest {

    use TRequest_ApplicationFee;
    use TRequest_Description;
    use TRequest_TransferGroup;
    use TRequest_Metadata;
    use TRequest_ReceiptEmail;
    use TRequest_Shipping;
    use TRequest_StatementDescriptor;

/*
    amount
    currency
    ?application_fee
    ?capture
    ?description
    ?destination
    ?transfer_group
    ?on_behalf_of
    ?metadata
    ?receipt_email
    ?shipping
    ?customer
    ?source
    ?statement_descriptor
*/

    protected $_amount;
    protected $_capture = true;
    protected $_destinationAccountId;
    protected $_destinationAmount;
    protected $_onBehalfOf;
    protected $_customerId;
    protected $_source;

    public function __construct(mint\ICurrency $amount, string $description=null) {
        $this->setAmount($amount);
        $this->setDescription($description);
    }

    public function setAmount(mint\ICurrency $amount) {
        $this->_amount = $amount;
        return $this;
    }

    public function getAmount(): mint\ICurrency {
        return $this->_amount;
    }

    public function shouldCapture(bool $flag=null) {
        if($flag !== null) {
            $this->_capture = $flag;
            return $this;
        }

        return $this->_capture;
    }


    public function setDestination(/*?string*/ $accountId, mint\ICurrency $amount=null) {
        if($amount && $amount->getAmount() > $this->_amount->getAmount()) {
            throw core\Error::{'EBounds,EArgument'}([
                'message' => 'Destination amount cannot be more than total amount',
                'data' => [
                    'chargeAmount' => $this->_amount,
                    'destinationAmount' => $amount
                ]
            ]);
        }

        $this->_destinationAccountId = $accountId;
        $this->_destinationAmount = $amount;
        return $this;
    }

    public function getDestinationAccountId()/*?string*/ {
        return $this->_destinationAccountId;
    }

    public function getDestinationAmount()/*?mint\ICurrency*/ {
        return $this->_destinationAmount;
    }

    public function setOnBehalfOfAccountId(/*?string*/ $accountId) {
        $this->_onBehalfOf = $accountId;
        return $this;
    }

    public function getOnBehalfOfAccountId()/*: ?string*/ {
        return $this->_onBehalfOf;
    }

    public function setCustomerId(/*?string*/ $customerId) {
        $this->_customerId = $customerId;
        return $this;
    }

    public function getCustomerId()/*: ?string*/ {
        return $this->_customerId;
    }

    public function setCard(/*?mint\ICreditCard*/ $card) {
        $this->_source = $card;
        return $this;
    }

    public function setSourceId(/*?string*/ $source) {
        $this->_source = $source;
        return $this;
    }

    public function getSource() {
        return $this->_source;
    }



    public function toArray(): array {
        $output = [
            'amount' => $this->_amount->getAmount(),
            'currency' => $this->_amount->getCurrency(),
            'capture' => $this->_capture
        ];

        if($this->_destinationAccountId) {
            $output['destination'] = [
                'account' => $this->_destinationAccountId
            ];

            if($this->_destinationAmount) {
                $output['destination']['amount'] = $this->_destinationAmount->getAmount();
            }
        }

        if($this->_onBehalfOf !== null) {
            $output['on_behalf_of'] = $this->_onBehalfOf;
        }

        if($this->_customerId !== null) {
            $output['customer'] = $this->_customerId;
        }

        if($this->_source !== null) {
            if($this->_source instanceof mint\ICreditCard) {
                $output['source'] = spur\payment\stripe2\Mediator::cardToArray($this->_source);
            } else {
                $output['source'] = $this->_source;
            }
        }

        $this->_applyApplicationFee($output);
        $this->_applyDescription($output);
        $this->_applyTransferGroup($output);
        $this->_applyMetadata($output);
        $this->_applyReceiptEmail($output);
        $this->_applyShipping($output);
        $this->_applyStatementDescriptor($output);

        return $output;
    }
}



// Update
class Charge_Update implements spur\payment\stripe2\IChargeUpdateRequest {

    use TRequest_ChargeId;
    use TRequest_Description;
    use TRequest_Metadata;
    use TRequest_ReceiptEmail;
    use TRequest_Shipping;
    use TRequest_TransferGroup;

/*
    ?description
    ?fraud_details
    ?metadata
    ?receipt_email
    ?shipping
    ?transfer_group
*/

    protected $_fraudDetails;

    public function __construct(string $id) {
        $this->setChargeId($id);
    }

    public function setFraudDetails(/*?array*/ $details) {
        $this->_fraudDetails = $details;
        return $this;
    }

    public function getFraudDetails()/*: ?array*/ {
        return $this->_fraudDetails;
    }


    public function toArray(): array {
        $output = [];

        if($this->_fraudDetails !== null) {
            $output['fraud_details'] = $this->_fraudDetails;
        }

        $this->_applyDescription($output);
        $this->_applyMetadata($output);
        $this->_applyReceiptEmail($output);
        $this->_applyShipping($output);
        $this->_applyTransferGroup($output);

        return $output;
    }
}



// Capture
class Charge_Capture implements spur\payment\stripe2\IChargeCaptureRequest {

    use TRequest_ChargeId;
    use TRequest_ApplicationFee;
    use TRequest_ReceiptEmail;
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

    public function setAmount(/*?mint\ICurrency*/ $amount) {
        $this->_amount = $amount;
        return $this;
    }

    public function getAmount()/*: ?mint\ICurrency*/ {
        return $this->_amount;
    }


    public function toArray(): array {
        $output = [];

        if($this->_amount !== null) {
            $output['amount'] = $this->_amount->getAmount();
        }

        $this->_applyChargeId($output);
        $this->_applyApplicationFee($output);
        $this->_applyReceiptEmail($output);
        $this->_applyStatementDescriptor($output);

        return $output;
    }
}
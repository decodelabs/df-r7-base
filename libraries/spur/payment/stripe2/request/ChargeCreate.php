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

class ChargeCreate implements spur\payment\stripe2\IChargeCreateRequest {

    use TRequest_ApplicationFee;
    use TRequest_Description;
    use TRequest_TransferGroup;
    use TRequest_Metadata;
    use TRequest_Email;
    use TRequest_Shipped;
    use TRequest_Source;
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



    public function toArray(): array {
        $output = [
            'amount' => $this->_amount->getIntegerAmount(),
            'currency' => $this->_amount->getCode(),
            'capture' => $this->_capture ? 'true' : 'false'
        ];

        if($this->_destinationAccountId) {
            $output['destination'] = [
                'account' => $this->_destinationAccountId
            ];

            if($this->_destinationAmount) {
                $output['destination']['amount'] = $this->_destinationAmount->getIntegerAmount();
            }
        }

        if($this->_onBehalfOf !== null) {
            $output['on_behalf_of'] = $this->_onBehalfOf;
        }

        if($this->_customerId !== null) {
            $output['customer'] = $this->_customerId;
        }



        $this->_applyApplicationFee($output);
        $this->_applyDescription($output);
        $this->_applyTransferGroup($output);
        $this->_applyMetadata($output);
        $this->_applyEmail($output, 'receipt_email');
        $this->_applyShipped($output);
        $this->_applySource($output);
        $this->_applyStatementDescriptor($output);

        return $output;
    }
}
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
use df\user;


// Application fee
trait TRequest_ApplicationFee {

    protected $_applicationFee;

    public function setApplicationFee(/*?mint\ICurrency*/ $fee) {
        $this->_applicationFee = $fee;
        return $this;
    }

    public function getApplicationFee()/*: ?mint\ICurrency*/ {
        return $this->_applicationFee;
    }

    protected function _applyApplicationFee(array &$output) {
        if($this->_applicationFee !== null) {
            $output['application_fee'] = $this->_applicationFee->getAmount();
        }
    }
}


// Charge id
trait TRequest_ChargeId {

    protected $_chargeId;

    public function setChargeId(string $id) {
        $this->_chargeId = $id;
        return $this;
    }

    public function getChargeId(): string {
        return $this->_chargeId;
    }

    protected function _applyChargeId(array &$output) {
        if($this->_chargeId !== null) {
            $output['charge'] = $this->_chargeId;
        }
    }
}


// Description
trait TRequest_Description {

    protected $_description;

    public function setDescription(/*?string */ $description) {
        $this->_description = $description;
        return $this;
    }

    public function getDescription()/*: ?string*/ {
        return $this->_description;
    }

    protected function _applyDescription(array &$output) {
        if($this->_description !== null) {
            $output['description'] = $this->_description;
        }
    }
}



// Metadata
trait TRequest_Metadata {

    protected $_metadata;

    public function setMetadata(/*?array */ $metadata) {
        $this->_metadata = $metadata;
        return $this;
    }

    public function getMetadata()/*: ?array*/ {
        return $this->_metadata;
    }

    protected function _applyMetadata(array &$output) {
        if($this->_metadata !== null) {
            $output['metadata'] = $this->_metadata;
        }
    }
}



// Receipt email
trait TRequest_ReceiptEmail {

    protected $_receiptEmail;

    public function setReceiptEmail(/*?string*/ $email) {
        $this->_receiptEmail = $email;
        return $this;
    }

    public function getReceiptEmail()/*: ?string*/ {
        return $this->_receiptEmail;
    }

    protected function _applyReceiptEmail(array &$output) {
        if($this->_receiptEmail !== null) {
            $output['receipt_email'] = $this->_receiptEmail;
        }
    }
}



// Shipping
trait TRequest_Shipping {

    protected $_shippingAddress;
    protected $_carrier;
    protected $_recipientName;
    protected $_recipientPhone;
    protected $_trackingNumber;

    public function setShippingAddress(/*?user\IPostalAddress*/ $address) {
        $this->_shippingAddress = $address;
        return $this;
    }

    public function getShippingAddress()/*: ?user\IPostralAddress*/ {
        return $this->_shippingAddress;
    }


    public function setCarrier(/*?string*/ $carrier) {
        $this->_carrier = $carrier;
        return $this;
    }

    public function getCarrier()/*: ?string*/ {
        return $this->_carrier;
    }


    public function setRecipientName(/*?string*/ $name) {
        $this->_recipientName = $name;
        return $this;
    }

    public function getRecipientName()/*: ?string*/ {
        return $this->_recipientName;
    }


    public function setRecipientPhone(/*?string*/ $phone) {
        $this->_recipientPhone = $phone;
        return $this;
    }

    public function getRecipientPhone()/*: ?string*/ {
        return $this->_recipientPhone;
    }


    public function setTrackingNumber(/*?string*/ $number) {
        $this->_trackingNumber = $number;
        return $this;
    }

    public function getTrackingNumber()/*: ?string*/ {
        return $this->_trackingNumber;
    }

    protected function _applyShipping(array &$output) {
        $shipping = [];

        if($this->_shippingAddress !== null) {
            $shipping['address'] = spur\payment\stripe2\Mediator::addressToArray($this->_shippingAddress);
        }

        if($this->_carrier !== null) {
            $shipping['carrier'] = $this->_carrier;
        }

        if($this->_recipientName !== null) {
            $shipping['name'] = $this->_recipientName;
        }

        if($this->_recipientPhone !== null) {
            $shipping['phone'] = $this->_recipientPhone;
        }

        if($this->_trackingNumber !== null) {
            $shipping['tracking_number'] = $this->_trackingNumber;
        }


        if(!empty($shipping)) {
            $output['shipping'] = $shipping;
        }
    }
}


// Statement descriptor
trait TRequest_StatementDescriptor {

    protected $_statementDescriptor;

    public function setStatementDescriptor(/*?string*/ $descriptor) {
        $this->_statementDescriptor = $descriptor;
        return $this;
    }

    public function getStatementDescriptor()/*: ?string*/ {
        return $this->_statementDescriptor;
    }

    protected function _applyStatementDescriptor(array &$output) {
        if($this->_statementDescriptor !== null) {
            $output['statement_descriptor'] = $this->_statementDescriptor;
        }
    }
}


// Transfer group
trait TRequest_TransferGroup {

    protected $_transferGroup;

    public function setTransferGroup(/*?string*/ $group) {
        $this->_transferGroup = $group;
        return $this;
    }

    public function getTransferGroup()/*: ?string*/ {
        return $this->_transferGroup;
    }

    protected function _applyTransferGroup(array &$output) {
        if($this->_transferGroup !== null) {
            $output['transfer_group'] = $this->_transferGroup;
        }
    }
}
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
            $output['application_fee'] = $this->_applicationFee->getIntegerAmount();
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



// Email
trait TRequest_Email {

    protected $_email;

    public function setEmailAddress(/*?string*/ $email) {
        $this->_email = $email;
        return $this;
    }

    public function getEmailAddress()/*: ?string*/ {
        return $this->_email;
    }

    protected function _applyEmail(array &$output, $key='email') {
        if($this->_email !== null) {
            $output[$key] = $this->_email;
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




// Shipping
trait TRequest_Shipping {

    protected $_shippingAddress;
    protected $_recipientName;
    protected $_recipientPhone;

    public function setShippingAddress(/*?user\IPostalAddress*/ $address) {
        $this->_shippingAddress = $address;
        return $this;
    }

    public function getShippingAddress()/*: ?user\IPostralAddress*/ {
        return $this->_shippingAddress;
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

    protected function _applyShipping(array &$output) {
        $shipping = [];

        if($this->_shippingAddress !== null) {
            $shipping['address'] = spur\payment\stripe2\Mediator::addressToArray($this->_shippingAddress);
        }

        if($this->_recipientName !== null) {
            $shipping['name'] = $this->_recipientName;
        }

        if($this->_recipientPhone !== null) {
            $shipping['phone'] = $this->_recipientPhone;
        }

        if(!empty($shipping)) {
            $output['shipping'] = $shipping;
        }
    }
}

trait TRequest_Shipped {

    use TRequest_Shipping;

    protected $_carrier;
    protected $_trackingNumber;

    public function setCarrier(/*?string*/ $carrier) {
        $this->_carrier = $carrier;
        return $this;
    }

    public function getCarrier()/*: ?string*/ {
        return $this->_carrier;
    }

    public function setTrackingNumber(/*?string*/ $number) {
        $this->_trackingNumber = $number;
        return $this;
    }

    public function getTrackingNumber()/*: ?string*/ {
        return $this->_trackingNumber;
    }

    protected function _applyShipped(array &$output) {
        $this->_applyShipping($output);

        if($this->_carrier !== null) {
            $output['shipping']['carrier'] = $this->_carrier;
        }

        if($this->_trackingNumber !== null) {
            $output['shipping']['tracking_number'] = $this->_trackingNumber;
        }
    }
}




// Source
trait TRequest_Source {

    protected $_source;

    public function setCard(/*?mint\ICreditCard*/ $card) {
        $this->_source = $card;
        return $this;
    }

    public function setSourceId(/*?string*/ $source) {
        $this->_source = $source;
        return $this;
    }

    public function setSource($source) {
        if($source instanceof mint\ICreditCard) {
            $this->setCard($source);
        } else if(is_string($source)) {
            $this->setSourceId($source);
        } else if($source === null) {
            $this->_source = null;
        } else {
            throw core\Error::EArgument([
                'message' => 'Invalid source',
                'data' => $source
            ]);
        }

        return $this;
    }

    public function getSource() {
        return $this->_source;
    }

    protected function _applySource(array &$output) {
        if($this->_source !== null) {
            if($this->_source instanceof mint\ICreditCard) {
                $output['source'] = spur\payment\stripe2\Mediator::cardToArray($this->_source);
                $output['source']['object'] = 'card';
            } else {
                $output['source'] = $this->_source;
            }
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
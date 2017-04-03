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

    public function setApplicationFee(?mint\ICurrency $fee) {
        $this->_applicationFee = $fee;
        return $this;
    }

    public function getApplicationFee(): ?mint\ICurrency {
        return $this->_applicationFee;
    }

    protected function _applyApplicationFee(array &$output) {
        if($this->_applicationFee !== null) {
            $output['application_fee'] = $this->_applicationFee->getIntegerAmount();
        }
    }
}



// Application fee percent
trait TRequest_ApplicationFeePercent {

    protected $_applicationFeePercent;

    public function setApplicationFeePercent(?float $percent) {
        $this->_applicationFeePercent = $percent;
        return $this;
    }

    public function getApplicationFeePercent(): ?float {
        return $this->_applicationFeePercent;
    }

    protected function _applyApplicationFeePercent(array &$output) {
        if($this->_applicationFeePercent !== null) {
            $output['application_fee_percent'] = $this->_applicationFeePercent;
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



// Coupon
trait TRequest_Coupon {

    protected $_coupon;

    public function setCouponId(?string $id) {
        $this->_coupon = $id;
        return $this;
    }

    public function getCouponId(): ?string {
        return $this->_coupon;
    }

    protected function _applyCoupon(array &$output) {
        if($this->_coupon !== null) {
            $output['coupon'] = $this->_coupon;
        }
    }
}


// Description
trait TRequest_Description {

    protected $_description;

    public function setDescription(?string $description) {
        $this->_description = $description;
        return $this;
    }

    public function getDescription(): ?string {
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

    public function setEmailAddress(?string $email) {
        $this->_email = $email;
        return $this;
    }

    public function getEmailAddress(): ?string {
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

    public function setMetadata(?array $metadata) {
        $this->_metadata = $metadata;
        return $this;
    }

    public function getMetadata(): ?array {
        return $this->_metadata;
    }

    protected function _applyMetadata(array &$output) {
        if($this->_metadata !== null) {
            $output['metadata'] = $this->_metadata;
        }
    }
}




// Plan
trait TRequest_Plan {

    protected $_planId;

    public function setPlanId(string $id) {
        $this->_planId = $id;
        return $this;
    }

    public function getPlanId(): string {
        return $this->_planId;
    }

    protected function _applyPlan(array &$output, string $key='plan') {
        if($this->_planId !== null) {
            $output[$key] = $this->_planId;
        }
    }
}




// Prorate
trait TRequest_Prorate {

    protected $_prorate;

    public function setProrate(?bool $prorate) {
        $this->_prorate = $prorate;
        return $this;
    }

    public function getProrate(): ?bool {
        return $this->_prorate;
    }

    protected function _applyProrate(array &$output) {
        if($this->_prorate !== null) {
            $output['prorate'] = $this->_prorate ? 'true' : 'false';
        }
    }
}





// Shipping
trait TRequest_Shipping {

    protected $_shippingAddress;
    protected $_recipientName;
    protected $_recipientPhone;

    public function setShippingAddress(?user\IPostalAddress $address) {
        $this->_shippingAddress = $address;
        return $this;
    }

    public function getShippingAddress(): ?user\IPostralAddress {
        return $this->_shippingAddress;
    }


    public function setRecipientName(?string $name) {
        $this->_recipientName = $name;
        return $this;
    }

    public function getRecipientName(): ?string {
        return $this->_recipientName;
    }


    public function setRecipientPhone(?string $phone) {
        $this->_recipientPhone = $phone;
        return $this;
    }

    public function getRecipientPhone(): ?string {
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

    public function setCarrier(?string $carrier) {
        $this->_carrier = $carrier;
        return $this;
    }

    public function getCarrier(): ?string {
        return $this->_carrier;
    }

    public function setTrackingNumber(?string $number) {
        $this->_trackingNumber = $number;
        return $this;
    }

    public function getTrackingNumber(): ?string {
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

    public function setCard(?mint\ICreditCard $card) {
        $this->_source = $card;
        return $this;
    }

    public function setSourceId(?string $source) {
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

    public function setStatementDescriptor(?string $descriptor) {
        $this->_statementDescriptor = $descriptor;
        return $this;
    }

    public function getStatementDescriptor(): ?string {
        return $this->_statementDescriptor;
    }

    protected function _applyStatementDescriptor(array &$output) {
        if($this->_statementDescriptor !== null) {
            $output['statement_descriptor'] = $this->_statementDescriptor;
        }
    }
}




// Subscription items
trait TRequest_SubscriptionItems {

    protected $_singleItem;
    protected $_items = [];

    public function setPlan(string $planId, int $quantity=1) {
        $this->_singleItem = $this->newItem($planId, $quantity);
        $this->_items = [];
        return $this;
    }

    public function getPlan(): ?spur\payment\stripe2\ISubscriptionItem {
        return $this->_singleItem;
    }

    public function clearPlan() {
        $this->_singleItem = null;
        return $this;
    }



    public function addPlan(string $planId, int $quantity=1) {
        return $this->addItem(
            $this->newItem($planId, $quantity)
        );
    }

    public function newItem(string $planId, int $quantity=1): spur\payment\stripe2\ISubscriptionItem {
        return new spur\payment\stripe2\SubscriptionItem(null, $planId, $quantity);
    }

    public function setItems(array $items) {
        return $this->clearItems()->addItems($items);
    }

    public function addItems(array $items) {
        foreach($items as $item) {
            $this->addItem($item);
        }

        return $this;
    }

    public function addItem(spur\payment\stripe2\ISubscriptionItem $item) {
        $this->_items[$item->getKey()] = $item;
        $this->_singleItem = null;
        return $this;
    }

    public function deleteItem(string $itemId) {
        $item = new spur\payment\stripe2\SubscriptionItem($itemId);
        $item->shouldDelete(true);
        return $this->addItem($item);
    }

    public function clearItems() {
        $this->_items = [];
        return $this;
    }

    public function getItems(): array {
        return $this->_items;
    }


    protected function _applyItems(array &$output, bool $forUpdate=false) {
        if(empty($this->_items) && !$this->_singleItem && !$forUpdate) {
            throw core\Error::ELogic('No plans have been set');
        }

        if($this->_singleItem !== null) {
            $output['plan'] = $this->_singleItem->getPlanId();
            $output['quantity'] = $this->_singleItem->getQuantity();
        } else {
            $output['items'] = [];

            foreach($this->_items as $item) {
                if($item->shouldDelete()) {
                    if($forUpdate) {
                        $output['items'][] = [
                            'id' => $item->getItemId(),
                            'deleted' => 'true'
                        ];
                    }

                    continue;
                }

                $arr = [
                    'plan' => $item->getPlanId(),
                    'quantity' => $item->getQuantity()
                ];

                if($forUpdate && (null !== ($itemId = $item->getItemId()))) {
                    $arr['id'] = $itemId;
                }

                $output['items'][] = $arr;
            }
        }
    }
}




// Tax Percent
trait TRequest_TaxPercent {

    protected $_taxPercent;

    public function setTaxPercent(?float $percent) {
        if($percent !== null) {
            if($percent < 0) {
                $percent = 0;
            } else if($percent > 100) {
                $percent = 100;
            }

            $percent = round($percent, 4);
        }

        $this->_taxPercent = $percent;
        return $this;
    }

    public function getTaxPercent(): ?float {
        return $this->_taxPercent;
    }

    protected function _applyTaxPercent(array &$output) {
        if($this->_taxPercent !== null) {
            $output['tax_percent'] = $this->_taxPercent;
        }
    }
}



// Transfer group
trait TRequest_TransferGroup {

    protected $_transferGroup;

    public function setTransferGroup(?string $group) {
        $this->_transferGroup = $group;
        return $this;
    }

    public function getTransferGroup(): ?string {
        return $this->_transferGroup;
    }

    protected function _applyTransferGroup(array &$output) {
        if($this->_transferGroup !== null) {
            $output['transfer_group'] = $this->_transferGroup;
        }
    }
}


// Trial days
trait TRequest_TrialDays {

    protected $_trialDays;

    public function setTrialDays(?int $days) {
        $this->_trialDays = $days;
        return $this;
    }

    public function getTrialDays(): ?int {
        return $this->_trialDays;
    }

    protected function _applyTrialDays(array &$output) {
        if($this->_trialDays !== null) {
            $output['trial_period_days'] = $this->_trialDays;
        }
    }
}


// Trial end
trait TRequest_TrialEnd {

    protected $_trialEnd;

    public function setTrialEnd($date) {
        $this->_trialEnd = core\time\Date::normalize($date);
        return $this;
    }

    public function getTrialEnd(): ?core\time\IDate {
        return $this->_trialEnd;
    }

    protected function _applyTrialEnd(array &$output) {
        if($this->_trialEnd) {
            $output['trial_end'] = $this->_trialEnd->toTimestamp();
        }
    }
}
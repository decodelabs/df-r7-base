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

class SubscriptionCreate implements spur\payment\stripe2\ISubscriptionCreateRequest {

    use TRequest_ApplicationFeePercent;
    use TRequest_Coupon;
    use TRequest_SubscriptionItems;
    use TRequest_Metadata;
    use TRequest_Prorate;
    use TRequest_Source;
    use TRequest_TaxPercent;
    use TRequest_TrialEnd;
    use TRequest_TrialDays;

/*
    customer
    ?application_fee_percent
    ?coupon
    ?items
    ?metadata
    ?plan
    ?prorate
    ?quantity
    ?source
    ?tax_percent
    ?trial_end
    ?trial_period_days
*/

    protected $_customerId;

    public function __construct(string $customerId, string $planId=null) {
        $this->setCustomerId($customerId);

        if($planId !== null) {
            $this->addPlan($planId);
        }
    }

    public function setCustomerId(string $customerId) {
        $this->_customerId = $customerId;
        return $this;
    }

    public function getCustomerId(): string {
        return $this->_customerId;
    }



    public function toArray(): array {
        $output = [
            'customer' => $this->_customerId
        ];

        $this->_applyApplicationFeePercent($output);
        $this->_applyCoupon($output);
        $this->_applyItems($output);
        $this->_applyMetadata($output);
        $this->_applyProrate($output);
        $this->_applySource($output);
        $this->_applyTaxPercent($output);
        $this->_applyTrialEnd($output);
        $this->_applyTrialDays($output);

        return $output;
    }
}
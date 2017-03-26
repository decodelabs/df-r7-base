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

class SubscriptionUpdate implements spur\payment\stripe2\ISubscriptionUpdateRequest {

    use TRequest_ApplicationFeePercent;
    use TRequest_Coupon;
    use TRequest_SubscriptionItems;
    use TRequest_Metadata;
    use TRequest_Prorate;
    use TRequest_Source;
    use TRequest_TaxPercent;
    use TRequest_TrialEnd;

/*
    ?application_fee_percent
    ?coupon
    ?items
    ?metadata
    ?plan
    ?prorate
    ?proration_date
    ?quantity
    ?source
    ?tax_percent
    ?trial_end
*/

    protected $_subscriptionId;
    protected $_prorationDate;


    public function __construct(string $subscriptionId) {
        $this->setSubscriptionId($subscriptionId);
    }

    public function setSubscriptionId(string $id) {
        $this->_subscriptionId = $id;
        return $this;
    }

    public function getSubscriptionId(): string {
        return $this->_subscriptionId;
    }


    public function setProrationDate($date) {
        $this->_prorationDate = core\time\Date::factory($date);
        return $this;
    }

    public function getProrationDate(): ?core\time\IDate {
        return $this->_prorationDate;
    }


    public function toArray(): array {
        $output = [];

        if($this->_prorationDate !== null) {
            $output['proration_date'] = $this->_prorationDate->toTimestamp();
        }

        $this->_applyApplicationFeePercent($output);
        $this->_applyCoupon($output);
        $this->_applyItems($output, true);
        $this->_applyMetadata($output);
        $this->_applyProrate($output);
        $this->_applySource($output);
        $this->_applyTaxPercent($output);
        $this->_applyTrialEnd($output);

        return $output;
    }
}
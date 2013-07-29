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
    
class Coupon implements ICoupon, core\IDumpable {

    use TApiObjectRequest;

    protected $_id;
    protected $_isLive = true;
    protected $_application = 'once';
    protected $_durationMonths = 1;
    protected $_redemptions = 0;
    protected $_maxRedemptions;
    protected $_expiryDate;
    protected $_amount;
    protected $_percent;

    public function __construct(IMediator $mediator, core\collection\ITree $data=null) {
        $this->_mediator = $mediator;

        if($data) {
            $this->_id = $data['id'];
            $this->_isLive = (bool)$data['livemode'];
            $this->_application = $data['duration'];
            $this->_durationMonths = $data['duration_in_months'];
            $this->_redemptions = $data['times_redeemed'];
            $this->_maxRedemptions = $data['max_redemptions'];

            if($data['redeem_by']) {
                $this->_expiryDate = new core\time\Date($data['redeem_by']);
            }

            if($data['amount_off']) {
                $this->_amount = mint\Currency::fromIntegerAmount($data['amount_off'], $data['currency']);
            }

            if($data['percent_off']) {
                $this->_percent = $data['percent_off'];
            }
        }
    }

// Details
    public function setId($id) {
        if(empty($id)) {
            $id = null;
        }

        $this->_id = $id;
        return $this;
    }

    public function getId() {
        return $this->_id;
    }

    public function isLive() {
        return $this->_isLive;
    }


// Duration
    public function setApplication($application) {
        switch($application) {
            case 'once':
            case 'forever':
            case 'repeating':
                $this->_application = $application;
                break;

            default:
                $this->_application = 'once';
                break;
        }

        return $this;
    }

    public function getApplication() {
        return $this->_application;
    }

    public function setDurationMonths($months) {
        $this->_durationMonths = $months ? (int)$months : null;
        return $this;
    }

    public function getDurationMonths() {
        return $this->_durationMonths;
    }


// Redemptions
    public function countRedemptions() {
        return $this->_redemptions;
    }

    public function setMaxRedemptions($max) {
        $this->_maxRedemptions = $max ? (int)$max : null;
        return $this;
    }

    public function getMaxRedemptions() {
        return $this->_maxRedemptions;
    }


// Expiry
    public function setExpiryDate($date) {
        $this->_expiryDate = $date ? core\time\Date::factory($date) : null;
        return $this;
    }

    public function getExpiryDate() {
        return $this->_expiryDate;
    }


// Amount
    public function setAmount($amount) {
        $this->_amount = $amount ? mint\Currency::factory($amount, $this->_mediator->getDefaultCurrency()) : null;
        return $this;
    }

    public function getAmount() {
        return $this->_amount;
    }


// Percent
    public function setPercent($percent) {
        $this->_percent = $percent ? (int)$percent : null;

        if($this->_percent > 100) {
            $this->_percent = 100;
        }

        return $this;
    }

    public function getPercent() {
        return $this->_percent;
    }


// Submit
    public function getSubmitArray() {
        $output = [];

        if($this->_id !== null) {
            $output['id'] = $this->_id;
        }

        $output['duration'] = $this->_application;

        if($this->_application == 'repeating') {
            $output['duration_in_months'] = $this->_durationMonths;
        }

        if($this->_maxRedemptions) {
            $output['max_redemptions'] = $this->_maxRedemptions;
        }

        if($this->_expiryDate) {
            $output['redeem_by'] = $this->_expiryDate->toTimestamp();
        }

        if($this->_amount) {
            $output['amount_off'] = $this->_amount->getIntegerAmount();
            $output['currency'] = $this->_amount->getCode();
        }

        if($this->_percent) {
            $output['percent_off'] = $this->_percent;
        }

        return $output;
    }

    public function submit() {
        switch($this->_submitAction) {
            case 'create':
                $data = $this->_mediator->createCoupon($this, true);
                $this->__construct($this->_mediator, $data);
                break;
        }

        return $this;
    }

    public function delete() {
        return $this->_mediator->deleteCoupon($this);
    }


// Dump
    public function getDumpProperties() {
        return [
            'id' => $this->_id,
            'isLive' => $this->_isLive,
            'application' => $this->_application,
            'duration' => $this->_durationMonths && $this->_application == 'repeating' ? $this->_durationMonths.' months' : null,
            'redemptions' => $this->_redemptions,
            'maxRedemptions' => $this->_maxRedemptions,
            'expiryDate' => $this->_expiryDate,
            'amount' => $this->_amount,
            'percent' => $this->_percent ? $this->_percent.'%' : null
        ];
    }
}
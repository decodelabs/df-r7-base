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

class Subscription implements ISubscription, core\IDumpable {

    use TMediatorProvider;

    protected $_id;
    protected $_customerId;
    protected $_plan;
    protected $_cancelAtPeriodEnd = false;
    protected $_status;
    protected $_quantity;
    protected $_startDate;
    protected $_cancelDate;
    protected $_endDate;
    protected $_periodStartDate;
    protected $_periodEndDate;
    protected $_trialStartDate;
    protected $_trialEndDate;

    public function __construct(IMediator $mediator, core\collection\ITree $data) {
        $this->_mediator = $mediator;

        $this->_id = $data['id'];
        $this->_customerId = $data['customer'];
        $this->_plan = new Plan($mediator, $data->plan);
        $this->_cancelAtPeriodEnd = (bool)$data['cancel_at_period_end'];
        $this->_status = $data['status'];
        $this->_quantity = (int)$data['quantity'];

        $this->_startDate = new core\time\Date($data['start']);

        if($data['canceled_at']) {
            $this->_cancelDate = new core\time\Date($data['canceled_at']);
        }

        if($data['ended_at']) {
            $this->_endDate = new core\time\Date($data['ended_at']);
        }

        $this->_periodStartDate = new core\time\Date($data['current_period_start']);
        $this->_periodEndDate = new core\time\Date($data['current_period_end']);

        if($data['trial_start']) {
            $this->_trialStartDate = new core\time\Date($data['trial_start']);
        }

        if($data['trial_end']) {
            $this->_trialEndDate = new core\time\Date($data['trial_end']);
        }
    }


// Ids
    public function getId() {
        return $this->_id;
    }

    public function getCustomerId() {
        return $this->_customerId;
    }

    public function fetchCustomer() {
        return $this->_mediator->fetchCustomer($this->_customerId);
    }

// Details
    public function getPlan() {
        return $this->_plan;
    }

    public function shouldCancelAtPeriodEnd() {
        return $this->_cancelAtPeriodEnd;
    }

    public function getQuantity() {
        return $this->_quantity;
    }

// Status
    public function getStatus() {
        return $this->_status;
    }

    public function isTrialing() {
        return $this->_status == 'trialing';
    }

    public function isActive() {
        return $this->_status == 'active';
    }

    public function isPastDue() {
        return $this->_status == 'past_due';
    }

    public function isCanceled() {
        return $this->_status == 'canceled';
    }

    public function isUnpaid() {
        return $this->_status == 'unpaid';
    }


// Plan dates
    public function getStartDate() {
        return $this->_startDate;
    }

    public function getCancelDate() {
        return $this->_cancelDate;
    }

    public function hasEnded() {
        return $this->_endDate !== null;
    }

    public function getEndDate() {
        return $this->_endDate;
    }


// Period dates
    public function getPeriodStartDate() {
        return $this->_periodStartDate;
    }

    public function getPeriodEndDate() {
        return $this->_periodEndDate;
    }


// Trial dates
    public function hasTrialPeriod() {
        return $this->_trialStartDate !== null;
    }

    public function getTrialStartDate() {
        return $this->_trialStartDate;
    }

    public function getTrialEndDate() {
        return $this->_trialEndDate;
    }


// Submit
    public function cancel($atPeriodEnd=false) {
        $data = $this->_mediator->cancelSubscription($this->_customerId, $atPeriodEnd, true);
        $this->__construct($this->_mediator, $data);
        return $this;
    }


// Dump
    public function getDumpProperties() {
        $output = [
            'customerId' => $this->_customerId,
            'plan' => $this->_plan,
            'cancelAtPeriodEnd' => $this->_cancelAtPeriodEnd,
            'status' => $this->_status,
            'quantity' => $this->_quantity,
            'startDate' => $this->_startDate,
            'cancelDate' => $this->_cancelDate,
            'endDate' => $this->_endDate,
            'periodStart' => $this->_periodStartDate,
            'periodEnd' => $this->_periodEndDate,
            'trialStart' => $this->_trialStartDate,
            'trialEnd' => $this->_trialEndDate
        ];

        return $output;
    }
}
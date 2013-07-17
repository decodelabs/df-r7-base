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
    
class Plan implements IPlan, core\IDumpable {

    use TApiObjectRequest;

    protected $_id;
    protected $_name;
    protected $_amount;
    protected $_isLive = true;
    protected $_intervalUnit = 'month';
    protected $_intervalQuantity = 1;
    protected $_trialPeriodDays;

    public function __construct(IMediator $mediator, core\collection\ITree $data=null) {
        $this->_mediator = $mediator;

        if($data) {
            $this->_submitAction = 'rename';
            $this->_id = $data['id'];
            $this->_name = $data['name'];
            $this->_isLive = (bool)$data['livemode'];
            $this->setAmount(mint\Currency::fromIntegerAmount($data['amount'], $data['currency']));
            $this->setInterval($data['interval_count'], $data['interval']);
            $this->setTrialPeriodDays($data['trial_period_days']);
        } else {
            $this->_submitAction = 'create';
        }
    }

// Id
    public function setId($id) {
        $this->_id = $id;
        return $this;
    }

    public function getId() {
        return $this->_id;
    }


// Name
    public function setName($name) {
        $this->_name = $name;
        return $this;
    }

    public function getName() {
        return $this->_name;
    }


// Amount
    public function setAmount($amount) {
        $this->_amount = mint\Currency::factory($amount);
        return $this;
    }

    public function getAmount() {
        return $this->_amount;
    }


// Live
    public function isLive() {
        return $this->_isLive;
    }


// Interval
    public function setInterval($quantity, $unit='month') {
        $this->_intervalQuantity = (int)$quantity;

        switch($unit) {
            case 'week':
            case 'month':
            case 'year':
                $this->_intervalUnit = $unit;
                break;

            default:
                $this->_intervalUnit = 'month';
                break;
        }

        return $this;
    }

    public function getInterval() {
        return $this->_intervalQuantity.' '.$this->_intervalUnit;
    }

    public function getIntervalQuantity() {
        return $this->_intervalQuantity;
    }

    public function getIntervalUnit() {
        return $this->_intervalUnit;
    }


// Trial
    public function setTrialPeriodDays($days) {
        if(!($days = (int)$days)) {
            $days = null;
        }

        $this->_trialPeriodDays = $days;
        return $this;
    }

    public function getTrialPeriodDays() {
        return $this->_trialPeriodDays;
    }


// Sumit
    public function getSubmitArray() {
        $output = [
            'id' => $this->_id,
            'name' => $this->_name,
            'amount' => $this->_amount->getIntegerAmount(),
            'currency' => $this->_amount->getCode(),
            'interval' => $this->_intervalUnit,
            'interval_count' => $this->_intervalQuantity
        ];

        if($this->_trialPeriodDays) {
            $output['trial_period_days'] = $this->_trialPeriodDays;
        }

        return $output;
    }

    public function submit() {
        switch($this->_submitAction) {
            case 'create':
                $data = $this->_mediator->createPlan($this, true);
                break;

            case 'rename':
                $data = $this->_mediator->renamePlan($this->_id, $this->_name, true);
                break;
        }

        $this->__construct($this->_mediator, $data);
        return $this;
    }

    public function rename($newName) {
        $data = $this->_mediator->renamePlan($this->_id, $newName, true);
        $this->__construct($this->_mediator, $data);
        return $this;
    }

    public function delete() {
        return $this->_mediator->deletePlan($this);
    }


// Dump
    public function getDumpProperties() {
        return [
            'id' => $this->_id,
            'name' => $this->_name,
            'amount' => $this->_amount,
            'isLive' => $this->_isLive,
            'interval' => $this->getInterval(),
            'trialPeriod' => $this->_trialPeriodDays ? $this->_trialPeriodDays.' days' : null
        ];
    }
}
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
    protected $_trialPeriod;
    protected $_metadata;
    protected $_statementDescriptor;

    public function __construct(IMediator $mediator, core\collection\ITree $data=null) {
        $this->_mediator = $mediator;

        if($data) {
            $this->_submitAction = 'rename';
            $this->_id = $data['id'];
            $this->_name = $data['name'];
            $this->_isLive = (bool)$data['livemode'];
            $this->setAmount(mint\Currency::fromIntegerAmount($data['amount'], $data['currency']));
            $this->setInterval($data['interval_count'], $data['interval']);
            $this->setTrialPeriod($data['trial_period_days']);
            $this->_metadata = $data->metadata->toArray();
            $this->_statementDescriptor = $data['statement_descriptor'];
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
        $this->_amount = mint\Currency::factory($amount, $this->_mediator->getDefaultCurrency());
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
            case 'day':
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
    public function setTrialPeriod($days) {
        if(!($days = (int)$days)) {
            $days = null;
        }

        $this->_trialPeriod = $days;
        return $this;
    }

    public function getTrialPeriod() {
        return $this->_trialPeriod;
    }


// Meta
    public function setMetadata(array $data=null) {
        $this->_metadata = $data;
        return $this;
    }

    public function getMetadata() {
        return $this->_metadata;
    }

// Statement
    public function setStatementDescriptor(string $descriptor=null) {
        $this->_statementDescriptor = $descriptor;
        return $this;
    }

    public function getStatementDescriptor() {
        return $this->_statementDescriptor;
    }


// Submit
    public function getSubmitArray() {
        $output = [
            'id' => $this->_id,
            'name' => $this->_name,
            'amount' => $this->_amount->getIntegerAmount(),
            'currency' => $this->_amount->getCode(),
            'interval' => $this->_intervalUnit,
            'interval_count' => $this->_intervalQuantity,
            'metadata' => $this->_metadata,
            'statement_descriptor' => $this->_statementDescriptor
        ];

        if($this->_trialPeriod) {
            $output['trial_period_days'] = $this->_trialPeriod;
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
            'trialPeriod' => $this->_trialPeriod ? $this->_trialPeriod.' days' : null,
            'statementDescriptor' => $this->_statementDescriptor,
            'metadata' => $this->_metadata
        ];
    }
}
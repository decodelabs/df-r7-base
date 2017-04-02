<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\mint\subscription;

use df;
use df\core;
use df\mint;

class Plan implements mint\IPlan, core\IDumpable {

    protected $_id;
    protected $_name;
    protected $_amount;
    protected $_interval;
    protected $_intervalCount;
    protected $_statementDescriptor;
    protected $_trialDays;

    public function __construct(string $id, string $name, mint\ICurrency $amount, string $interval='month') {
        $this->setId($id);
        $this->setName($name);
        $this->setAmount($amount);
        $this->setInterval($interval);
    }

    public function setId(string $id) {
        $this->_id = $id;
        return $this;
    }

    public function getId(): string {
        return $this->_id;
    }

    public function setAmount(mint\ICurrency $amount) {
        $this->_amount = $amount;
        return $this;
    }

    public function getAmount(): mint\ICurrency {
        return $this->_amount;
    }


    public function setName(string $name) {
        $this->_name = $name;
        return $this;
    }

    public function getName(): string {
        return $this->_name;
    }


    public function setInterval(string $interval, int $count=null) {
        switch($interval) {
            case 'day':
            case 'week':
            case 'month':
            case 'year':
                break;

            default:
                throw core\Error::EArgument([
                    'message' => 'Invalid interval',
                    $interval
                ]);
        }

        $this->_interval = $interval;

        if($count !== null) {
            $this->setIntervalCount($count);
        }

        return $this;
    }

    public function getInterval(): string {
        return $this->_interval;
    }


    public function setIntervalCount(int $count) {
        $this->_intervalCount = max($count, 1);
        return $this;
    }

    public function getIntervalCount(): int {
        return $this->_intervalCount;
    }

    public function setStatementDescriptor(?string $descriptor) {
        $this->_statementDescriptor = $descriptor;
        return $this;
    }

    public function getStatementDescriptor(): ?string {
        return $this->_statementDescriptor;
    }

    public function setTrialDays(?int $days) {
        $this->_trialDays = $days;
        return $this;
    }

    public function getTrialDays(): ?int {
        return $this->_trialDays;
    }


// Updates
    public function shouldUpdate(mint\IPlan $plan): bool {
        if($this->_name !== $plan->getName()) {
            return true;
        }

        if($this->_statementDescriptor !== null
        && $this->_statementDescriptor !== $plan->getStatementDescriptor()) {
            return true;
        }

        if($this->_trialDays !== null
        && $this->_trialDays !== $plan->getTrialDays()) {
            return true;
        }

        return false;
    }


// Dump
    public function getDumpProperties() {
        $output = ($this->_id ?? '*').' : '.$this->_name;

        if($this->_statementDescriptor) {
            $output .= ' ['.$this->_statementDescriptor.']';
        }

        $output .= "\n";
        $output .= $this->_amount.' @ '.$this->_intervalCount.' '.$this->_interval;

        if($this->_trialDays) {
            $output .= ' ('.$this->_trialDays.' day trial)';
        }

        return $output;
    }
}
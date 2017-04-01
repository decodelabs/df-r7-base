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

class PlanCreate implements spur\payment\stripe2\IPlanCreateRequest {

    use TRequest_Metadata;
    use TRequest_Plan;
    use TRequest_StatementDescriptor;
    use TRequest_TrialDays;

/*
    id
    amount
    currency
    interval
    name
    ?interval_count
    ?metadata
    ?statement_descriptor
    ?trial_period_days
*/

    protected $_id;
    protected $_amount;
    protected $_name;
    protected $_interval = 'month';
    protected $_intervalCount = 1;

    public function __construct(string $id, string $name, mint\ICurrency $amount, string $interval='month') {
        $this->setPlanId($id);
        $this->setName($name);
        $this->setAmount($amount);
        $this->setInterval($interval);
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



    public function toArray(): array {
        $output = [
            'amount' => $this->_amount->getIntegerAmount(),
            'currency' => $this->_amount->getCode(),
            'name' => $this->_name,
            'interval' => $this->_interval,
            'interval_count' => $this->_intervalCount
        ];

        $this->_applyPlan($output, 'id');
        $this->_applyMetadata($output);
        $this->_applyStatementDescriptor($output);
        $this->_applyTrialDays($output);

        return $output;
    }
}
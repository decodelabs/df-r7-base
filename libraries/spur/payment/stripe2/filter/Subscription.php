<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\payment\stripe2\filter;

use df;
use df\core;
use df\spur;
use df\mint;

class Subscription extends Base implements spur\payment\stripe2\ISubscriptionFilter {

    use TFilter_Created;
    use TFilter_Customer;

    protected $_plan;
    protected $_status = 'all';

    public function __construct(string $customerId=null) {
        $this->setCustomerId($customerId);
    }

    public function setPlanId(?string $planId) {
        $this->_plan = $planId;
        return $this;
    }

    public function getPlanId(): ?string {
        return $this->_plan;
    }


    public function setStatus(string $status) {
        switch($status) {
            case 'trialing':
            case 'active':
            case 'past_due':
            case 'unpaid':
            case 'canceled':
            case 'all':
                break;

            default:
                throw core\Error::EArgument([
                    'message' => 'Invalid status',
                    'data' => $status
                ]);
        }

        $this->_status = $status;
        return $this;
    }

    public function getStatus(): string {
        return $this->_status;
    }


    public function toArray(): array {
        $output = parent::toArray();

        if($this->_plan !== null) {
            $output['plan'] = $this->_plan;
        }

        if($this->_status !== 'all') {
            $output['status'] = $this->_status;
        }

        $this->_applyCreated($output);
        $this->_applyCustomer($output);

        return $output;
    }
}
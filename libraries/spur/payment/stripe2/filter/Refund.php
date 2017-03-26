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

class Refund extends Base implements spur\payment\stripe2\IRefundFilter {

    protected $_chargeId;

    public function __construct(string $chargeId=null) {
        $this->setChargeId($chargeId);
    }

    public function setChargeId(?string $chargeId) {
        $this->_chargeId = $chargeId;
        return $this;
    }

    public function getChargeId(): ?string {
        return $this->_chargeId;
    }

    public function toArray(): array {
        $output = parent::toArray();

        if($this->_chargeId !== null) {
            $output['charge'] = $this->_chargeId;
        }

        return $output;
    }
}
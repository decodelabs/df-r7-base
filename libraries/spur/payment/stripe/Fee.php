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
    
class Fee implements IFee {

    protected $_amount;
    protected $_type;
    protected $_description;
    protected $_application;
    protected $_refundAmount;

    public function __construct(core\collection\ITree $data) {
        $this->_amount = mint\Currency::fromIntegerAmount($data['amount'], $data['currency']);
        $this->_type = $data['type'];
        $this->_description = $data['description'];
        $this->_application = $data['application'];

        if($data['amount_refunded'] > 0) {
            $this->_refundAmount = mint\Currency::fromIntegerAmount($data['amount_refunded'], $this->_amount->getCode());
        }
    }

    public function getAmount() {
        return $this->_amount;
    }

    public function getType() {
        return $this->_type;
    }

    public function getDescription() {
        return $this->_description;
    }

    public function getApplication() {
        return $this->_application;
    }

    public function getRefundAmount() {
        return $this->_refundAmount;
    }
}
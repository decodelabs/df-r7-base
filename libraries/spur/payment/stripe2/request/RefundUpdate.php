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

class RefundUpdate implements spur\payment\stripe2\IRefundUpdateRequest {

    use TRequest_Metadata;

/*
    ?metadata
*/

    protected $_refundId;

    public function __construct(string $refundId) {
        $this->setRefundId($refundId);
    }

    public function setRefundId(string $id) {
        $this->_refundId = $id;
        return $this;
    }

    public function getRefundId(): string {
        return $this->_refundId;
    }

    public function toArray(): array {
        $output = [];

        $this->_applyMetadata($output);

        return $output;
    }
}
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

class CardCreate implements spur\payment\stripe2\ICardCreateRequest {

    use TRequest_Metadata;
    use TRequest_Source;

/*
    source
    ?metadata
*/

    protected $_customerId;

    public function __construct(string $customerId, $source) {
        $this->setCustomerId($customerId);
        $this->setSource($source);
    }

    public function setCustomerId(string $customerId) {
        $this->_customerId = $customerId;
        return $this;
    }

    public function getCustomerId(): string {
        return $this->_customerId;
    }



    public function toArray(): array {
        $output = [];
        $this->_applySource($output);
        $this->_applyMetadata($output);

        return $output;
    }
}
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

class ChargeUpdate implements spur\payment\stripe2\IChargeUpdateRequest {

    use TRequest_ChargeId;
    use TRequest_Description;
    use TRequest_Metadata;
    use TRequest_Email;
    use TRequest_Shipping;
    use TRequest_TransferGroup;

/*
    ?description
    ?fraud_details
    ?metadata
    ?receipt_email
    ?shipping
    ?transfer_group
*/

    protected $_fraudDetails;

    public function __construct(string $id) {
        $this->setChargeId($id);
    }

    public function setFraudDetails(/*?array*/ $details) {
        $this->_fraudDetails = $details;
        return $this;
    }

    public function getFraudDetails()/*: ?array*/ {
        return $this->_fraudDetails;
    }


    public function toArray(): array {
        $output = [];

        if($this->_fraudDetails !== null) {
            $output['fraud_details'] = $this->_fraudDetails;
        }

        $this->_applyDescription($output);
        $this->_applyMetadata($output);
        $this->_applyEmail($output, 'receipt_email');
        $this->_applyShipped($output);
        $this->_applyTransferGroup($output);

        return $output;
    }
}
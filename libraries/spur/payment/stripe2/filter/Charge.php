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

class Charge extends Base implements spur\payment\stripe2\IChargeFilter {

    use TFilter_Created;
    use TFilter_Customer;
    use TFilter_Source;

    protected $_transferGroup;

    public function __construct(string $customerId=null) {
        $this->setCustomerId($customerId);
    }


    public function setTransferGroup(/*?string*/ $group) {
        $this->_transferGroup = $group;
        return $this;
    }

    public function getTransferGroup()/*: ?string*/ {
        return $this->_transferGroup;
    }

    public function toArray(): array {
        $output = parent::toArray();

        if($this->_transferGroup !== null) {
            $output['transfer_group'] = $this->_transferGroup;
        }

        $this->_applyCreated($output);
        $this->_applyCustomer($output);
        $this->_applySource($output);

        return $output;
    }
}
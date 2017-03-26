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

class BalanceTransaction extends Base implements spur\payment\stripe2\IBalanceTransactionFilter {

    use TFilter_Availability;
    use TFilter_Created;
    use TFilter_Currency;

    protected $_sourceOnly = false;
    protected $_transferId;
    protected $_type;

    public function __construct(string $type=null) {
        $this->setType($type);
    }

    public function isSourceOnly(bool $flag=null) {
        if($flag !== null) {
            $this->_sourceOnly = $flag;
            return $this;
        }

        return $this->_sourceOnly;
    }


    public function setTransferId(?string $transferId) {
        $this->_transferId = $transferId;
        return $this;
    }

    public function getTransferId(): ?string {
        return $this->_transferId;
    }


    public function setType(?string $type) {
        switch($type) {
            case null:
            case 'charge':
            case 'refund':
            case 'adjustment':
            case 'application_fee':
            case 'application_fee_refund':
            case 'payment':
            case 'transfer':
            case 'transfer_failure':
                break;

            default:
                throw core\Error::EArgument([
                    'message' => 'Invalid type',
                    'data' => $type
                ]);
        }

        $this->_type = $type;
        return $this;
    }

    public function getType(): ?string {
        return $this->_type;
    }

    public function toArray(): array {
        $output = parent::toArray();

        if($this->_sourceOnly) {
            $output['source'] = true;
        }

        if($this->_transferId !== null) {
            $output['transfer'] = $this->_transferId;
        }

        if($this->_type !== null) {
            $output['type'] = $this->_type;
        }

        $this->_applyAvailability($output);
        $this->_applyCreated($output);
        $this->_applyCurrency($output);

        return $output;
    }
}
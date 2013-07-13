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
    

// Exceptions
interface IException {}
class BadMethodCallException extends \BadMethodCallException implements IException {}
class RuntimeException extends \RuntimeException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}

class ApiError extends RuntimeException implements core\IDumpable {

    protected $_data;

    public function __construct(array $data) {
        parent::__construct($data['message']);
        $this->_data = $data;
    }

    public function getData() {
        return $this->_data;
    }

    public function getDumpProperties() {
        return $this->_data;
    }

    public function getType() {
        if(isset($this->_data['type'])) {
            return $this->_data['type'];
        }

        return 'card_error';
    }
}

class ApiDataError extends ApiError {}
class ApiImplementationError extends ApiError {}



// Interfaces
interface IMediator {
    public function getHttpClient();

// Api key
    public function setApiKey($key);
    public function getApiKey();


// Charges
    public function newChargeRequest($amount, mint\ICreditCardReference $card, $description=null);
    public function submitCharge(IChargeRequest $request, $returnRaw=false);
    public function fetchCharge($id, $returnRaw=false);
    public function refundCharge($id, $amount=null, $refundApplicationFee=null, $returnRaw=false);
    public function captureCharge($id, $amount=null, $applicationFee=null, $returnRaw=false);
    public function fetchChargeList($limit=10, $offset=0, $filter=null, $customerId=null, $returnRaw=false);

// IO
    public function callServer($method, $path, array $data=array());
}

interface IMediatorProvider {
    public function getMediator();
}


interface IChargeRequest {
    public function setAmount($amount);
    public function getAmount();
    public function setCustomerId($id);
    public function getCustomerId();
    public function setCard(mint\ICreditCardReference $card);
    public function getCard();
    public function setDescription($description);
    public function getDescription();
    public function shouldCapture($flag=null);
    public function setApplicationFee($amount);
    public function getApplicationFee();

    public function getSubmitArray();
}


interface ICharge extends IMediatorProvider {
    public function getId();
    public function getAmount();
    public function getCreationDate();
    public function getDescription();

    public function isLive();
    public function isPaid();
    public function getFailureException();

    public function isRefunded();
    public function getRefundAmount();
    public function canRefund($amount=null);
    public function getRemainingRefundAmount();
    public function refund($amount=null, $refundApplicationFee=null);

    public function isCaptured();
    public function canCapture();
    public function capture($amount=null, $applicationFee=null);

    public function getCard();
    public function getCardFingerprint();
    public function hasPassedCardVerificationCheck();
    public function hasPassedAddressCheck();
    public function hasPassedPostalCodeCheck();

    public function hasCustomer();
    public function getCustomerId();
    public function fetchCustomer();

    public function getFees();
    public function getTotalFeeAmount();

    public function hasInvoice();
    public function getInvoiceId();
    public function fetchInvoice();

    public function hasDispute();
    public function getDispute();
}



interface IFee {
    public function getAmount();
    public function getType();
    public function getDescription();
    public function getApplication();
    public function getRefundAmount();
}


interface IDispute extends IMediatorProvider {
    public function getChargeId();
    public function fetchCharge();
    public function getAmount();
    public function getCreationDate();
    public function isLive();

    public function getStatus();
    public function isWon();
    public function isLost();
    public function requiresResponse();
    public function isUnderReview();

    public function getReason();

    public function getEvidenceDueDate();
    public function hasEvidence();
    public function getEvidence();
}
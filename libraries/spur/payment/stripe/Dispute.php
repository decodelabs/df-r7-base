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
    
class Dispute implements IDispute {

    use TMediatorProvider;

    protected $_chargeId;
    protected $_amount;
    protected $_creationDate;
    protected $_isLive = true;
    protected $_status;
    protected $_reason;
    protected $_evidenceDueDate;
    protected $_evidence;

    public function __construct(IMediator $mediator, core\collection\ITree $data) {
        $this->_mediator = $mediator;
        $this->_chargeId = $data['charge'];
        $this->_amount = mint\Currency::fromIntegerAmount($data['amount'], $data['currency']);
        $this->_creationDate = new core\time\Date($data['created']);
        $this->_isLive = (bool)$data['livemode'];
        $this->_status = $data['status'];
        $this->_reason = $data['reason'];

        if($data['evidence_due_by']) {
            $this->_evidenceDueDate = new core\time\Date($data['evidence_due_by']);
        }

        $this->_evidence = $data['evidence'];
    }

// Basic details
    public function getChargeId() {
        return $this->_chargeId;
    }

    public function fetchCharge() {
        return $this->_mediator->fetchCharge($this->_chargeId);
    }

    public function getAmount() {
        return $this->_amount;
    }

    public function getCreationDate() {
        return $this->_creationDate;
    }

    public function isLive() {
        return $this->_isLive;
    }


// Status
    public function getStatus() {
        return $this->_status;
    }

    public function isWon() {
        return $this->_status == 'won';
    }

    public function isLost() {
        return $this->_status == 'lost';
    }

    public function requiresResponse() {
        return $this->_status == 'needs_response';
    }

    public function isUnderReview() {
        return $this->_status == 'under_review';
    }


// Reason
    public function getReason() {
        return $this->_reason;
    }

// Evidence
    public function getEvidenceDueDate() {
        return $this->_evidenceDueDate;
    }

    public function hasEvidence() {
        return $this->_evidence !== null;
    }

    public function getEvidence() {
        return $this->_evidence;
    }
}
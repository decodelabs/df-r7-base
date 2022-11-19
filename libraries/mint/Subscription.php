<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\mint;

use df\core;

class Subscription implements ISubscription
{
    protected $_id;
    protected $_localId;

    protected $_customerId;
    protected $_planId;

    protected $_trialStart;
    protected $_trialEnd;
    protected $_periodStart;
    protected $_periodEnd;
    protected $_startDate;
    protected $_endDate;
    protected $_cancelDate;
    protected $_atPeriodEnd = false;

    public function __construct(?string $id, string $customerId, string $planId)
    {
        $this->setId($id);
        $this->setCustomerId($customerId);
        $this->setPlanId($planId);
    }

    public function setId(?string $id)
    {
        $this->_id = $id;
        return $this;
    }

    public function getId(): ?string
    {
        return $this->_id;
    }

    public function setLocalId(?string $id)
    {
        $this->_localId = $id;
        return $this;
    }

    public function getLocalId(): ?string
    {
        return $this->_localId;
    }


    public function setCustomerId(string $customerId)
    {
        $this->_customerId = $customerId;
        return $this;
    }

    public function getCustomerId(): string
    {
        return $this->_customerId;
    }


    public function setPlanId(string $planId)
    {
        $this->_planId = $planId;
        return $this;
    }

    public function getPlanId(): string
    {
        return $this->_planId;
    }




    public function setTrialStart($date)
    {
        $this->_trialStart = core\time\Date::normalize($date);
        return $this;
    }

    public function getTrialStart(): ?core\time\IDate
    {
        return $this->_trialStart;
    }

    public function setTrialEnd($date)
    {
        $this->_trialEnd = core\time\Date::normalize($date);
        return $this;
    }

    public function getTrialEnd(): ?core\time\IDate
    {
        return $this->_trialEnd;
    }



    public function setPeriodStart($date)
    {
        $this->_periodStart = core\time\Date::normalize($date);
        return $this;
    }

    public function getPeriodStart(): ?core\time\IDate
    {
        return $this->_periodStart;
    }

    public function setPeriodEnd($date)
    {
        $this->_periodEnd = core\time\Date::normalize($date);
        return $this;
    }

    public function getPeriodEnd(): ?core\time\IDate
    {
        return $this->_periodEnd;
    }



    public function setStartDate($date)
    {
        $this->_startDate = core\time\Date::normalize($date);
        return $this;
    }

    public function getStartDate(): ?core\time\IDate
    {
        return $this->_startDate;
    }

    public function setEndDate($date)
    {
        $this->_endDate = core\time\Date::normalize($date);
        return $this;
    }

    public function getEndDate(): ?core\time\IDate
    {
        return $this->_endDate;
    }

    public function setCancelDate($date, bool $atPeriodEnd = false)
    {
        $this->_cancelDate = core\time\Date::normalize($date);
        $this->_atPeriodEnd = $atPeriodEnd;
        return $this;
    }

    public function getCancelDate(): ?core\time\IDate
    {
        return $this->_cancelDate;
    }

    public function willCancelAtPeriodEnd(): bool
    {
        return $this->_atPeriodEnd;
    }

    // Coupon
}

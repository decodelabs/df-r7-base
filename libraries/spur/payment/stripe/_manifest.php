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

interface IMediator extends spur\IHttpMediator {

// Api key
    public function setApiKey($key);
    public function getApiKey();

// Currency
    public function setDefaultCurrency($code);
    public function getDefaultCurrency();

// Charges
    public function newChargeRequest($amount, mint\ICreditCardReference $card=null, $description=null, $emailAddress=null);
    public function createCharge(IChargeRequest $request, $returnRaw=false);
    public function fetchCharge($id, $returnRaw=false);
    public function refundCharge($id, $amount=null, $refundApplicationFee=null, $returnRaw=false);
    public function captureCharge($id, $amount=null, $applicationFee=null, $returnRaw=false);
    public function fetchChargeList($limit=10, $offset=0, $filter=null, $customerId=null, $returnRaw=false);


// Customers
    public function newCustomerRequest($emailAddress=null, mint\ICreditCardReference $card=null, $description=null, $balance=null);
    public function createCustomer(ICustomerRequest $request, $returnRaw=false);
    public function fetchCustomer($id, $returnRaw=null);
    public function updateCustomer(ICustomerRequest $request, $returnRaw=false);
    public function deleteCustomer($id);
    public function fetchCustomerList($limit=10, $offset=0, $filter=null, $returnRaw=false);
    public function endDiscount($customerId);

// Cards
    public function createCard($customer, mint\ICreditCard $card, $returnRaw=false);
    public function fetchCard($customer, $id, $returnRaw=false);
    public function updateCard($customer, $id, mint\ICreditCard $card, $returnRaw=false);
    public function deleteCard($customer, $id);
    public function fetchCardList($customer, $limit=10, $offset=0, $returnRaw=false);


// Plans
    public function newPlanRequest($id, $name, $amount, $intervalQuantity=1, $intervalUnit='month');
    public function createPlan(IPlan $plan, $returnRaw=false);
    public function fetchPlan($id, $returnRaw=false);
    public function renamePlan($id, $newName, $returnRaw=false);
    public function updatePlan(IPlan $plan, $returnRaw=false);
    public function deletePlan($id);
    public function fetchPlanList($limit=10, $offset=0, $returnRaw=false);


// Subscriptions
    public function newSubscriptionRequest($customerId, $planId, mint\ICreditCardReference $card=null, $quantity=1);
    public function fetchSubscription($id, $returnRaw=false);
    public function updateSubscription(ISubscriptionRequest $request, $returnRaw=false);
    public function cancelSubscription($id, $atPeriodEnd=false, $returnRaw=false);
    public function cancelCustomerSubscription($customerId, $atPeriodEnd=false, $returnRaw=false);


// Coupons
    public function newCouponRequest($id, $application, $amount=null, $percent=null);
    public function createCoupon(ICoupon $coupon, $returnRaw=false);
    public function fetchCoupon($id, $returnRaw=false);
    public function deleteCoupon($id);
    public function fetchCouponList($limit=10, $offset=0, $returnRaw=false);


// Account
    public function getAccountDetails();
}

interface IMediatorProvider {
    public function getMediator();
}

trait TMediatorProvider {

    protected $_mediator;

    public function getMediator() {
        return $this->_mediator;
    }
}

interface IApiObjectRequest extends IMediatorProvider {
    public function setSubmitAction($action);
    public function getSubmitAction();

    public function getSubmitArray();
    public function submit();
}

trait TApiObjectRequest {

    use TMediatorProvider;

    protected $_submitAction = 'create';

    public function setSubmitAction($action) {
        $this->_submitAction = $action;
        return $this;
    }

    public function getSubmitAction() {
        return $this->_submitAction;
    }
}


interface ICreditCard extends mint\ICreditCard, IMediatorProvider {
    public function getId();
    public function getFingerprint();
    public function hasPassedVerificationCheck();
    public function hasPassedAddressCheck();
    public function hasPassedPostalCodeCheck();
}

interface IChargeRequest extends IApiObjectRequest {
    public function setAmount($amount);
    public function getAmount();
    public function setCustomerId($id);
    public function getCustomerId();
    public function setCard(mint\ICreditCardReference $card=null);
    public function getCard();
    public function setDescription($description);
    public function getDescription();
    public function setEmailAddress($email);
    public function getEmailAddress();
    public function shouldCapture(bool $flag=null);
    public function setApplicationFee($amount);
    public function getApplicationFee();
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



interface ICustomerRequest extends IApiObjectRequest {
    public function setId($id);
    public function getId();
    public function setEmailAddress($email);
    public function getEmailAddress();
    public function setDescription($description);
    public function getDescription();
    public function setBalance($amount);
    public function getBalance();
    public function setCard(mint\ICreditCardReference $card=null);
    public function getCard();
    public function setDefaultCardId($card);
    public function getDefaultCardId();
    public function setCouponCode($code);
    public function getCouponCode();
    public function setPlanId($id);
    public function getPlanId();
    public function setQuantity($quantity);
    public function getQuantity();
    public function setTargetCustomer(ICustomer $customer);
    public function getTargetCustomer();
}


interface ICustomer extends IMediatorProvider {
    public function getId();
    public function isLive();
    public function isDelinquent();
    public function getCreationDate();
    public function getEmailAddress();
    public function getDescription();

    public function getBalance();
    public function isInCredit();
    public function owesPayment();

    public function hasDiscount();
    public function getCoupon();
    public function getDiscountStartDate();
    public function getDiscountEndDate();
    public function endDiscount();

    public function hasSubscription();
    public function getSubscription();
    public function newSubscriptionRequest($planId, mint\ICreditCardReference $card=null, $quantity=1);
    public function updateSubscription(ISubscriptionRequest $request);
    public function cancelSubscription($atPeriodEnd=false);

    public function getCards();
    public function getCard($id);
    public function countCards();
    public function getDefaultCard();
    public function createCard(mint\ICreditCard $card);
    public function updateCard($id, mint\ICreditCard $card);
    public function deleteCard($id);

    public function update();
    public function delete();
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



interface IPlan extends IApiObjectRequest {
    public function setId($id);
    public function getId();
    public function setName($name);
    public function getName();
    public function setAmount($amount);
    public function getAmount();
    public function isLive();

    public function setInterval($quantity, $unit='month');
    public function getInterval();
    public function getIntervalQuantity();
    public function getIntervalUnit();
    public function setTrialPeriod($days);
    public function getTrialPeriod();

    public function setMetadata(array $data=null);
    public function getMetadata();
    public function setStatementDescriptor(string $descriptor=null);
    public function getStatementDescriptor();

    public function rename($newName);
    public function update();
    public function delete();
}


interface ISubscriptionRequest extends IApiObjectRequest {
    public function setCustomerId($customer);
    public function getCustomerId();

    public function setPlanId($plan);
    public function getPlanId();

    public function setCouponCode($code);
    public function getCouponCode();

    public function shouldProrate(bool $flag=null);

    public function setTrialEndDate($date);
    public function getTrialEndDate();

    public function setCard(mint\ICreditCardReference $card=null);
    public function getCard();

    public function setQuantity($quantity);
    public function getQuantity();
}


interface ISubscription extends IMediatorProvider {
    public function getId();
    public function getCustomerId();
    public function fetchCustomer();

    public function getPlan();
    public function shouldCancelAtPeriodEnd();
    public function getQuantity();

    public function getStatus();
    public function isTrialing();
    public function isActive();
    public function isPastDue();
    public function isCanceled();
    public function isUnpaid();

    public function getStartDate();
    public function getCancelDate();
    public function hasEnded();
    public function getEndDate();

    public function getPeriodStartDate();
    public function getPeriodEndDate();

    public function hasTrialPeriod();
    public function getTrialStartDate();
    public function getTrialEndDate();

    public function cancel($atPeriodEnd=false);
}



interface ICoupon extends IApiObjectRequest {
    public function setId($id);
    public function getId();
    public function isLive();

    public function setApplication($application);
    public function getApplication();
    public function setDurationMonths($months);
    public function getDurationMonths();

    public function countRedemptions();
    public function setMaxRedemptions($max);
    public function getMaxRedemptions();

    public function setExpiryDate($date);
    public function getExpiryDate();

    public function setAmount($amount);
    public function getAmount();

    public function setPercent($percent);
    public function getPercent();

    public function delete();
}


interface IAccountDetails {
    public function getId();
    public function getEmailAddress();
    public function getSupportedCurrencies();
    public function canCharge();
    public function hasSubmittedDetails();
    public function isTransferEnabled();
    public function getStatementDescriptor();
}

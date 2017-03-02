<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\mint;

use df;
use df\core;
use df\mint;
use df\user;


// Gateway
interface IGateway {
    public function setDefaultCurrency($code);
    public function getDefaultCurrency();

    public function getSupportedCurrencies();
    public function isCurrencySupported($code);

    public function submitCharge(IChargeRequest $charge): mint\IChargeResult;
    public function submitStandaloneCharge(IStandaloneChargeRequest $charge): IChargeResult;
}

interface ICaptureProviderGateway extends IGateway {
    public function authorizeStandaloneCharge(IStandaloneChargeRequest $charge): IChargeResult;
    public function captureCharge($id);
}

interface IRefundProviderGateway extends IGateway {
    public function refund($chargeId, $amount=null);
}

interface ICustomerTrackingGateway extends IGateway {
    public function submitCustomerCharge(ICustomerChargeRequest $charge): IChargeResult;

    public function addCustomer(ICustomer $customer);
    public function updateCustomer(ICustomer $customer);
    public function deleteCustomer($customerId);
}

interface ICustomerTrackingCaptureProviderGateway extends ICaptureProviderGateway, ICustomerTrackingGateway {
    public function authorizeCustomerCharge(ICustomerChargeRequest $charge): IChargeResult;
}

interface ICardStoreGateway extends ICustomerTrackingGateway {
    public function addCard($customerId, ICreditCard $card);
    public function updateCard($customerId, $cardId, ICreditCard $card);
    public function deleteCard($customerId, $cardId, ICreditCard $card);
}

interface ISubscriptionProviderGateway extends ICustomerTrackingGateway {
    public function addPlan();
    public function updatePlan();
    public function deletePlan();

    public function subscribeCustomer($planId, $customerId);
    public function unsubscribeCustomer($planId, $customerId);
}




// Credit card
interface ICreditCardReference {}

interface ICreditCard extends ICreditCardReference, core\IArrayProvider {
    public function setName($name);
    public function getName();

    public static function isValidNumber($number);
    public function setNumber($number);
    public function getNumber();
    public function setLast4Digits($digits);
    public function getLast4Digits();

    public static function getSupportedBrands();
    public function getBrand();

    public function setStartMonth($month);
    public function getStartMonth();
    public function setStartYear($year);
    public function getStartYear();
    public function setStartString($start);
    public function getStartString();
    public function getStartDate();

    public function setExpiryMonth($month);
    public function getExpiryMonth();
    public function setExpiryYear($year);
    public function getExpiryYear();
    public function setExpiryString($expiry);
    public function getExpiryString();
    public function getExpiryDate();

    public function setCvc($cvc);
    public function getCvc();

    public function setIssueNumber($number);
    public function getIssueNumber();

    public function setBillingAddress(user\IPostalAddress $address=null);
    public function getBillingAddress();

    public function isValid();
}

interface ICreditCardToken extends ICreditCardReference {

}



// Charge
interface IChargeRequest {
    public function setAmount(ICurrency $amount);
    public function getAmount(): ICurrency;
    public function setCard(ICreditCardReference $card);
    public function getCard(): ICreditCardReference;
    public function setDescription(/*?string*/ $description);
    public function getDescription(); //: ?string;
}

interface IStandaloneChargeRequest extends IChargeRequest {
    public function setEmailAddress(/*?string*/ $email);
    public function getEmailAddress();//: ?string;
}

interface ICustomerChargeRequest extends IChargeRequest {
    public function setCustomerId(string $id);
    public function getCustomerId(): string;
}


interface IChargeResult {
    public function isSuccessful(bool $flag=null);
    public function isCardAccepted(bool $flag=null);
    public function isCardExpired(bool $flag=null);
    public function isCardUnavailable(bool $flag=null);
    public function isApiFailure(bool $flag=null);

    public function setMessage(/*?string*/ $message);
    public function getMessage();//: ?string;

    public function setInvalidFields(string ...$fields);
    public function addInvalidFields(string ...$fields);
    public function getInvalidFields(): array;
}



// Customer
interface ICustomer {

}



// Currency
interface ICurrency extends core\IStringProvider {
    public function setAmount($amount);
    public function getAmount();
    public function getIntegerAmount();
    public function getFormattedAmount();

    public function setCode($code);
    public function getCode();

    public function convert($code, $origRate, $newRate);
    public function convertNew($code, $origRate, $newRate);
    public function hasRecognizedCode();
    public function getDecimalPlaces();
    public function getDecimalFactor();

    public function add($amount);
    public function addNew($amount);
    public function subtract($amount);
    public function subtractNew($amount);
    public function multiply($factor);
    public function multiplyNew($factor);
    public function divide($factor);
    public function divideNew($factor);
}
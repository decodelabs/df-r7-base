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


// Exceptions
interface IException {}

class RuntimeException extends \RuntimeException implements IException {}
class LogicException extends \LogicException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}



// Interfaces
interface IGateway {
    public function setDefaultCurrency($code);
    public function getDefaultCurrency();

    public function getSupportedCurrencies();
    public function isCurrencySupported($code);

    public function submitCharge(ICharge $charge);
}

interface ICaptureProviderGateway extends IGateway {
    public function authorizeCharge(ICharge $charge);
    public function captureCharge($id);
}

interface IRefundProviderGateway extends IGateway {
    public function refund($chargeId, $amount=null);
}

interface ICustomerTrackingGateway extends IGateway {
    public function addCustomer(ICustomer $customer);
    public function updateCustomer(ICustomer $customer);
    public function deleteCustomer($customerId);
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

    public function setVerificationCode($code);
    public function getVerificationCode();

    public function setIssueNumber($number);
    public function getIssueNumber();

    public function setBillingAddress(user\IPostalAddress $address=null);
    public function getBillingAddress();

    public function isValid();
}

interface ICreditCardToken extends ICreditCardReference {

}




interface ICustomer {

}



interface ICurrency extends core\IStringProvider {
    public function setAmount($amount);
    public function getAmount();
    public function getIntegerAmount();
    public function getFormattedAmount();
    public function setCode($code);
    public function getCode();
    public function convert($code, $origRate, $newRate);
    public function hasRecognizedCode();
    public function getDecimalPlaces();
    public function getDecimalFactor();
}
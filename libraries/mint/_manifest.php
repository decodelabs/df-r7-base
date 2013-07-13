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

}

interface IRefundProviderGateway extends IGateway {
    public function refund();
}

interface ICustomerTrackingGateway extends IGateway {

}

interface ICardStoreGateway extends IGateway {
    public function addCard($customerId, ICreditCard $card);
    public function updateCard($customerId, $token, ICreditCard $card);
    public function deleteCard($customerId, $token, ICreditCard $card);
}



interface ICreditCardReference {}

interface ICreditCard extends ICreditCardReference, core\IArrayProvider {
    public function setName($name);
    public function getName();

    public static function isValidNumber($number);
    public function setNumber($number);
    public function getNumber();

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



interface ICurrency {
    public function setAmount($amount);
    public function getAmount();
    public function getIntegerAmount();
    public function setCode($code);
    public function getCode();
    public function convert($code, $origRate, $newRate);
    public function hasRecognisedCode();
    public function getDecimalPlaces();
    public function getDecimalFactor();
}
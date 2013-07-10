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

interface ICreditCard extends core\IArrayProvider {
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

    public function setCvv($cvv);
    public function getCvv();

    public function setIssueNumber($number);
    public function getIssueNumber();

    public function isValid();
}


interface ICustomer {
    public function setCreditCard(ICreditCard $card=null);
    public function getCreditCard();

    public function setEmail($email);
    public function getEmail();

    public function setBillingAddress(user\IAddress $address=null);
    public function getBillingAddress();

    public function setShippingAddress(user\IAddress $address=null);
    public function getShippingAddress();
}
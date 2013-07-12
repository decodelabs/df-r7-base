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
    
class CreditCard implements ICreditCard, core\IDumpable {

    protected static $_brands = [
        'visa' => '/^4\d{12}(\d{3})?$/',
        'mastercard' => '/^(5[1-5]\d{4}|677189)\d{10}$/',
        'discover' => '/^(6011|65\d{2}|64[4-9]\d)\d{12}|(62\d{14})$/',
        'amex' => '/^3[47]\d{13}$/',
        'diners_club' => '/^3(0[0-5]|[68]\d)\d{11}$/',
        'jcb' => '/^35(28|29|[3-8]\d)\d{12}$/',
        'switch' => '/^6759\d{12}(\d{2,3})?$/',
        'solo' => '/^6767\d{12}(\d{2,3})?$/',
        'dankort' => '/^5019\d{12}$/',
        'maestro' => '/^(5[06-8]|6\d)\d{10,17}$/',
        'forbrugsforeningen' => '/^600722\d{10}$/',
        'laser' => '/^(6304|6706|6709|6771(?!89))\d{8}(\d{4}|\d{6,7})?$/'
    ];

    protected $_name;
    protected $_number;
    protected $_brand;
    protected $_startMonth;
    protected $_startYear;
    protected $_expiryMonth;
    protected $_expiryYear;
    protected $_cvv;
    protected $_issueNumber;
    protected $_billingAddress;

    public static function fromArray(array $data) {
        $output = new self();

        foreach($data as $key => $value) {
            switch($key) {
                case 'name':
                    $output->setName($value);
                    break;

                case 'number':
                    $output->setNumber($value);
                    break;

                case 'start':
                    $output->setStartString($value);
                    break;

                case 'startMonth':
                    $output->setStartMonth($value);
                    break;

                case 'startYear':
                    $output->setStartYear($value);
                    break;

                case 'expiry':
                    $output->setExpiryString($value);
                    break;

                case 'expiryMonth':
                    $output->setExpiryMonth($value);
                    break;

                case 'expiryYear':
                    $output->setExpiryYear($value);
                    break;

                case 'cvv':
                    $output->setCvv($value);
                    break;

                case 'issueNumber':
                    $output->setIssueNumber($value);
                    break;

                case 'billingAddress':
                    if(is_array($value)) {
                        $value = user\PostalAddress::fromArray($value);
                    }

                    if($value instanceof user\IPostalAddress) {
                        $output->setBillingAddress($value);
                    }

                    break;
            }
        }

        return $output;
    }

    protected function __construct() {}

// Name
    public function setName($name) {
        $this->_name = $name;
        return $this;
    }

    public function getName() {
        return $this->_name;
    }

// Number
    public static function isValidNumber($number) {
        $str = '';

        foreach(array_reverse(str_split($number)) as $i => $c) {
            $str .= $i % 2 ? $c * 2 : $c;
        }

        return array_sum(str_split($str)) % 10 === 0;
    }

    public function setNumber($number) {
        $this->_number = $number;
        $this->_brand = null;

        return $this;
    }

    public function getNumber() {
        return $this->_number;
    }


// Brand
    public static function getSupportedBrands() {
        return array_keys(self::$_brands);
    }

    public function getBrand() {
        if($this->_brand === null) {
            foreach(self::$_brands as $brand => $regex) {
                if(preg_match($regex, $this->_number)) {
                    $this->_brand = $brand;
                    break;
                }
            }

            if($this->_brand === null) {
                $this->_brand = false;
            }
        }

        return $this->_brand;
    }


// Start
    public function setStartMonth($month) {
        $this->_startMonth = (int)$month;
        return $this;
    }

    public function getStartMonth() {
        return $this->_startMonth;
    }

    public function setStartYear($year) {
        if(strlen($year) == 2) {
            $year = '20'.$year;
        }

        $this->_startYear = (int)$year;
        return $this;
    }

    public function getStartYear() {
        return $this->_startYear;
    }

    public function setStartString($start) {
        $parts = explode('/', $start);

        return $this
            ->setStartMonth(array_shift($parts))
            ->setStartYear(array_shift($parts));
    }

    public function getStartString() {
        return $this->_startMonth.'/'.$this->_startYear;
    }

    public function getStartDate() {
        return new core\time\Date($this->_startYear.'-'.$this->_startMonth.'-1');
    }


// Expiry
    public function setExpiryMonth($month) {
        $this->_expiryMonth = (int)$month;
        return $this;
    }

    public function getExpiryMonth() {
        return $this->_expiryMonth;
    }

    public function setExpiryYear($year) {
        if(strlen($year) == 2) {
            $year = '20'.$year;
        }

        $this->_expiryYear = (int)$year;
        return $this;
    }

    public function getExpiryYear() {
        return $this->_expiryYear;
    }

    public function setExpiryString($expiry) {
        $parts = explode('/', $expiry);

        return $this
            ->setExpiryMonth(array_shift($parts))
            ->setExpiryYear(array_shift($parts));
    }

    public function getExpiryString() {
        return $this->_expiryMonth.'/'.$this->_expiryYear;
    }

    public function getExpiryDate() {
        return new core\time\Date($this->_expiryYear.'-'.$this->_expiryMonth.'-1');
    }


// CVV
    public function setCvv($cvv) {
        $this->_cvv = $cvv;
        return $this;
    }

    public function getCvv() {
        return $this->_cvv;
    }


// Issue number
    public function setIssueNumber($number) {
        $this->_issueNumber = $number;
        return $this;
    }

    public function getIssueNumber() {
        return $this->_issueNumber;
    }


// Billing address
    public function setBillingAddress(user\IPostalAddress $address=null) {
        $this->_billingAddress = $address;
        return $this;
    }

    public function getBillingAddress() {
        return $this->_billingAddress;
    }


// Valid
    public function isValid() {
        if(!$this->_number || !$this->_expiryMonth || !$this->_expiryYear) {
            return false;
        }

        if($this->getExpiryDate()->isPast()) {
            return false;
        }

        if(!$this->isValidNumber($this->_number)) {
            return false;
        }

        return true;
    }


// Array
    public function toArray() {
        return [
            'name' => $this->_name,
            'number' => $this->_number,
            'startMonth' => $this->_startMonth,
            'startYear' => $this->_startYear,
            'expiryMonth' => $this->_expiryMonth,
            'expiryYear' => $this->_expiryYear,
            'cvv' => $this->_cvv,
            'issueNumber' => $this->_issueNumber,
            'billingAddress' => $this->_billingAddress ? $this->_billingAddress->toArray() : null
        ];
    }



// Dump
    public function getDumpProperties() {
        $output = [
            'name' => $this->_name,
            'number' => $this->_number
        ];

        if($this->_number) {
            $output['brand'] = $this->getBrand();
        }

        if($this->_startMonth && $this->_startYear) {
            $output['start'] = $this->getStartString();
        } else {
            $output['start'] = null;
        }

        if($this->_expiryMonth && $this->_expiryYear) {
            $output['expiry'] = $this->getExpiryString();
        } else {
            $output['expiry'] = null;
        }

        $output['cvv'] = $this->_cvv;

        if($this->_issueNumber) {
            $output['issueNumber'] = $this->_issueNumber;
        }

        if($this->_billingAddress) {
            $output['billingAddress'] = $this->_billingAddress;
        }

        $output['valid'] = $this->isValid();

        return $output;
    }
}
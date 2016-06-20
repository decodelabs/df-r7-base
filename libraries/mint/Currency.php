<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\mint;

use df;
use df\core;
use df\mint;

class Currency implements ICurrency, core\IDumpable {

    use core\TStringProvider;

    const CURRENCIES = [
        '784' => 'AED',
        '971' => 'AFN',
        '008' => 'ALL',
        '051' => 'AMD',
        '532' => 'ANG',
        '973' => 'AOA',
        '032' => 'ARS',
        '036' => 'AUD',
        '533' => 'AWG',
        '031' => 'AZN',
        '977' => 'BAM',
        '052' => 'BBD',
        '050' => 'BDT',
        '975' => 'BGN',
        '060' => 'BMD',
        '096' => 'BND',
        '068' => 'BOB',
        '986' => 'BRL',
        '044' => 'BSD',
        '072' => 'BWP',
        '084' => 'BZD',
        '124' => 'CAD',
        '976' => 'CDF',
        '756' => 'CHF',
        '152' => 'CLP',
        '156' => 'CNY',
        '170' => 'COP',
        '188' => 'CRC',
        '132' => 'CVE',
        '203' => 'CZK',
        '262' => 'DJF',
        '208' => 'DKK',
        '214' => 'DOP',
        '012' => 'DZD',
        '233' => 'EEK',
        '818' => 'EGP',
        '230' => 'ETB',
        '978' => 'EUR',
        '242' => 'FJD',
        '238' => 'FKP',
        '826' => 'GBP',
        '981' => 'GEL',
        '292' => 'GIP',
        '270' => 'GMD',
        '324' => 'GNF',
        '320' => 'GTQ',
        '328' => 'GYD',
        '344' => 'HKD',
        '340' => 'HNL',
        '191' => 'HRK',
        '332' => 'HTG',
        '348' => 'HUF',
        '360' => 'IDR',
        '376' => 'ILS',
        '356' => 'INR',
        '352' => 'ISK',
        '388' => 'JMD',
        '392' => 'JPY',
        '404' => 'KES',
        '417' => 'KGS',
        '116' => 'KHR',
        '174' => 'KMF',
        '410' => 'KRW',
        '136' => 'KYD',
        '398' => 'KZT',
        '418' => 'LAK',
        '422' => 'LBP',
        '144' => 'LKR',
        '430' => 'LRD',
        '426' => 'LSL',
        '440' => 'LTL',
        '428' => 'LVL',
        '504' => 'MAD',
        '498' => 'MDL',
        '969' => 'MGA',
        '807' => 'MKD',
        '496' => 'MNT',
        '446' => 'MOP',
        '478' => 'MRO',
        '480' => 'MUR',
        '462' => 'MVR',
        '454' => 'MWK',
        '484' => 'MXN',
        '458' => 'MYR',
        '943' => 'MZN',
        '516' => 'NAD',
        '566' => 'NGN',
        '558' => 'NIO',
        '578' => 'NOK',
        '524' => 'NPR',
        '554' => 'NZD',
        '590' => 'PAB',
        '604' => 'PEN',
        '598' => 'PGK',
        '608' => 'PHP',
        '586' => 'PKR',
        '985' => 'PLN',
        '600' => 'PYG',
        '634' => 'QAR',
        '946' => 'RON',
        '941' => 'RSD',
        '643' => 'RUB',
        '646' => 'RWF',
        '682' => 'SAR',
        '090' => 'SBD',
        '690' => 'SCR',
        '752' => 'SEK',
        '702' => 'SGD',
        '654' => 'SHP',
        '694' => 'SLL',
        '706' => 'SOS',
        '366' => 'SRD',
        '678' => 'STD',
        '222' => 'SVC',
        '748' => 'SZL',
        '764' => 'THB',
        '972' => 'TJS',
        '776' => 'TOP',
        '949' => 'TRY',
        '780' => 'TTD',
        '901' => 'TWD',
        '834' => 'TZS',
        '980' => 'UAH',
        '800' => 'UGX',
        '840' => 'USD',
        '858' => 'UYU',
        '860' => 'UZS',
        '704' => 'VND',
        '548' => 'VUV',
        '882' => 'WST',
        '950' => 'XAF',
        '951' => 'XCD',
        '952' => 'XOF',
        '953' => 'XPF',
        '886' => 'YER',
        '710' => 'ZAR',
        '967' => 'ZMW'
    ];

    const DECIMALS = [
        'BIF' => 0,
        'CLP' => 0,
        'DJF' => 0,
        'GNF' => 0,
        'JPY' => 0,
        'KMF' => 0,
        'KRW' => 0,
        'MGA' => 0,
        'PYG' => 0,
        'RWF' => 0,
        'VND' => 0,
        'VUV' => 0,
        'XAF' => 0,
        'XOF' => 0,
        'XPF' => 0
    ];

    protected $_amount;
    protected $_code;

    public static function factory($amount, $code=null) {
        if($amount instanceof ICurrency) {
            return $amount;
        }

        if(is_array($amount)) {
            $newAmount = array_shift($amount);
            $newCode = array_shift($amount);

            if(!empty($newCode)) {
                $code = $newCode;
            }

            $amount = $newAmount;
        }

        if(empty($code)) {
            $code = 'USD';
        }

        return new self($amount, $code);
    }

    public static function fromIntegerAmount($amount, $code) {
        $output = new self(0, $code);
        $output->setAmount($amount / $output->getDecimalFactor());
        return $output;
    }

    public static function isRecognizedCode($code) {
        return isset(self::CURRENCIES[$code]) || in_array($code, self::CURRENCIES);
    }

    public static function getRecognizedCodes() {
        return self::CURRENCIES;
    }

    public function __construct($amount, $code) {
        $this->setAmount($amount);
        $this->setCode($code);
    }

    public function setAmount($amount) {
        $this->_amount = $amount;
        return $this;
    }

    public function getAmount() {
        return $this->_amount;
    }

    public function getIntegerAmount() {
        return (int)round($this->_amount * $this->getDecimalFactor());
    }

    public function getFormattedAmount() {
        return number_format($this->_amount, $this->getDecimalPlaces());
    }

    public static function normalizeCode($code) {
        if(isset(self::CURRENCIES[$code])) {
            $code = self::CURRENCIES[$code];
        }

        return strtoupper($code);
    }


// Code
    public function setCode($code) {
        $this->_code = self::normalizeCode($code);
        return $this;
    }

    public function getCode() {
        return $this->_code;
    }

    public function convert($code, $origRate, $newRate) {
        return $this->setAmount(($this->_amount / $origRate) * $newRate)
            ->setCode($code);
    }

    public function convertNew($code, $origRate, $newRate) {
        $output = clone $this;
        return $output->convert($code, $origRate, $newRate);
    }

    public function hasRecognizedCode() {
        return $this->isRecognizedCode($this->_code);
    }

    public function getDecimalPlaces() {
        if(isset(self::DECIMALS[$this->_code])) {
            return self::DECIMALS[$this->_code];
        }

        return 2;
    }

    public function getDecimalFactor() {
        return pow(10, $this->getDecimalPlaces());
    }


// Math
    public function add($amount) {
        if($amount instanceof ICurrency) {
            if($amount->getCode() != $this->_code) {
                throw new RuntimeException(
                    'Cannot combine different currency amounts'
                );
            }

            $amount = $amount->getAmount();
        }

        $this->_amount += $amount;
        return $this;
    }

    public function addNew($amount) {
        $output = clone $this;
        return $output->add($amount);
    }

    public function subtract($amount) {
        if($amount instanceof ICurrency) {
            if($amount->getCode() != $this->_code) {
                throw new RuntimeException(
                    'Cannot combine different currency amounts'
                );
            }

            $amount = $amount->getAmount();
        }

        $this->_amount -= $amount;
        return $this;
    }

    public function subtractNew($amount) {
        $output = clone $this;
        return $output->subtract($amount);
    }

    public function multiply($factor) {
        $this->_amount *= $factor;
        return $this;
    }

    public function multiplyNew($factor) {
        $output = clone $this;
        return $output->multiply($factor);
    }

    public function divide($factor) {
        $this->_amount /= $factor;
        return $this;
    }

    public function divideNew($factor) {
        $output = clone $this;
        return $output->divide($factor);
    }



// String
    public function toString(): string {
        return $this->getFormattedAmount().' '.$this->_code;
    }

// Dump
    public function getDumpProperties() {
        return $this->toString();
    }
}
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

    protected static $_currencies = [
        '036' => 'AUD',
        '986' => 'BRL',
        '124' => 'CAD',
        '756' => 'CHF',
        '152' => 'CLP',
        '156' => 'CNY',
        '203' => 'CZK',
        '208' => 'DKK',
        '978' => 'EUR',
        '242' => 'FJD',
        '826' => 'GBP',
        '344' => 'HKD',
        '348' => 'HUF',
        '376' => 'ILS',
        '356' => 'INR',
        '392' => 'JPY',
        '410' => 'KRW',
        '418' => 'LAK',
        '484' => 'MXN',
        '458' => 'MYR',
        '578' => 'NOK',
        '554' => 'NZD',
        '598' => 'PGK',
        '608' => 'PHP',
        '985' => 'PLN',
        '090' => 'SBD',
        '752' => 'SEK',
        '702' => 'SGD',
        '764' => 'THB',
        '776' => 'TOP',
        '949' => 'TRY',
        '901' => 'TWD',
        '840' => 'USD',
        '704' => 'VND',
        '548' => 'VUV',
        '882' => 'WST',
        '710' => 'ZAR'
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

    public static function isRecognisedCode($code) {
        return in_array($code, self::$_currencies);
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

    public function setCode($code) {
        if(isset(self::$_currencies[$code])) {
            $code = self::$_currencies[$code];
        }

        $this->_code = strtoupper($code);
        return $this;
    }

    public function getCode() {
        return $this->_code;
    }

    public function convert($code, $origRate, $newRate) {
        return $this->setAmount(($this->_amount / $origRate) * $newRate)
            ->setCode($code);
    }

    public function hasRecognisedCode() {
        return $this->isRecognisedCode($this->_code);
    }


// Dump
    public function getDumpProperties() {
        return $this->_amount.' '.$this->_code;
    }
}
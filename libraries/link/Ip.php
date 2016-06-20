<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link;

use df;
use df\core;
use df\link;
use df\flex;

class Ip implements IIp, core\IDumpable {

    protected $_ip;
    protected $_isV4 = false;
    protected $_isV6 = false;

    public static function factory($ip) {
        if($ip instanceof self) {
            return $ip;
        }

        return new self($ip);
    }

    public function __construct($ip) {
        if($ip == 'localhost') {
            $ip = '127.0.0.1';
        }

        $in = $ip = (string)$ip;
        $this->_isV4 = $hasV4 = strpos($ip, '.') > 0;
        $this->_isV6 = $hasV6 = strpos($ip, ':') !== false;

        if(!$hasV4 && !$hasV6) {
            throw new InvalidArgumentException('Could not detect IPv4 or IPv6 signature - '.$ip);
        }

        if($hasV4 && $hasV6) {
            // IPv6 with IPv4 compat - strip the compat
            $ip = substr($ip, strrpos($ip, ':') + 1);
            $hasV6 = false;
        }

        if($hasV4) {
            $ip = array_pad(explode('.', $ip), 4, 0);

            if(count($ip) > 4) {
                throw new InvalidArgumentException($in.' is not a valid IPv4 address');
            }

            for($i = 0; $i < 4; $i++) {
                if($ip[$i] > 255) {
                    throw new InvalidArgumentException($in.' is not a valid IPv4 address');
                }
            }

            $part7 = base_convert(($ip[0] * 256) + $ip[1], 10, 16);
            $part8 = base_convert(($ip[2] * 256) + $ip[3], 10, 16);

            $ip = '::ffff:'.$part7.':'.$part8;
        }

        $ip = strtolower($ip);

        if(false !== strpos($ip, '::')) {
            $ip = str_replace('::', str_repeat(':0', 8 - substr_count($ip, ':')).':', $ip);
        }

        if(0 === strpos($ip, ':')) {
            $ip = '0'.$ip;
        }

        $this->_ip = $ip;
    }


// Ranges
    public function isInRange($range) {
        return IpRange::factory($range)->check($this);
    }

    public function isV4() {
        return $this->_isV4;
    }

    public function isStandardV4() {
        return $this->_isV4 && !$this->_isV6;
    }

    public function isV6() {
        return $this->_isV6;
    }

    public function isStandardV6() {
        return $this->_isV6 && !$this->_isV4;
    }

    public function isHybrid() {
        return $this->_isV4 && $this->_isV6;
    }

    public function convertToV6() {
        $this->_isV6 = true;
        return $this;
    }


// Strings
    public function __toString(): string {
        try {
            return $this->toString();
        } catch(\Exception $e) {
            return '0.0.0.0';
        } catch(\Error $e) {
            return '0.0.0.0';
        }
    }

    public function toString(): string {
        if($this->isStandardV4()) {
            return $this->getV4String();
        } else {
            return $this->getCompressedV6String();
        }
    }

    public function getV6String() {
        if($this->_isV4) {
            return '0:0:0:0:0:ffff:'.$this->getV4String();
        }

        return $this->_ip;
    }

    public function getCompressedV6String() {
        if($this->_isV4) {
            return '::ffff:'.$this->getV4String();
        }

        $ip = ':'.$this->_ip.':';
        preg_match_all('/(:0)+/', $ip, $matches);

        if(isset($matches[0]) && !empty($matches[0])) {
            $match = '';

            foreach($matches[0] as $zero) {
                if(strlen($zero) > strlen($match)) {
                    $match = $zero;
                }
            }

            $ip = preg_replace('/'.$match.'/', ':', $ip, 1);
        }

        $ip = preg_replace('/((^:)|(:$))/', '', $ip);
        $ip = preg_replace('/((^:)|(:$))/', '::', $ip);

        return $ip;
    }

    public function getV4String() {
        if(!$this->_isV4) {
            throw new RuntimeException('Ip is not in V4 range');
        }

        $pos = strrpos($this->_ip, ':');
        $part1 = base_convert(substr($this->_ip, 15, $pos - 15), 16, 10);
        $part2 = base_convert(substr($this->_ip, $pos + 1), 16, 10);

        $b = ($part1 % 256);
        $a = ($part1 - $b) / 256;
        $d = ($part2 % 256);
        $c = ($part2 - $d) / 256;

        return $a.'.'.$b.'.'.$c.'.'.$d;
    }


// Base conversion
    public function getV6Decimal() {
        return flex\Text::baseConvert($this->getV6Hex(), 16, 10);
    }

    public function getV4Decimal() {
        return flex\Text::baseConvert($this->getV4Hex(), 16, 10);
    }


    public function getV6Hex() {
        $parts = explode(':', $this->_ip);
        $output = '';

        foreach($parts as $part) {
            $output .= str_pad($part, 4, '0', STR_PAD_LEFT);
        }

        return $output;
    }

    public function getV4Hex() {
        if(!$this->isV4()) {
            throw new RuntimeException('Ip is not in V4 range');
        }

        $parts = array_slice(explode(':', $this->_ip), -2);
        $output = '';

        foreach($parts as $part) {
            $output .= str_pad($part, 4, '0', STR_PAD_LEFT);
        }

        return $output;
    }


// Loopback
    public static function getV4Loopback() {
        return new self('127.0.0.1');
    }

    public static function getV6Loopback() {
        return new self('::1');
    }

    public function isLoopback() {
        return $this->isV4Loopback()
            || $this->isV6Loopback();
    }

    public function isV6Loopback() {
        return $this->_ip == '0:0:0:0:0:0:0:1';
    }

    public function isV4Loopback() {
        return $this->_ip == '0:0:0:0:0:ffff:7f00:1';
    }


// Dump
    public function getDumpProperties() {
        return $this->__toString();
    }
}
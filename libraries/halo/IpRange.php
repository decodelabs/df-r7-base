<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo;

use df;
use df\core;
use df\halo;

class IpRange implements IIpRange {
    
    protected $_isV4 = false;
    protected $_start = null;
    protected $_end = null;
    protected $_netmask = null;
    protected $_isValid = false;
    
    public static function factory($range) {
        if($range instanceof self) { 
            return $range;
        }
        
        return new self($range);
    }
    
    public function __construct($range) {
        if(false !== strpos($range, ':')) {
            $this->_parseV6($range);
        } else {
            $this->_parseV4($range);
        }
    }
    
    protected function _parseV4($range) {
        $this->_isV4 = true;
        
        if(false !== strpos($range, '/')) {
            // CIDR
            list($range, $netmask) = explode('/', $range, 2);
            $parts = explode('.', $range);
                
            while(count($parts) < 4) {
                $parts[] = '0';
            }
            
            list($a, $b, $c, $d) = $parts;
            $range = ip2long(sprintf('%u.%u.%u.%u', $a, $b, $c, $d));
            
            if(!$range) {
                return false;
            }
            
            $this->_start = $range;
            
            if(false !== strpos($netmask, '.')) {
                // 255.255.0.0
                $this->_netmask = ip2long(str_replace('*', '0', $netmask));
            } else {
                // /24
                $this->_netmask = -pow(2, (32 - $netmask));
            }
        } else {
            if(false !== strpos($range, '*')) {
                // Wildcards
                $range = str_replace('*', '0', $range).'-'.str_replace('*', '255', $range);
            }
            
            if(false !== strpos($range, '-')) {
                // Simple range
                $parts = explode('-', $range, 2);
                $start = ip2long(trim(array_shift($parts)));
                $end = ip2long(trim(array_shift($parts)));
                
                if($start < 0) {
                    $start += pow(2, 32);
                }
                
                if($end < 0) {
                    $end += pow(2, 32);
                }
                
                $this->_start = sprintf('%08x', $start);
                $this->_end = sprintf('%08x', $end);
            } else {
                // Single ip match
                $this->_start = $this->_end = Ip::factory($range)->getV4Hex();
            }
        }
        
        $this->_isValid = true;
    }
    
    protected function _parseV6($range) {
        $this->_isV4 = false;
        
        if(false !== strpos($range, '/')) {
            // CIDR
            list($range, $netmask) = explode('/', $range, 2);
            $ip = new Ip($range);
            $range = $ip->getV6Decimal();
            
            if(is_numeric($netmask) && $netmask >= 0 && $netmask <= 128) {
                if($netmask == 0) {
                    $range = 0;
                } else {
                    $range = core\string\Manipulator::baseConvert($range, 10, 2, 128);
                    $range = str_pad(substr($range, 0, $netmask), 128, 0, STR_PAD_RIGHT);
                    $range = core\string\Manipulator::baseConvert($range, 2, 10);
                }
            }
            
            $this->_start = core\string\Manipulator::baseConvert($range, 10, 16, 32);
            $this->_end = core\string\Manipulator::baseConvert($range, 10, 2, 128);
            $this->_end = str_pad(substr($this->_end, 0, $netmask), 128, 1, STR_PAD_RIGHT);
            $this->_end = core\string\Manipulator::baseConvert($this->_end, 2, 16, 32);
        } else {
            if(false !== strpos($range, '*')) {
                // Wildcards
                $range = str_replace('*', '0', $range).'-'.str_replace('*', 'ffff', $range);
            }
            
            if(false !== strpos($range, '-')) {
                // Simple range
                $parts = explode('-', $range, 2);
                $start = ip2long(trim(array_shift($parts)));
                $end = ip2long(trim(array_shift($parts)));
                
                if($start < 0) {
                    $start += pow(2, 32);
                }
                
                if($end < 0) {
                    $end += pow(2, 32);
                }
                
                $this->_start = sprintf('%08x', $start);
                $this->_end = sprintf('%08x', $end);
            } else {
                // Single ip match
                $this->_start = $this->_end = Ip::factory($range)->getV4Hex();
            }
        }
        
        $this->_isValid = true;
    }
    
    public function check($ip) {
        if(!$this->_isValid) {
            return false;
        }
        
        $ip = Ip::factory($ip);
        
        if($this->_isV4) {
            if(!$ip->isV4()) {
                return false;
            }
            
            if($this->_end !== null) {
                // range
                $hex = $ip->getV4Hex();
                return $this->_start <= $hex && $hex <= $this->_end;
            } else {
                // netmask
                return ($ip->getV4Decimal() & $this->_netmask)
                    == ($this->_start & $this->_netmask);
            }
        } else {
            // range
            $hex = $ip->getV6Hex();
            return $this->_start <= $hex && $hex <= $this->_end;
        }
    }
}
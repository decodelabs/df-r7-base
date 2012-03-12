<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\socket\address;

use df;
use df\core;
use df\halo;

abstract class Base implements IAddress {
    
    use core\TStringProvider;
    use core\uri\TUrl_TransientScheme;
    
    public static function factory($address) {
        if($address instanceof IAddress) {
            return $address;
        }
        
        $parts = explode('://', $address, 2);
        
        $temp = array_pop($parts);
        $proto = strtolower(array_shift($parts));
        
        if(!strlen($proto)) {
            if(false !== stristr(str_replace('\\', '/', $temp), '/')) {
                $proto = 'unix';
            } else {
                $proto = 'tcp';
            }
        }
        
        if($proto == 'unix' || $proto == 'udg') {
            return new Unix($address);
        } else {
            return new Inet($address);
        }
    }
    
    public function __construct($url=null) {
        if($url !== null) {
            $this->import($url);
        }
    }
    
    
// Dump
    public function getDumpProperties() {
        return $this->toString();
    }
}
<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo;

use df;
use df\core;
use df\halo;

// Exceptions
interface IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}
class RuntimeException extends \RuntimeException implements IException {}


// Interfaces
interface IIp extends core\IStringProvider {
    // Ranges
    public function isInRange($range);
    public function isV4();
    public function isStandardV4();
    public function isV6();
    public function isStandardV6();
    public function isHybrid();
    
    // Strings
    public function getV6String();
    public function getCompressedV6String();
    public function getV4String();
    
    // Base conversion
    public function getV6Decimal();
    public function getV4Decimal();
    public function getV6Hex();
    public function getV4Hex();
    
    // Loopback
    public static function getV4Loopback();
    public static function getV6Loopback();
    public function isLoopback();
    public function isV6Loopback();
    public function isV4Loopback();
}


interface IIpRange {
    public function check($ip);
}
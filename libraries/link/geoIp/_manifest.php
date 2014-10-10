<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\geoIp;

use df;
use df\core;
use df\link;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}


// Interfaces
interface IHandler {
    public static function isAdapterAvailable($name);
    public static function getAdapterList();
    public static function getAvailableAdapterList();
    public function getAdapter();
    public function lookup($ip);
}

interface IAdapter {
    public static function fromConfig();
    public static function isAvailable();
    public function getName();
    public function lookup(link\IIp $ip, IResult $result);
}

trait TAdapter {

    public function getName() {
        $parts = explode('\\', get_class($this));
        return array_pop($parts);
    }
}


interface IResult {
    public function getIp();
    public function getContinent();
    public function getContinentName();
    public function getContinentGeonameId();
    public function getCountry();
    public function getCountryName();
    public function getCountryGeonameId();
    public function getRegion();
    public function getRegionName();
    public function getRegionGeonameId();
    public function getCityName();
    public function getCityGeonameId();
    public function getPostcode();
    public function getLongitude();
    public function getLatitude();
    public function hasLatLong();
    public function getTimezone();
}
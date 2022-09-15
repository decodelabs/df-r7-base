<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\link\geoIp;

use DecodeLabs\Compass\Ip;

class Result
{
    public Ip $ip;
    public $continent;
    public $continentName;
    public $continentGeonameId;
    public $country;
    public $countryName;
    public $countryGeonameId;
    public $region;
    public $regionName;
    public $regionGeonameId;
    public $cityName;
    public $cityGeonameId;
    public $postcode;
    public $longitude;
    public $latitude;
    public $timezone;


    public function __construct(Ip $ip)
    {
        $this->ip = $ip;
    }

    public function getIp(): Ip
    {
        return $this->ip;
    }

    public function getContinent()
    {
        return $this->continent;
    }

    public function getContinentName()
    {
        return $this->continentName;
    }

    public function getContinentGeonameId()
    {
        return $this->continentGeonameId;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function getCountryName()
    {
        return $this->countryName;
    }

    public function getCountryGeonameId()
    {
        return $this->countryGeonameId;
    }

    public function getRegion()
    {
        return $this->region;
    }

    public function getRegionName()
    {
        return $this->regionName;
    }

    public function getRegionGeonameId()
    {
        return $this->regionGeonameId;
    }

    public function getCityName()
    {
        return $this->cityName;
    }

    public function getCityGeonameId()
    {
        return $this->cityGeonameId;
    }

    public function getPostcode()
    {
        return $this->postcode;
    }

    public function getLongitude()
    {
        return $this->longitude;
    }

    public function getLatitude()
    {
        return $this->latitude;
    }

    public function hasLatLong()
    {
        return $this->longitude !== null && $this->latitude !== null;
    }

    public function getTimezone()
    {
        return $this->timezone;
    }
}

<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\geoIp\adapter;

use df;
use df\core;
use df\link;
use df\opal;

class MaxMindDb implements link\geoIp\IAdapter {
    
    use link\geoIp\TAdapter;

    protected $_reader;

    public static function fromConfig() {
        $file = self::_getFileFromConfig();

        if(empty($file)) {
            throw new link\geoIp\RuntimeException(
                'MaxMind DB file has not been set in config'
            );
        }

        if(!is_file($file)) {
            throw new link\geoIp\RuntimeException(
                'MaxMind DB file could not be found'
            );
        }

        return new self(new opal\mmdb\Reader($file));
    }

    public static function isAvailable() {
        $file = self::_getFileFromConfig();
        return !empty($file) && is_file($file);
    }

    protected static function _getFileFromConfig() {
        $config = link\geoIp\Config::getInstance();
        $settings = $config->getSettingsFor('MaxMindDb');
        $file = $settings['file'];

        if(dirname($file) == '.') {
            $file = df\Launchpad::$application->getLocalDataStoragePath().'/geoIp/'.$file;
        }

        return $file;
    }

    public function __construct(opal\mmdb\IReader $reader) {
        $this->_reader = $reader;
    }

    public function lookup(link\IIp $ip, link\geoIp\IResult $result) {
        $data = $this->_reader->get($ip);

        if($data === null) {
            return $result;
        }

        if(isset($data['continent']['code'])) {
            $result->continent = $data['continent']['code'];
            $result->continentName = $data['continent']['names']['en'];
            $result->continentGeonameId = $data['continent']['geoname_id'];
        }

        if(isset($data['country']['iso_code'])) {
            $result->country = $data['country']['iso_code'];
            $result->countryName = $data['country']['names']['en'];
            $result->countryGeonameId = $data['country']['geoname_id'];
        }

        if(isset($data['subdivisions'])) {
            $region = array_pop($data['subdivisions']);
            $result->region = $region['iso_code'];
            $result->regionName = $region['names']['en'];
            $result->regionGeonameId = $region['geoname_id'];
        }

        if(isset($data['city']['names'])) {
            $result->cityName = $data['city']['names']['en'];
            $result->cityGeonameId = $data['city']['geoname_id'];
        }

        if(isset($data['postal']['code'])) {
            $result->postcode = $data['postal']['code'];
        }

        if(isset($data['location']['latitude'])) {
            $result->latitude = $data['location']['latitude'];
            $result->longitude = $data['location']['longitude'];
        }

        if(isset($data['location']['time_zone'])) {
            $result->timezone = $data['location']['time_zone'];
        }

        return $result;
    }
}
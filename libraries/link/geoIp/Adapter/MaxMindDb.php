<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\link\geoIp\Adapter;

use DecodeLabs\Compass\Ip;
use DecodeLabs\Exceptional;
use DecodeLabs\Genesis;
use df\link\geoIp\Adapter;
use df\link\geoIp\Config;

use df\link\geoIp\Result;
use df\opal\mmdb\IReader;
use df\opal\mmdb\Reader;

class MaxMindDb implements Adapter
{
    protected $reader;

    public static function fromConfig(): Adapter
    {
        $file = self::getFileFromConfig();

        if (empty($file)) {
            throw Exceptional::Runtime(
                'MaxMind DB file has not been set in config'
            );
        }

        if (!is_file($file)) {
            throw Exceptional::Runtime(
                'MaxMind DB file could not be found'
            );
        }

        return new self(new Reader($file));
    }

    public static function isAvailable(): bool
    {
        $file = self::getFileFromConfig();
        return !empty($file) && is_file($file);
    }

    protected static function getFileFromConfig(): ?string
    {
        $config = Config::getInstance();
        $settings = $config->getSettingsFor('MaxMindDb');
        $file = $settings['file'];

        if (dirname((string)$file) == '.') {
            $file = Genesis::$hub->getLocalDataPath() . '/geoIp/' . $file;
        }

        return $file;
    }

    public function __construct(IReader $reader)
    {
        $this->reader = $reader;
    }

    public function getName(): string
    {
        $parts = explode('\\', get_class($this));
        return (string)array_pop($parts);
    }

    public function lookup(
        Ip|string $ip,
        Result $result
    ): Result {
        $ip = Ip::parse($ip);

        try {
            $data = $this->reader->get($ip);
        } catch (\Exception $e) {
            return $result;
        }

        if ($data === null) {
            return $result;
        }

        if (isset($data['continent']['code'])) {
            $result->continent = $data['continent']['code'];
            $result->continentName = $data['continent']['names']['en'];

            if (isset($data['continent']['geoname_id'])) {
                $result->continentGeonameId = $data['continent']['geoname_id'];
            }
        }

        if (isset($data['country']['iso_code'])) {
            $result->country = $data['country']['iso_code'];
            $result->countryName = $data['country']['names']['en'];

            if (isset($data['country']['geoname_id'])) {
                $result->countryGeonameId = $data['country']['geoname_id'];
            }
        }

        if (isset($data['subdivisions'])) {
            if ($region = array_pop($data['subdivisions'])) {
                $result->regionName = $region['names']['en'];

                if (isset($region['iso_code'])) {
                    $result->region = $region['iso_code'];
                }

                if (isset($region['geoname_id'])) {
                    $result->regionGeonameId = $region['geoname_id'];
                }
            }
        }

        if (isset($data['city']['names'])) {
            $result->cityName = $data['city']['names']['en'];

            if (isset($data['city']['geoname_id'])) {
                $result->cityGeonameId = $data['city']['geoname_id'];
            }
        }

        if (isset($data['postal']['code'])) {
            $result->postcode = $data['postal']['code'];
        }

        if (isset($data['location']['latitude'])) {
            $result->latitude = $data['location']['latitude'];
            $result->longitude = $data['location']['longitude'];
        }

        if (isset($data['location']['time_zone'])) {
            $result->timezone = $data['location']['time_zone'];

            // Fix Yangon transition
            if ($result->timezone === 'Asia/Yangon') {
                $result->timezone = 'Asia/Rangoon';
            }
        }

        return $result;
    }
}

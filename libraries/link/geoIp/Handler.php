<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\link\geoIp;

use DecodeLabs\Compass\Ip;
use DecodeLabs\Exceptional;
use DecodeLabs\R7\Config\GeoIp as GeoIpConfig;
use DecodeLabs\R7\Legacy;

class Handler
{
    protected $_adapter;

    public static function factory($adapter = null): Handler
    {
        if (!$adapter instanceof Adapter) {
            $config = GeoIpConfig::load();

            if (!$config->isEnabled()) {
                return new self();
            }

            if ($adapter === null) {
                $adapter = $config->getDefaultAdapter();
            }

            $class = 'df\\link\\geoIp\\Adapter\\' . ucfirst($adapter);

            if (!class_exists($class)) {
                throw Exceptional::Runtime(
                    'GeoIp adapter ' . $adapter . ' could not be found'
                );
            }

            try {
                $adapter = $class::fromConfig();
            } catch (RuntimeException $e) {
                $adapter = null;
            }
        }

        return new self($adapter);
    }

    public static function isAdapterAvailable(string $name): bool
    {
        $class = 'df\\link\\geoIp\\Adapter\\' . ucfirst($name);

        if (!class_exists($class)) {
            return false;
        }

        return $class::isAvailable();
    }

    public static function getAdapterList(): array
    {
        $output = [];

        foreach (Legacy::getLoader()->lookupClassList('link/geoIp/Adapter') as $name => $class) {
            $output[$name] = $class::isAvailable();
        }

        ksort($output);
        return $output;
    }

    public static function getAvailableAdapterList(): array
    {
        $output = [];

        foreach (self::getAdapterList() as $name => $available) {
            if ($available) {
                $output[$name] = true;
            }
        }

        return $output;
    }

    public function __construct(Adapter $adapter = null)
    {
        $this->_adapter = $adapter;
    }

    public function getAdapter(): Adapter
    {
        return $this->_adapter;
    }

    public function lookup(
        Ip|string $ip
    ): Result {
        $ip = Ip::parse($ip);
        $result = new Result($ip);

        if ($this->_adapter) {
            return $this->_adapter->lookup($ip, $result);
        }

        return $result;
    }
}

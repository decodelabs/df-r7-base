<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\geoIp;

use df\Launchpad;
use df\link\Ip;
use df\link\geoIp\Adapter;
use df\link\geoIp\Result;

use DecodeLabs\Exceptional;

class Handler
{
    protected $_adapter;

    public static function factory($adapter=null): Handler
    {
        if (!$adapter instanceof Adapter) {
            $config = Config::getInstance();

            if (!$config->isEnabled()) {
                return new self();
            }

            if ($adapter === null) {
                $adapter = $config->getDefaultAdapter();
            }

            $class = 'df\\link\\geoIp\\Adapter\\'.ucfirst($adapter);

            if (!class_exists($class)) {
                throw Exceptional::Runtime(
                    'GeoIp adapter '.$adapter.' could not be found'
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
        $class = 'df\\link\\geoIp\\Adapter\\'.ucfirst($name);

        if (!class_exists($class)) {
            return false;
        }

        return $class::isAvailable();
    }

    public static function getAdapterList(): array
    {
        $output = [];

        foreach (Launchpad::$loader->lookupClassList('link/geoIp/Adapter') as $name => $class) {
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

    public function __construct(Adapter $adapter=null)
    {
        $this->_adapter = $adapter;
    }

    public function getAdapter(): Adapter
    {
        return $this->_adapter;
    }

    public function lookup($ip): Result
    {
        $ip = Ip::factory($ip);
        $result = new Result($ip);

        if ($this->_adapter) {
            return $this->_adapter->lookup($ip, $result);
        }

        return $result;
    }
}

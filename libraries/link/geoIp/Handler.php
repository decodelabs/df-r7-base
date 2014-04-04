<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\geoIp;

use df;
use df\core;
use df\link;

class Handler implements IHandler {
    
    protected $_adapter;

    public static function factory($adapter=null) {
        if(!$adapter instanceof IAdapter) {
            $config = Config::getInstance();

            if(!$config->isEnabled()) {
                return new self();
            }

            if($adapter === null) {
                $adapter = $config->getDefaultAdapter();
            }

            $class = 'df\\link\\geoIp\\adapter\\'.ucfirst($adapter);

            if(!class_exists($class)) {
                throw new RuntimeException(
                    'GeoIp adapter '.$adapter.' could not be found'
                );
            }

            try {
                $adapter = $class::fromConfig();
            } catch(RuntimeException $e) {
                $adapter = null;
            }
        }

        return new self($adapter);
    }

    public static function isAdapterAvailable($name) {
        $class = 'df\\link\\geoIp\\adapter\\'.ucfirst($name);

        if(!class_exists($class)) {
            return false;
        }

        return $class::isAvailable();
    }

    public static function getAdapterList() {
        $output = [];

        foreach(df\Launchpad::$loader->lookupFileList('link/geoIp/adapter', ['php']) as $baseName => $path) {
            $name = substr($baseName, 0, -4);
            
            if($name === 'Base' || $name === '_manifest') {
                continue;
            }
            
            $class = 'df\\link\\geoIp\\adapter\\'.$name;

            if(class_exists($class)) {
                $output[$name] = $class::isAvailable();
            }
        }
        
        ksort($output);
        return $output;
    }

    public static function getAvailableAdapterList() {
        $output = [];

        foreach(self::getAdapterList() as $name => $available) {
            if($available) {
                $output[$name] = true;
            }
        }

        return $output;
    }

    public function __construct(IAdapter $adapter=null) {
        $this->_adapter = $adapter;
    }

    public function getAdapter() {
        return $this->_adapter;
    }

    public function lookup($ip) {
        $ip = link\Ip::factory($ip);
        $result = new Result($ip);

        if($this->_adapter) {
            return $this->_adapter->lookup($ip, $result);
        }
        
        return $result;
    }
}
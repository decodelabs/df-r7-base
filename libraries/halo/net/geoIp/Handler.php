<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\net\geoIp;

use df;
use df\core;
use df\halo;

class Handler implements IHandler {
    
    protected $_adapter;

    public static function factory($adapter=null) {
        if(!$adapter instanceof IAdapter) {
            if($adapter === null) {
                $config = Config::getInstance();
                $adapter = $config->getDefaultAdapter();
            }

            $class = 'df\\halo\\net\\geoIp\\adapter\\'.ucfirst($adapter);

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

    public function __construct(IAdapter $adapter=null) {
        $this->_adapter = $adapter;
    }

    public function getAdapter() {
        return $this->_adapter;
    }

    public function lookup($ip) {
        $ip = halo\net\Ip::factory($ip);
        $result = new Result($ip);

        if($this->_adapter) {
            return $this->_adapter->lookup($ip, $result);
        }
        
        return $result;
    }
}
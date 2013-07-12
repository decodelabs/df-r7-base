<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\mint\gateway;

use df;
use df\core;
use df\mint;
    
abstract class Base implements mint\IGateway {

    public static function factory($name) {
        $class = 'df\\mint\\gateway\\'.ucfirst($name);

        if(!class_exists($class)) {
            throw new mint\RuntimeException(
                'Payment gateway '.$name.' could not be found'
            );
        }

        return new $class();
    }
}
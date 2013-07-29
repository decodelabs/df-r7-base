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

    protected $_defaultCurrency = 'USD';

    public static function factory($name, $settings) {
        $class = 'df\\mint\\gateway\\'.ucfirst($name);

        if(!class_exists($class)) {
            throw new mint\RuntimeException(
                'Payment gateway '.$name.' could not be found'
            );
        }

        $settings = core\collection\Tree::factory($settings);
        // TODO: lookup config

        return new $class($settings);
    }

    protected function __construct(core\collection\ITree $settings) {
        if($settings->has('defaultCurrency')) {
            $this->setDefaultCurrency($settings['defaultCurrency']);
        }
    }


    public function setDefaultCurrency($code) {
        $code = strtoupper($code);

        if(!mint\Currency::isRecognizedCode($code)) {
            throw new mint\InvalidArgumentException(
                'Invalid currency code: '.$code
            );  
        }

        if(!$this->isCurrencySupported($code)) {
            throw new mint\InvalidArgumentException(
                'Currency not supported: '.$code
            );
        }

        $this->_defaultCurrency = $code;
        return $this;
    }

    public function getDefaultCurrency() {
        return $this->_defaultCurrency;
    }

    public function isCurrencySupported($code) {
        $code = mint\Currency::normalizeCode($code);
        return in_array($code, $this->getSupportedCurrencies());
    }
}
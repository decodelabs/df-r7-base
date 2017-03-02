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
            throw core\Error::{'ENotFound'}(
                'Payment gateway '.$name.' could not be found'
            );
        }

        return new $class(core\collection\Tree::factory($settings));
    }

    protected function __construct(core\collection\ITree $settings) {
        if($settings->has('defaultCurrency')) {
            $this->setDefaultCurrency($settings['defaultCurrency']);
        }
    }


    public function setDefaultCurrency($code) {
        $code = strtoupper($code);

        if(!mint\Currency::isRecognizedCode($code)) {
            throw core\Error::{'mint/ECurrency,EArgument'}(
                'Invalid currency code: '.$code
            );
        }

        if(!$this->isCurrencySupported($code)) {
            throw core\Error::{'mint/ECurrency,EArgument'}(
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

    public function submitCharge(mint\IChargeRequest $charge): mint\IChargeResult {
        if($charge instanceof mint\ICustomerChargeRequest
        && $this instanceof mint\ICustomerTrackingGateway) {
            return $this->submitCustomerCharge($charge);
        } else if($charge instanceof mint\IStandaloneChargeRequest) {
            return $this->submitStandaloneCharge($charge);
        } else {
            throw core\Error::{'mint/ECharge,EArgument'}([
                'message' => 'Gateway doesn\'t support this type of charge',
                'data' => $charge
            ]);
        }
    }
}
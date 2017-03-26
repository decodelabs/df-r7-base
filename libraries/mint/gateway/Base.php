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

    public static function factory(string $name, $settings=null): mint\IGateway {
        $class = 'df\\mint\\gateway\\'.ucfirst($name);

        if(!class_exists($class)) {
            throw core\Error::{'ENotFound'}(
                'Payment gateway '.$name.' could not be found'
            );
        }

        return new $class(core\collection\Tree::factory($settings));
    }

    protected function __construct(core\collection\ITree $settings) {}

    public function isCurrencySupported($code): bool {
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

    public function newStandaloneCharge(mint\ICurrency $amount, mint\ICreditCardReference $card, string $description=null, string $email=null) {
        return new mint\charge\Standalone($amount, $card, $description, $email);
    }

    public function newCustomerCharge(mint\ICurrency $amount, mint\ICreditCardReference $card, string $customerId, string $description=null) {
        return new mint\charge\Customer($amount, $card, $customerId, $description);
    }

    public function authorizeCharge(mint\IChargeRequest $charge): mint\IChargeResult {
        if($charge instanceof mint\ICustomerChargeRequest
        && $this instanceof mint\ICustomerTrackingCaptureProviderGateway) {
            return $this->authorizeCustomerCharge($charge);
        } else if($charge instanceof mint\IStandaloneChargeRequest
        && $this instanceof mint\ICaptureProviderGateway) {
            return $this->authorizeStandaloneCharge($charge);
        } else {
            throw core\Error::{'mint/ECharge,EArgument'}([
                'message' => 'Gateway doesn\'t support authorizing this type of charge',
                'data' => $charge
            ]);
        }
    }

    public function newChargeCapture(string $id) {
        return new mint\charge\Capture($id);
    }

    public function newChargeRefund(string $id, mint\ICurrency $amount=null) {
        return new mint\charge\Refund($id, $amount);
    }
}
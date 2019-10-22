<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\mint\gateway;

use df;
use df\core;
use df\mint;

abstract class Base implements mint\IGateway
{
    public static function factory(string $name, $settings=null): mint\IGateway
    {
        $class = 'df\\mint\\gateway\\'.ucfirst($name);

        if (!class_exists($class)) {
            throw core\Error::{'ENotFound'}(
                'Payment gateway '.$name.' could not be found'
            );
        }

        return new $class(core\collection\Tree::factory($settings));
    }

    public function isTesting(): bool
    {
        return false;
    }

    public function getApiKey(): ?string
    {
        return null;
    }

    public function getPublicKey(): ?string
    {
        return null;
    }

    public function isCurrencySupported($code): bool
    {
        $code = mint\Currency::normalizeCode($code);
        return in_array($code, $this->getSupportedCurrencies());
    }

    public function getApiIps(): ?array
    {
        return null;
    }

    public function getWebhookIps(): ?array
    {
        return null;
    }


    public function submitCharge(mint\IChargeRequest $charge): string
    {
        if ($charge instanceof mint\ICustomerChargeRequest
        && $this instanceof mint\ICustomerTrackingGateway) {
            return $this->submitCustomerCharge($charge);
        } else {
            return $this->submitStandaloneCharge($charge);
        }
    }

    public function newStandaloneCharge(mint\ICurrency $amount, mint\ICreditCardReference $card, string $description=null, string $email=null): mint\IChargeRequest
    {
        return new mint\charge\Request($amount, $card, $description, $email);
    }
}

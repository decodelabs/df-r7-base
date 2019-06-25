<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\mint\config;

use df;
use df\core;
use df\apex;
use df\axis;
use df\mint;

class Unit extends axis\unit\Config implements mint\IModelConfig
{
    const ID = 'Mint';
    const USE_ENVIRONMENT_ID_BY_DEFAULT = true;

    public function getDefaultValues(): array
    {
        return [
            'enabled' => false,
            'testing' => false,
            'primaryAccount' => null,
            'subscriptionAccount' => null,
            'accounts' => [
                '!stripe' => [
                    'gateway' => 'Stripe',
                    'enabled' => false,
                    'testing' => true,
                    'liveApiKey' => null,
                    'livePublicKey' => null,
                    'testApiKey' => null,
                    'testPublicKey' => null
                ],
                '!paypal' => [
                    'gateway' => 'PaypalExpressCheckout',
                    'enabled' => false,
                    'testing' => true,
                    'email' => 'name@domain.com'
                ]
            ]
        ];
    }


    // Enabled
    public function isEnabled(bool $flag=null)
    {
        if ($flag !== null) {
            $this->values->enabled = $flag;
            return $this;
        }

        return (bool)$this->values['enabled'];
    }


    // Accounts
    public function getPrimaryAccount(): ?string
    {
        return $this->values['primaryAccount'];
    }

    public function getPrimarySettings(): ?core\collection\ITree
    {
        if (null === ($key = $this->getPrimaryAccount())) {
            return null;
        }

        return $this->getSettingsFor($key);
    }

    public function getSubscriptionAccount(): ?string
    {
        return $this->values->get('subscriptionAccount', $this->values['primaryAccount']);
    }

    public function getSubscriptionSettings(): ?core\collection\ITree
    {
        if (null === ($key = $this->getSubscriptionAccount())) {
            return null;
        }

        return $this->getSettingsFor($key);
    }

    public function getSettingsFor(string $account): ?core\collection\ITree
    {
        if (!$this->values->get('enabled', true)) {
            return null;
        }

        if (!isset($this->values->accounts->{$account})) {
            return null;
        }

        if (!$this->values->accounts->{$account}->get('enabled', true)) {
            return null;
        }

        $output = clone $this->values->accounts->{$account};

        if ($this->values['testing']) {
            $output['testing'] = true;
        }

        return $output;
    }
}

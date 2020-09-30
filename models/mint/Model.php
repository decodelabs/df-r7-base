<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\mint;

use df;
use df\core;
use df\apex;
use df\axis;
use df\mint;

use DecodeLabs\Exceptional;

class Model extends axis\Model
{
    protected $_enabled;
    protected $_primaryAccount = false;
    protected $_subscriptionAccount = false;
    protected $_gateways = [];

    public function isEnabled(): bool
    {
        if ($this->_enabled === null) {
            $this->_enabled = $this->config->isEnabled();
        }

        return $this->_enabled;
    }

    public function getPrimaryGateway(): ?mint\IGateway
    {
        return $this->_getPrimaryGateway(false);
    }

    public function getPrimaryTestingGateway(): ?mint\IGateway
    {
        return $this->_getPrimaryGateway(true);
    }

    private function _getPrimaryGateway(bool $testing): ?mint\IGateway
    {
        if (!$this->isEnabled()) {
            throw Exceptional::{'df/mint/gateway/Setup'}([
                'message' => 'Payments are not enabled'
            ]);
        }

        if ($this->_primaryAccount === false) {
            $this->_primaryAccount = $this->config->getPrimaryAccount();
        }

        if ($this->_primaryAccount === null) {
            throw Exceptional::{'df/mint/gateway/Setup'}([
                'message' => 'Primary account has not been defined'
            ]);
        }

        if ($testing) {
            return $this->getTestingGateway($this->_primaryAccount);
        } else {
            return $this->getGateway($this->_primaryAccount);
        }
    }

    public function getSubscriptionGateway(): ?mint\IGateway
    {
        return $this->_getSubscriptionGateway(false);
    }

    public function getSubscriptionTestingGateway(): ?mint\IGateway
    {
        return $this->_getSubscriptionGateway(true);
    }

    private function _getSubscriptionGateway(bool $testing): ?mint\IGateway
    {
        if (!$this->isEnabled()) {
            throw Exceptional::{'df/mint/gateway/Setup'}([
                'message' => 'Payments are not enabled'
            ]);
        }

        if ($this->_subscriptionAccount === false) {
            $this->_subscriptionAccount = $this->config->getSubscriptionAccount();
        }

        if ($this->_subscriptionAccount === null) {
            throw Exceptional::{'df/mint/gateway/Setup'}([
                'message' => 'Subscription account has not been defined'
            ]);
        }

        if ($testing) {
            return $this->getTestingGateway($this->_subscriptionAccount);
        } else {
            return $this->getGateway($this->_subscriptionAccount);
        }
    }

    public function getGateway(string $account): ?mint\IGateway
    {
        return $this->_getGateway($account, false);
    }

    public function getTestingGateway(string $account): ?mint\IGateway
    {
        return $this->_getGateway($account, true);
    }

    private function _getGateway(string $account, bool $testing): ?mint\IGateway
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $key = $account;

        if ($testing) {
            $key .= ':testing';
        }

        if (!array_key_exists($key, $this->_gateways)) {
            $settings = $this->config->getSettingsFor($account);

            if ($settings === null) {
                $this->_gateways[$key] = null;

                throw Exceptional::{'df/mint/gateway/Setup'}([
                    'message' => 'Gateway is not available',
                    'data' => $account
                ]);
            }

            if (!$name = $settings['gateway']) {
                $this->_gateways[$key] = null;

                throw Exceptional::{'df/mint/gateway/Setup'}([
                    'message' => 'Gateway not defined',
                    'data' => $settings
                ]);
            }

            if ($testing) {
                $settings['testing'] = true;
            }

            $this->_gateways[$key] = mint\gateway\Base::factory($name, $settings);
        }

        if (!$gateway = $this->_gateways[$key]) {
            throw Exceptional::{'df/mint/gateway/Setup'}([
                'message' => 'Gateway '.$account.' is not available'
            ]);
        }

        return $gateway;
    }
}

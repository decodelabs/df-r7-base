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

class Model extends axis\Model {

    protected $_enabled;
    protected $_primaryAccount = false;
    protected $_subscriptionAccount = false;
    protected $_gateways = [];

    public function isEnabled(): bool {
        if($this->_enabled === null) {
            $this->_enabled = $this->config->isEnabled();
        }

        return $this->_enabled;
    }

    public function getPrimaryGateway(): mint\IGateway {
        if(!$this->isEnabled()) {
            throw core\Error::{'mint\gateway\ESetup'}([
                'message' => 'Payments are not enabled'
            ]);
        }

        if($this->_primaryAccount === false) {
            $this->_primaryAccount = $this->config->getPrimaryAccount();
        }

        if($this->_primaryAccount === null) {
            throw core\Error::{'mint\gateway\ESetup'}([
                'message' => 'Primary account has not been defined'
            ]);
        }

        return $this->getGateway($this->_primaryAccount);
    }

    public function getSubscriptionGateway(): mint\IGateway {
        if(!$this->isEnabled()) {
            throw core\Error::{'mint\gateway\ESetup'}([
                'message' => 'Payments are not enabled'
            ]);
        }

        if($this->_subscriptionAccount === false) {
            $this->_subscriptionAccount = $this->config->getSubscriptionAccount();
        }

        if($this->_subscriptionAccount === null) {
            throw core\Error::{'mint\gateway\ESetup'}([
                'message' => 'Subscription account has not been defined'
            ]);
        }

        return $this->getGateway($this->_subscriptionAccount);
    }

    public function getGateway(string $account): mint\IGateway {
        if(!$this->isEnabled()) {
            return null;
        }

        if(!array_key_exists($account, $this->_gateways)) {
            $settings = $this->config->getSettingsFor($account);

            if($settings === null) {
                $this->_gateways[$account] = null;

                throw core\Error::{'mint\gateway\ESetup'}([
                    'message' => 'Gateway is not available',
                    'data' => $account
                ]);
            }

            if(!$name = $settings['gateway']) {
                $this->_gateways[$account] = null;

                throw core\Error::{'mint\gateway\ESetup'}([
                    'message' => 'Gateway not defined',
                    'data' => $settings
                ]);
            }

            $this->_gateways[$account] = mint\gateway\Base::factory($name, $settings);
        }

        if(!$gateway = $this->_gateways[$account]) {
            throw core\Error::{'mint\gateway\ESetup'}([
                'message' => 'Gateway is not available'
            ]);
        }

        return $gateway;
    }
}
<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\mint\gateway;

use df;
use df\core;
use df\mint;
use df\spur;
    
class Stripe extends Base {

    protected $_mediator;

    protected function __construct(core\collection\ITree $settings) {
        if(!$settings->has('apiKey')) {
            throw new mint\InvalidArgumentException(
                'Stripe API key not set in config'
            );
        }
        
        $this->_mediator = new spur\payment\stripe\Mediator($settings['apiKey']);
        parent::__construct($settings);
    }

    public function getSupportedCurrencies() {
        $cache = Stripe_Cache::getInstance();
        $key = $this->_getCacheKeyPrefix().'supportedCurrencies';
        $output = $cache->get($key);

        if(empty($output)) {
            $output = $this->_mediator->getAccountDetails()->getSupportedCurrencies();
            $cache->set($key, $output);
        }

        return $output;
    }

    public function submitCharge(mint\ICharge $charge) {
        core\stub($charge);
    }

    protected function _getCacheKeyPrefix() {
        return $this->_mediator->getApiKey().'-';
    }
}


class Stripe_Cache extends core\cache\Base {
    const CACHE_ID = 'payment/stripe';
}
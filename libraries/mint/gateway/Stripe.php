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
            throw core\Error::{'ESetup'}(
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

    public function submitStandaloneCharge(mint\IStandaloneChargeRequest $charge): mint\IChargeResult {
        $request = $this->_mediator->newChargeRequest(
            $charge->getAmount(),
            $charge->getCard(),
            $charge->getDescription(),
            $charge->getEmailAddress()
        );

        return $this->_submitCharge($request);
    }

    protected function _submitCharge(spur\payment\stripe\IChargeRequest $request) {
        $result = new mint\charge\Result();

        try {
            $request->submit();
        } catch(spur\payment\stripe\ECard $e) {
            $result->isSuccessful(false);
            $result->setMessage($e->getMessage());

            switch($e->getData()['code'] ?? null) {
                case 'invalid_number':
                case 'incorrect_number':
                    $result->addInvalidFields('number');
                    break;

                case 'invalid_expiry_month':
                    $result->addInvalidFields('expiryMonth');
                    break;

                case 'invalid_expiry_year':
                    $result->addInvalidFields('expiryYear');
                    break;

                case 'invalid_cvc':
                case 'incorrect_cvc':
                    $result->addInvalidFields('cvc');
                    break;

                case 'invalid_swipe_data':
                    // hmm
                    break;

                case 'expired_card':
                    $result->isCardExpired(true);
                    break;

                case 'incorrect_zip':
                    $result->addInvalidFields('billingAddress');
                    break;

                case 'card_declined':
                    // meh, already covered
                    break;

                case 'missing':
                    $result->isCardUnavailable(true);
                    break;

                case 'processing_error':
                    $result->isApiFailure(true);
                    break;
            }

            return $result;
        } catch(spur\payment\stripe\EApi $e) {
            core\logException($e);

            $result->isSuccessful(false);
            $result->isApiFailure(true);
            $result->setMessage($e->getMessage());

            return $result;
        }

        $result->isSuccessful(true);
        $result->isCardAccepted(true);

        return $result;
    }

    protected function _getCacheKeyPrefix() {
        return $this->_mediator->getApiKey().'-';
    }
}


class Stripe_Cache extends core\cache\Base {
    const CACHE_ID = 'payment/stripe';
}
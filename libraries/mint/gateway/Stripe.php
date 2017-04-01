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

class Stripe extends Base implements
    mint\ICaptureProviderGateway,
    mint\IRefundProviderGateway {

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


// Currency
    public function getSupportedCurrencies(): array {
        $cache = Stripe_Cache::getInstance();
        $key = $this->_getCacheKeyPrefix().'supportedCurrencies';
        $output = $cache->get($key);

        if(empty($output)) {
            $output = $this->_mediator->getAccountDetails()->getSupportedCurrencies();
            $cache->set($key, $output);
        }

        return $output;
    }


// Charges
    public function submitStandaloneCharge(mint\IStandaloneChargeRequest $charge): mint\IChargeResult {
        $request = $this->_mediator->newChargeRequest(
            $charge->getAmount(),
            $charge->getCard(),
            $charge->getDescription(),
            $charge->getEmailAddress()
        );

        return $this->_submitCharge(function() use($request) {
            $charge = $request->submit();
            return $charge->getId();
        });
    }

    public function submitCustomerCharge(mint\ICustomerChargeRequest $charge): mint\IChargeResult {
        $request = $this->_mediator->newChargeRequest(
            $charge->getAmount(),
            $charge->getCard(),
            $charge->getDescription()
        );

        $request->setCustomerId($charge->getCustomerId());

        return $this->_submitCharge(function() use($request) {
            $charge = $request->submit();
            return $charge->getId();
        });
    }



    public function authorizeStandaloneCharge(mint\IStandaloneChargeRequest $charge): mint\IChargeResult {
        $request = $this->_mediator->newChargeRequest(
            $charge->getAmount(),
            $charge->getCard(),
            $charge->getDescription(),
            $charge->getEmailAddress()
        );

        $request->shouldCapture(false);

        return $this->_submitCharge(function() use($request) {
            $charge = $request->submit();
            return $charge->getId();
        });
    }

    public function captureCharge(mint\IChargeCapture $charge) {
        return $this->_submitCharge(function() use($charge) {
            $result = $this->_mediator->captureCharge($charge->getId());
            return $result->getId();
        });
    }

    protected function _submitCharge(Callable $handler) {
        $result = new mint\charge\Result();

        try {
            $chargeId = $handler();
        } catch(spur\payment\stripe\ECard $e) {
            $data = $e->getData();

            $result->isSuccessful(false);
            $result->setMessage($e->getMessage());
            $result->setChargeId($data['charge'] ?? null);

            switch($data['code'] ?? null) {
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
            $result->setChargeId($data['charge'] ?? null);

            if($e instanceof spur\payment\stripe\ETransport) {
                $result->setMessage('Unable to process your card details at this time');
            } else {
                $result->setMessage($e->getMessage());
            }

            return $result;
        }

        $result->setChargeId($chargeId);
        $result->isSuccessful(true);
        $result->isCardAccepted(true);

        return $result;
    }


    public function refundCharge(mint\IChargeRefund $refund) {
        return $this->_submitCharge(function() use($refund) {
            $result = $this->_mediator->captureCharge($refund->getId(), $refund->getAmount());
            return $result->getId();
        });
    }


// Cache
    protected function _getCacheKeyPrefix() {
        return $this->_mediator->getApiKey().'-';
    }
}


class Stripe_Cache extends core\cache\Base {
    const CACHE_ID = 'payment/stripe';
}
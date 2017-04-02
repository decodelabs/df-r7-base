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

    use mint\TCaptureProviderGateway;
    use mint\TRefundProviderGateway;

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
    public function submitStandaloneCharge(mint\IStandaloneChargeRequest $charge): string {
        return $this->_execute(function() use($charge) {
            return $this->_mediator->newChargeRequest(
                    $charge->getAmount(),
                    $charge->getCard(),
                    $charge->getDescription(),
                    $charge->getEmailAddress()
                )
                ->submit()
                ->getId();
        }, 'ECharge');
    }

    public function authorizeStandaloneCharge(mint\IStandaloneChargeRequest $charge): string {
        return $this->_execute(function() use($charge) {
            return $this->_mediator->newChargeRequest(
                    $charge->getAmount(),
                    $charge->getCard(),
                    $charge->getDescription(),
                    $charge->getEmailAddress()
                )
                ->shouldCapture(false)
                ->submit()
                ->getId();
        }, 'ECharge');
    }

    public function captureCharge(mint\IChargeCapture $charge): string {
        return $this->_execute(function() use($charge) {
            return $this->_mediator->captureCharge($charge->getId())
                ->getId();
        }, 'ECharge,ECapture');
    }


// Refund
    public function refundCharge(mint\IChargeRefund $refund): string {
        return $this->_execute(function() use($refund) {
            return $this->_mediator->captureCharge(
                    $refund->getId(), $refund->getAmount()
                )
                ->getId();
        }, 'ECharge,ERefund');
    }


// Cache
    protected function _getCacheKeyPrefix() {
        return $this->_mediator->getApiKey().'-';
    }

    protected function _execute(callable $func, string $eType=null) {
        try {
            return $func();
        } catch(spur\payment\stripe\EApi $e) {
            $types = ['EApi'];

            if(!empty($eType)) {
                $types[] = $eType;
            }

            if($e instanceof spur\payment\stripe\ENotFound) {
                $types[] = 'ENotFound';
            }

            if($e instanceof spur\payment\stripe\ETransport) {
                $types[] = 'ETransport';
            }

            if($e instanceof spur\payment\stripe\ECard) {
                $types[] = 'ECard';

                switch($data['code'] ?? null) {
                    case 'invalid_number':
                    case 'incorrect_number':
                        $types[] = 'ECardNumber';
                        break;

                    case 'invalid_expiry_month':
                    case 'invalid_expiry_year':
                    case 'expired_card':
                        $types[] = 'ECardExpiry';
                        break;

                    case 'invalid_cvc':
                    case 'incorrect_cvc':
                        $types[] = 'ECardCvc';
                        break;

                    case 'invalid_swipe_data':
                        // hmm
                        break;

                    case 'incorrect_zip':
                        $types[] = 'ECardAddress';
                        break;

                    case 'card_declined':
                        // meh, already covered
                        break;

                    case 'missing':
                        $types[] = 'ENotFound';
                        $types[] = 'ECardMissing';
                        break;

                    case 'processing_error':
                        break;
                }
            }

            throw core\Error::{implode(',', array_unique($types))}([
                'message' => $e->getMessage(),
                'previous' => $e
            ]);
        }
    }
}


class Stripe_Cache extends core\cache\Base {
    const CACHE_ID = 'payment/stripe';
}
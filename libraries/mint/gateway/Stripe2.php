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

class Stripe2 extends Base implements
    mint\ICaptureProviderGateway//,
    //mint\IRefundProviderGateway,
    //mint\ICustomerTrackingGateway
     {

    protected $_mediator;

    protected function __construct(core\collection\ITree $settings) {
        if(!$settings->has('apiKey')) {
            throw core\Error::{'ESetup'}(
                'Stripe API key not set in config'
            );
        }

        $this->_mediator = new spur\payment\stripe2\Mediator($settings['apiKey']);
        parent::__construct($settings);
    }


// Currency
    public function getSupportedCurrencies(): array {
        $cache = Stripe2_Cache::getInstance();
        $key = $this->_getCacheKeyPrefix().'supportedCurrencies';
        $output = $cache->get($key);

        if(empty($output)) {
            $account = $this->_mediator->fetchAccountDetails();
            $spec = $this->_mediator->fetchCountrySpec($account['country']);
            $output = $spec->supported_payment_currencies->toArray();

            $output = array_map('strtoupper', $output);
            $cache->set($key, $output);
        }

        return $output;
    }


// Charges
    public function submitStandaloneCharge(mint\IStandaloneChargeRequest $charge): mint\IChargeResult {
        $request = $this->_mediator->newChargeCreateRequest(
                $charge->getAmount(),
                $charge->getDescription()
            )
            ->setCard($charge->getCard())
            ->setEmailAddress($charge->getEmailAddress());

        return $this->_submitCharge(function() use($request) {
            $charge = $this->_mediator->createCharge($request);
            return $charge['id'];
        });
    }

    public function submitCustomerCharge(mint\ICustomerChargeRequest $charge): mint\IChargeResult {
        $request = $this->_mediator->newChargeCreateRequest(
                $charge->getAmount(),
                $charge->getDescription()
            )
            ->setCard($charge->getCard())
            ->setCustomerId($charge->getCustomerId())
            ->setEmailAddress($charge->getEmailAddress());

        return $this->_submitCharge(function() use($request) {
            $charge = $this->_mediator->createCharge($request);
            return $charge['id'];
        });
    }



    public function authorizeStandaloneCharge(mint\IStandaloneChargeRequest $charge): mint\IChargeResult {
        $request = $this->_mediator->newChargeCreateRequest(
                $charge->getAmount(),
                $charge->getDescription()
            )
            ->setCard($charge->getCard())
            ->setEmailAddress($charge->getEmailAddress())
            ->shouldCapture(false);

        return $this->_submitCharge(function() use($request) {
            $charge = $this->_mediator->createCharge($request);
            return $charge['id'];
        });
    }

    public function captureCharge(mint\IChargeCapture $charge) {
        $request = $this->_mediator->newChargeCaptureRequest($charge->getId());

        return $this->_submitCharge(function() use($request) {
            $charge = $this->_mediator->captureCharge($request);
            return $charge['id'];
        });
    }

    protected function _submitCharge(Callable $handler) {
        $result = new mint\charge\Result();

        try {
            $chargeId = $handler();
        } catch(spur\payment\stripe2\ECard $e) {
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
        } catch(spur\payment\stripe2\EApi $e) {
            core\logException($e);

            $result->isSuccessful(false);
            $result->isApiFailure(true);
            $result->setMessage($e->getMessage());
            $result->setChargeId($data['charge'] ?? null);

            return $result;
        }

        $result->setChargeId($chargeId);
        $result->isSuccessful(true);
        $result->isCardAccepted(true);

        return $result;
    }


/*
    public function refundCharge(mint\IChargeRefund $refund) {
        return $this->_submitCharge(function() use($refund) {
            $result = $this->_mediator->captureCharge($refund->getId(), $refund->getAmount());
            return $result->getId();
        });
    }
*/


// Customers
/*
    public function addCustomer(string $email=null, string $description=null, mint\ICreditCard $card=null): string {
        $request = $this->_mediator->newCustomerRequest(
            $email, $card, $description
        );

        try {
            $customer = $request->submit();
        } catch(spur\payment\stripe\EApi $e) {
            core\logException($e);

            throw core\Error::{'mint/ECustomer,mint/EApi'}([
                'message' => $e->getMessage(),
                'previous' => $e
            ]);
        }

        return $customer->getId();
    }

    public function updateCustomer(mint\ICustomer $customer) {

    }

    public function deleteCustomer(string $customerId) {
        try {
            $this->_mediator->deleteCustomer($customerId);
        } catch(spur\payment\stripe\EApi $e) {
            core\logException($e);

            throw core\Error::{'mint/ECustomer,mint/EApi'}([
                'message' => $e->getMessage(),
                'previous' => $e
            ]);
        }

        return $this;
    }
*/


// Cache
    protected function _getCacheKeyPrefix() {
        return $this->_mediator->getApiKey().'-';
    }
}


class Stripe2_Cache extends core\cache\Base {
    const CACHE_ID = 'payment/stripe2';
}
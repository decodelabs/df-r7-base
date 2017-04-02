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
    mint\ICaptureProviderGateway,
    mint\IRefundProviderGateway,
    mint\ICustomerTrackingGateway,
    mint\ICustomerTrackingCaptureProviderGateway,
    //mint\ICardStoreGateway,
    mint\ISubscriptionProviderGateway,
    mint\ISubscriptionPlanControllerGateway
     {

    use mint\TCaptureProviderGateway;
    use mint\TRefundProviderGateway;
    use mint\TCustomerTrackingGateway;
    use mint\TSubscriptionPlanControllerGateway;

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


// Direct charge
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


// Authorize / capture
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

    public function authorizeCustomerCharge(mint\ICustomerChargeRequest $charge): mint\IChargeResult {
        $request = $this->_mediator->newChargeCreateRequest(
                $charge->getAmount(),
                $charge->getDescription()
            )
            ->setCard($charge->getCard())
            ->setCustomerId($charge->getCustomerId())
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



// Refund
    public function refundCharge(mint\IChargeRefund $refund) {
        $request = $this->_mediator->newRefundCreateRequest($refund->getId())
            ->setAmount($refund->getAmount());

        return $this->_submitCharge(function() use($request) {
            $refund = $this->_mediator->createRefund($request);
            return $refund['id'];
        });
    }


// Charge handler
    protected function _submitCharge(callable $handler) {
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





// Customers
    public function fetchCustomer(string $customerId): mint\ICustomer {
        $data = $this->_execute(function() use($customerId) {
            return $this->_mediator->fetchCustomer($customerId);
        }, 'ECustomer');

        return $this->_wrapCustomer($data);
    }

    public function addCustomer(mint\ICustomer $customer): mint\ICustomer {
        $data = $this->_execute(function() use($customer) {
            $metadata = ($id = $customer->getUserId()) ? ['userId' => (string)$id] : null;

            return $this->_mediator->createCustomer(
                $this->_mediator->newCustomerCreateRequest(
                        $customer->getEmailAddress(),
                        $customer->getDescription()
                    )
                    ->setCard($customer->getCard())
                    ->setMetadata($metadata)
                    // shipping
            );
        }, 'ECustomer');

        return $this->_wrapCustomer($data);
    }

    public function updateCustomer(mint\ICustomer $customer): mint\ICustomer {
        if($customer->getId() === null) {
            throw core\Error::EArgument([
                'message' => 'Customer Id not set',
                'data' => $customer
            ]);
        }

        $data = $this->_execute(function() use($customer) {
            $metadata = ($id = $customer->getUserId()) ? ['userId' => (string)$id] : null;

            return $this->_mediator->updateCustomer(
                $this->_mediator->newCustomerUpdateRequest($customer->getId())
                    ->setEmailAddress($customer->getEmailAddress())
                    ->setDescription($customer->getDescription())
                    ->setCard($customer->getCard())
                    ->setMetadata($metadata)
                    // shipping
            );
        }, 'ECustomer');

        return $this->_wrapCustomer($data);
    }

    public function deleteCustomer(string $customerId) {
        $this->_execute(function() use($customerId) {
            $this->_mediator->deleteCustomer($customerId);
        }, 'ECustomer');

        return $this;
    }

    protected function _wrapCustomer(spur\payment\stripe2\IDataObject $customer): mint\ICustomer {
        return (new mint\subscription\Customer(
                $customer['id'],
                $customer['email'],
                $customer['description']
            ))
            ->isDelinquent((bool)$customer['delinquent'])
            ->setUserId($customer->metadata['userId']);
    }


// Cards



// Plans
    public function getPlans(): array {
        $cache = Stripe2_Cache::getInstance();
        $key = $this->_getCacheKeyPrefix().'plans';
        $output = $cache->get($key);

        if($output === null) {
            $data = $this->_execute(function() {
                return $this->_mediator->fetchPlans(
                    $this->_mediator->newPlanFilter()
                        ->setLimit(100)
                );
            }, 'EPlan');

            $output = [];

            foreach($data as $plan) {
                $output[] = $this->_wrapPlan($plan);
            }

            $cache->set($key, $output);
        }

        return $output;
    }


    public function addPlan(mint\IPlan $plan) {
        $data = $this->_execute(function() use($plan) {
            return $this->_mediator->createPlan(
                $this->_mediator->newPlanCreateRequest(
                        $plan->getId(),
                        $plan->getName(),
                        $plan->getAmount(),
                        $plan->getInterval()
                    )
                    ->setIntervalCount($plan->getIntervalCount())
                    ->setStatementDescriptor($plan->getStatementDescriptor())
                    ->setTrialDays($plan->getTrialDays())
            );
        }, 'EPlan');

        $this->clearPlanCache();
        return $this->_wrapPlan($data);
    }

    public function updatePlan(mint\IPlan $plan) {
        $data = $this->_execute(function() use($plan) {
            return $this->_mediator->updatePlan(
                $this->_mediator->newPlanUpdateRequest($plan->getId())
                    ->setName($plan->getName())
                    ->setStatementDescriptor($plan->getStatementDescriptor())
                    ->setTrialDays($plan->getTrialDays())
            );
        }, 'EPlan');

        $this->clearPlanCache();
        return $this->_wrapPlan($data);
    }

    public function deletePlan(string $planId) {
        $this->_execute(function() use($planId) {
            $this->_mediator->deletePlan($planId);
        }, 'mint/EPlan');

        $this->clearPlanCache();
        return $this;
    }

    protected function _wrapPlan(spur\payment\stripe2\IDataObject $plan): mint\IPlan {
        return (new mint\subscription\Plan(
                $plan['id'],
                $plan['name'],
                $plan['amount'],
                $plan['interval']
            ))
            ->setIntervalCount($plan['interval_count'])
            ->setStatementDescriptor($plan['statement_descriptor'])
            ->setTrialDays($plan['trial_period_days']);
    }

    public function clearPlanCache() {
        $cache = Stripe2_Cache::getInstance();
        $key = $this->_getCacheKeyPrefix().'plans';
        $cache->remove($key);
        return $this;
    }



// Helpers
    protected function _getCacheKeyPrefix() {
        return $this->_mediator->getApiKey().'-';
    }

    protected function _execute(callable $func, string $eType=null) {
        try {
            return $func();
        } catch(spur\payment\stripe2\EApi $e) {
            $type = 'EApi,'.$eType;

            if($e instanceof core\ENotFound) {
                $type .= ',ENotFound';
            }

            if($e instanceof core\ETransport) {
                $type .= ',ETransport';
            }

            throw core\Error::{$type}([
                'message' => $e->getMessage(),
                'previous' => $e
            ]);
        }
    }
}


class Stripe2_Cache extends core\cache\Base {
    const CACHE_ID = 'payment/stripe2';
}
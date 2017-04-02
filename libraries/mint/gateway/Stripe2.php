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
    use mint\TSubscriptionProviderGateway;
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
    public function submitStandaloneCharge(mint\IStandaloneChargeRequest $charge): string {
        return $this->_execute(function() use($charge) {
            return $this->_mediator->createCharge(
                $this->_mediator->newChargeCreateRequest(
                        $charge->getAmount(),
                        $charge->getDescription()
                    )
                    ->setCard($charge->getCard())
                    ->setEmailAddress($charge->getEmailAddress())
            )['id'];
        }, 'ECharge');
    }

    public function submitCustomerCharge(mint\ICustomerChargeRequest $charge): string {
        return $this->_execute(function() use($charge) {
            return $this->_mediator->createCharge(
                $this->_mediator->newChargeCreateRequest(
                        $charge->getAmount(),
                        $charge->getDescription()
                    )
                    ->setCard($charge->getCard())
                    ->setCustomerId($charge->getCustomerId())
                    ->setEmailAddress($charge->getEmailAddress())
            )['id'];
        }, 'ECharge');
    }


// Authorize / capture
    public function authorizeStandaloneCharge(mint\IStandaloneChargeRequest $charge): string {
        return $this->_execute(function() use($charge) {
            return $this->_mediator->createCharge(
                $this->_mediator->newChargeCreateRequest(
                        $charge->getAmount(),
                        $charge->getDescription()
                    )
                    ->setCard($charge->getCard())
                    ->setEmailAddress($charge->getEmailAddress())
                    ->shouldCapture(false)
            )['id'];
        }, 'ECharge');
    }

    public function authorizeCustomerCharge(mint\ICustomerChargeRequest $charge): string {
        return $this->_execute(function() use($charge) {
            return $this->_mediator->createCharge(
                $this->_mediator->newChargeCreateRequest(
                        $charge->getAmount(),
                        $charge->getDescription()
                    )
                    ->setCard($charge->getCard())
                    ->setCustomerId($charge->getCustomerId())
                    ->setEmailAddress($charge->getEmailAddress())
                    ->shouldCapture(false)
            )['id'];
        }, 'ECharge');
    }

    public function captureCharge(mint\IChargeCapture $charge): string {
        return $this->_execute(function() use($charge) {
            return $this->_mediator->captureCharge(
                $this->_mediator->newChargeCaptureRequest($charge->getId())
            )['id'];
        }, 'ECharge,ECapture');
    }



// Refund
    public function refundCharge(mint\IChargeRefund $refund): string {
        return $this->_execute(function() use($refund) {
            return $this->_mediator->createRefund(
                $this->_mediator->newRefundCreateRequest($refund->getId())
                    ->setAmount($refund->getAmount())
            )['charge'];
        }, 'ECharge,ERefund');
    }





// Customers
    public function fetchCustomer(string $customerId): mint\ICustomer {
        return $this->_execute(function() use($customerId) {
            $data = $this->_mediator->fetchCustomer($customerId);
            return $this->_wrapCustomer($data);
        }, 'ECustomer');
    }

    public function addCustomer(mint\ICustomer $customer): mint\ICustomer {
        return $this->_execute(function() use($customer) {
            $metadata = ($id = $customer->getUserId()) ? ['userId' => (string)$id] : null;

            $data = $this->_mediator->createCustomer(
                $this->_mediator->newCustomerCreateRequest(
                        $customer->getEmailAddress(),
                        $customer->getDescription()
                    )
                    ->setCard($customer->getCard())
                    ->setMetadata($metadata)
                    // shipping
            );

            return $this->_wrapCustomer($data);
        }, 'ECustomer');
    }

    public function updateCustomer(mint\ICustomer $customer): mint\ICustomer {
        if($customer->getId() === null) {
            throw core\Error::EArgument([
                'message' => 'Customer Id not set',
                'data' => $customer
            ]);
        }

        return $this->_execute(function() use($customer) {
            $metadata = ($id = $customer->getUserId()) ? ['userId' => (string)$id] : null;

            $data = $this->_mediator->updateCustomer(
                $this->_mediator->newCustomerUpdateRequest($customer->getId())
                    ->setEmailAddress($customer->getEmailAddress())
                    ->setDescription($customer->getDescription())
                    ->setCard($customer->getCard())
                    ->setMetadata($metadata)
                    // shipping
            );

            return $this->_wrapCustomer($data);
        }, 'ECustomer');
    }

    public function deleteCustomer(string $customerId) {
        $this->_execute(function() use($customerId) {
            $this->_mediator->deleteCustomer($customerId);
        }, 'ECustomer');

        return $this;
    }

    protected function _wrapCustomer(spur\payment\stripe2\IDataObject $customer): mint\ICustomer {
        return (new mint\Customer(
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


    public function addPlan(mint\IPlan $plan): mint\IPlan {
        return $this->_execute(function() use($plan) {
            $data = $this->_mediator->createPlan(
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

            $this->clearPlanCache();
            return $this->_wrapPlan($data);
        }, 'EPlan');
    }

    public function updatePlan(mint\IPlan $plan): mint\IPlan {
        return $this->_execute(function() use($plan) {
            $data = $this->_mediator->updatePlan(
                $this->_mediator->newPlanUpdateRequest($plan->getId())
                    ->setName($plan->getName())
                    ->setStatementDescriptor($plan->getStatementDescriptor())
                    ->setTrialDays($plan->getTrialDays())
            );

            $this->clearPlanCache();
            return $this->_wrapPlan($data);
        }, 'EPlan');
    }

    public function deletePlan(string $planId) {
        $this->_execute(function() use($planId) {
            $this->_mediator->deletePlan($planId);
            $this->clearPlanCache();
        }, 'mint/EPlan');

        return $this;
    }

    protected function _wrapPlan(spur\payment\stripe2\IDataObject $plan): mint\IPlan {
        return (new mint\Plan(
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


// Subscriptions
    public function subscribeCustomer(mint\ISubscription $subscription): mint\ISubscription {
        core\stub($subscription);
    }

    public function updateSubscription(mint\ISubscription $subscription): mint\ISubscription {
        core\stub($subscription);
    }

    public function endSubscriptionTrial(int $inDays=null) {
        core\stub($inDays);
    }

    public function cancelSubscription(string $subscriptionId, bool $atPeriodEnd=false): mint\ISubscription {
        core\stub($subscriptionId, $atPeriodEnd);
    }



// Helpers
    protected function _getCacheKeyPrefix() {
        return $this->_mediator->getApiKey().'-';
    }

    protected function _execute(callable $func, string $eType=null) {
        try {
            return $func();
        } catch(spur\payment\stripe2\EApi $e) {
            $types = ['EApi'];

            if(!empty($eType)) {
                $types[] = $eType;
            }

            if($e instanceof spur\payment\stripe2\ENotFound) {
                $types[] = 'ENotFound';
            }

            if($e instanceof spur\payment\stripe2\ETransport) {
                $types[] = 'ETransport';
            }

            if($e instanceof spur\payment\stripe2\ECard) {
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


class Stripe2_Cache extends core\cache\Base {
    const CACHE_ID = 'payment/stripe2';
}
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

use Stripe as StripePHP;
use DecodeLabs\Exceptional;

class Stripe extends Base implements
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

    protected $_apiKey;
    protected $_publicKey;

    protected function __construct(core\collection\ITree $settings)
    {
        $key = null;

        if ($settings->has('apiKey')) {
            $key = $settings['apiKey'];
        } else {
            $key = $settings['testing'] ?
                $settings['testApiKey'] :
                $settings['liveApiKey'];
        }

        if (!$key) {
            throw Exceptional::Setup(
                'Stripe API key not set in config'
            );
        }

        $this->_apiKey = $key;


        if ($settings->has('publicKey')) {
            $key = $settings['publicKey'];
        } else {
            $key = $settings['testing'] ?
                $settings['testPublicKey'] :
                $settings['livePublicKey'];
        }

        if (!$key) {
            throw Exceptional::Setup(
                'Stripe API key not set in config'
            );
        }

        $this->_publicKey = $key;
    }

    // Testing
    public function isTesting(): bool
    {
        return false !== stristr($this->_apiKey, '_test_');
    }

    public function getApiKey(): ?string
    {
        return $this->_apiKey;
    }

    public function getPublicKey(): ?string
    {
        return $this->_publicKey;
    }


    // Ips
    public function getApiIps(): ?array
    {
        return $this->_getCachedValue('apiIps', function () {
            try {
                $data = json_decode((string)file_get_contents('https://stripe.com/files/ips/ips_api.json'), true);
                return $data['API'] ?? null;
            } catch (\Exception $e) {
                return null;
            }
        }, 'Ip');
    }

    public function getWebhookIps(): ?array
    {
        return $this->_getCachedValue('webhookIps', function () {
            try {
                $data = json_decode((string)file_get_contents('https://stripe.com/files/ips/ips_webhooks.json'), true);
                return $data['WEBHOOKS'] ?? null;
            } catch (\Exception $e) {
                return null;
            }
        }, 'Ip');
    }



    // Currency
    public function getSupportedCurrencies(): array
    {
        return $this->_getCachedValue('supportedCurrencies', function () {
            $account = StripePHP\Account::retrieve(null, [
                'api_key' => $this->_apiKey
            ]);

            $spec = StripePHP\CountrySpec::retrieve($account['country'], [
                'api_key' => $this->_apiKey
            ]);

            return array_map('strtoupper', $spec['supported_payment_currencies']);
        }, 'Currency');
    }



    // Direct charge
    public function submitStandaloneCharge(mint\IChargeRequest $charge): string
    {
        return $this->_execute(function () use ($charge) {
            return StripePHP\Charge::create([
                'amount' => $charge->getAmount()->getIntegerAmount(),
                'currency' => $charge->getAmount()->getCode(),
                'description' => $charge->getDescription() ?? '',
                'receipt_email' => $charge->getEmailAddress(),
                'source' => $this->_prepareSource($charge->getCard()),
                'metadata' => [
                    'email' => $charge->getEmailAddress()
                ]
            ], [
                'api_key' => $this->_apiKey
            ])['id'];
        }, 'Charge');
    }

    public function submitCustomerCharge(mint\ICustomerChargeRequest $charge): string
    {
        return $this->_execute(function () use ($charge) {
            return StripePHP\Charge::create([
                'amount' => $charge->getAmount()->getIntegerAmount(),
                'currency' => $charge->getAmount()->getCode(),
                'description' => $charge->getDescription() ?? '',
                'receipt_email' => $charge->getEmailAddress(),
                'source' => $this->_prepareSource($charge->getCard()),
                'customer' => $charge->getCustomerId(),
                'metadata' => [
                    'email' => $charge->getEmailAddress()
                ]
            ], [
                'api_key' => $this->_apiKey
            ])['id'];
        }, 'Charge');
    }



    // Authorize / capture
    public function authorizeStandaloneCharge(mint\IChargeRequest $charge): string
    {
        return $this->_execute(function () use ($charge) {
            return StripePHP\Charge::create([
                'amount' => $charge->getAmount()->getIntegerAmount(),
                'currency' => $charge->getAmount()->getCode(),
                'description' => $charge->getDescription() ?? '',
                'receipt_email' => $charge->getEmailAddress(),
                'source' => $this->_prepareSource($charge->getCard()),
                'capture' => false,
                'metadata' => [
                    'email' => $charge->getEmailAddress()
                ]
            ], [
                'api_key' => $this->_apiKey
            ])['id'];
        }, 'Charge');
    }

    public function authorizeCustomerCharge(mint\ICustomerChargeRequest $charge): string
    {
        return $this->_execute(function () use ($charge) {
            return StripePHP\Charge::create([
                'amount' => $charge->getAmount()->getIntegerAmount(),
                'currency' => $charge->getAmount()->getCode(),
                'description' => $charge->getDescription() ?? '',
                'receipt_email' => $charge->getEmailAddress(),
                'source' => $this->_prepareSource($charge->getCard()),
                'customer' => $charge->getCustomerId(),
                'capture' => false,
                'metadata' => [
                    'email' => $charge->getEmailAddress()
                ]
            ], [
                'api_key' => $this->_apiKey
            ])['id'];
        }, 'Charge');
    }

    public function captureCharge(mint\IChargeCapture $charge): string
    {
        return $this->_execute(function () use ($charge) {
            $charge = StripePHP\Charge::retrieve($charge->getId(), [
                'api_key' => $this->_apiKey
            ]);

            $charge->capture();
            return $charge['id'];
        }, 'Charge,Capture');
    }



    // Refund
    public function refundCharge(mint\IChargeRefund $refund): string
    {
        return $this->_execute(function () use ($refund) {
            if (!$amount = $refund->getAmount()) {
                throw Exceptional::UnexpectedValue(
                    'Refund amount has not been set', null, $refund
                );
            }

            return StripePHP\Refund::create([
                'charge' => $refund->getId(),
                'amount' => $amount->getIntegerAmount()
            ], [
                'api_key' => $this->_apiKey
            ])['charge'];
        }, 'Charge,Refund');
    }



    // Customers
    public function fetchCustomer(string $customerId): mint\ICustomer
    {
        return $this->_execute(function () use ($customerId) {
            $customer = StripePHP\Customer::retrieve($customerId, [
                'api_key' => $this->_apiKey
            ]);

            if ($customer['deleted']) {
                throw Exceptional::{'Api,Customer,NotFound'}([
                    'message' => 'Customer has been deleted',
                    'data' => $customer
                ]);
            }

            return $this->_wrapCustomer($customer);
        }, 'Customer');
    }

    public function addCustomer(mint\ICustomer $customer): mint\ICustomer
    {
        return $this->_execute(function () use ($customer) {
            $customer = StripePHP\Customer::create([
                'email' => $customer->getEmailAddress(),
                'description' => $customer->getDescription(),
                'source' => $this->_prepareSource($customer->getCard()),
                'metadata' => [
                    'localId' => $customer->getLocalId(),
                    'userId' => $customer->getUserId()
                ]
            ], [
                'api_key' => $this->_apiKey
            ]);

            return $this->_wrapCustomer($customer);
        }, 'Customer');
    }

    public function updateCustomer(mint\ICustomer $customer): mint\ICustomer
    {
        if ($customer->getId() === null) {
            throw Exceptional::InvalidArgument([
                'message' => 'Customer Id not set',
                'data' => $customer
            ]);
        }

        return $this->_execute(function () use ($customer) {
            $customer = StripePHP\Customer::update((string)$customer->getId(), [
                'email' => $customer->getEmailAddress(),
                'description' => $customer->getDescription(),
                'source' => $this->_prepareSource($customer->getCard()),
                'metadata' => [
                    'localId' => $customer->getLocalId(),
                    'userId' => $customer->getUserId()
                ]
            ], [
                'api_key' => $this->_apiKey
            ]);

            return $this->_wrapCustomer($customer);
        }, 'Customer');
    }

    public function deleteCustomer(string $customerId)
    {
        $this->_execute(function () use ($customerId) {
            $customer = StripePHP\Customer::retrieve($customerId, [
                'api_key' => $this->_apiKey
            ]);
            $customer->delete();
        }, 'Customer');

        return $this;
    }

    protected function _wrapCustomer(StripePHP\Customer $customer): mint\ICustomer
    {
        $output = (new mint\Customer(
                $customer['id'],
                $customer['email'],
                $customer['description']
            ))
            ->isDelinquent((bool)$customer['delinquent'])
            ->setLocalId($customer->metadata['localId'])
            ->setUserId($customer->metadata['userId']);

        $subs = [];

        foreach ($customer->subscriptions as $subscription) {
            $subs[] = $this->_wrapSubscription($subscription);
        }

        $output->setCachedSubscriptions($subs);
        return $output;
    }




    // Cards



    // Plans
    public function getPlans(): array
    {
        return $this->_getCachedValue('plans', function () {
            $plans = StripePHP\Plan::all([
                'limit' => 100
            ], [
                'api_key' => $this->_apiKey
            ]);

            $output = [];

            foreach ($plans as $plan) {
                $output[] = $this->_wrapPlan($plan);
            }

            return $output;
        }, 'Plan');
    }


    public function addPlan(mint\IPlan $plan): mint\IPlan
    {
        return $this->_execute(function () use ($plan) {
            $plan = StripePHP\Plan::create([
                'id' => $plan->getId(),
                'nickname' => $plan->getName(),
                'amount' => $plan->getAmount()->getIntegerAmount(),
                'currency' => $plan->getAmount()->getCode(),
                'interval' => $plan->getInterval(),
                'interval_count' => $plan->getIntervalCount(),
                'product' => [
                    'name' => $plan->getName(),
                    'type' => 'service',
                    'statement_descriptor' => $plan->getStatementDescriptor()
                ],
                'trial_period_days' => $plan->getTrialDays()
            ], [
                'api_key' => $this->_apiKey
            ]);

            $this->clearPlanCache();
            return $this->_wrapPlan($plan);
        }, 'Plan');
    }

    public function updatePlan(mint\IPlan $plan): mint\IPlan
    {
        return $this->_execute(function () use ($plan) {
            $plan = StripePHP\Plan::update($plan->getId(), [
                'nickname' => $plan->getName(),
                'trial_period_days' => $plan->getTrialDays()
            ], [
                'api_key' => $this->_apiKey
            ]);

            $this->clearPlanCache();
            return $this->_wrapPlan($plan);
        }, 'Plan');
    }

    public function deletePlan(string $planId)
    {
        $this->_execute(function () use ($planId) {
            $plan = StripePHP\Plan::retrieve($planId, ['api_key' => $this->_apiKey]);
            $productId = $plan['product'];
            $plan->delete();
            $product = StripePHP\Product::retrieve($productId, ['api_key' => $this->_apiKey]);
            $product->delete();
            $this->clearPlanCache();
        }, 'Plan');

        return $this;
    }

    protected function _wrapPlan(StripePHP\Plan $plan): mint\IPlan
    {
        return (new mint\Plan(
                $plan['id'],
                $plan['nickname'],
                new mint\Currency($plan['amount'], $plan['currency']),
                $plan['interval']
            ))
            ->setIntervalCount($plan['interval_count'])
            ->setTrialDays($plan['trial_period_days']);
    }

    public function clearPlanCache()
    {
        $cache = Stripe_Cache::getInstance();
        $key = $this->_getCacheKeyPrefix().'plans';
        $cache->remove($key);
        return $this;
    }



    // Subscriptions
    public function fetchSubscription(string $subscriptionId): mint\ISubscription
    {
        return $this->_execute(function () use ($subscriptionId) {
            $subscription = StripePHP\Subscription::retrieve($subscriptionId, [
                'api_key' => $this->_apiKey
            ]);

            return $this->_wrapSubscription($subscription);
        });
    }

    public function getSubscriptionsFor(mint\ICustomer $customer): array
    {
        if ($customer->getId() === null) {
            throw Exceptional::InvalidArgument([
                'message' => 'Customer Id not set',
                'data' => $customer
            ]);
        }

        if (!$customer->hasSubscriptionCache()) {
            $subscriptions = StripePHP\Subscription::all([
                'limit' => 100,
                'customer' => $customer->getId()
            ], [
                'api_key' => $this->_apiKey
            ]);

            $subs = [];

            foreach ($subscriptions as $subscription) {
                $subs[] = $this->_wrapSubscription($subscription);
            }

            $customer->setCachedSubscriptions($subs);
        }

        if (null === ($output = $customer->getCachedSubscriptions())) {
            return [];
        }

        return $output;
    }

    public function subscribeCustomer(mint\ISubscription $subscription): mint\ISubscription
    {
        return $this->_execute(function () use ($subscription) {
            $subscription = StripePHP\Subscription::create([
                'customer' => $subscription->getCustomerId(),
                'items' => [[
                    'plan' => $subscription->getPlanId()
                ]],
                'trial_end' => $this->_normalizeDate($subscription->getTrialEnd()),
                'metadata' => [
                    'localId' => $subscription->getLocalId()
                ]
            ], [
                'api_key' => $this->_apiKey
            ]);

            return $this->_wrapSubscription($subscription);
        }, 'Subscription');
    }

    public function updateSubscription(mint\ISubscription $subscription): mint\ISubscription
    {
        return $this->_execute(function () use ($subscription) {
            $subscription = StripePHP\Subscription::update((string)$subscription->getId(), [
                'items' => [[
                    'plan' => $subscription->getPlanId()
                ]],
                'trial_end' => $this->_normalizeDate($subscription->getTrialEnd()),
                'metadata' => [
                    'localId' => $subscription->getLocalId()
                ]
            ], [
                'api_key' => $this->_apiKey
            ]);

            return $this->_wrapSubscription($subscription);
        }, 'Subscription');
    }

    public function endSubscriptionTrial(string $subscriptionId, int $inDays=null): mint\ISubscription
    {
        return $this->_execute(function () use ($subscriptionId, $inDays) {
            if ($inDays === null || $inDays <= 0) {
                $date = 'now';
            } else {
                $date = $this->_normalizeDate(core\time\Date::factory('+'.$inDays.' days'));
            }

            $subscription = StripePHP\Subscription::update($subscriptionId, [
                'trial_end' => $date,
            ], [
                'api_key' => $this->_apiKey
            ]);

            return $this->_wrapSubscription($subscription);
        });
    }

    public function cancelSubscription(string $subscriptionId, bool $atPeriodEnd=false): mint\ISubscription
    {
        return $this->_execute(function () use ($subscriptionId, $atPeriodEnd) {
            $subscription = StripePHP\Subscription::retrieve($subscriptionId, [
                'api_key' => $this->_apiKey
            ]);

            $subscription->cancel([
                'at_period_end' => $atPeriodEnd ? 'true' : 'false'
            ]);

            return $this->_wrapSubscription($subscription);
        });
    }

    protected function _wrapSubscription(StripePHP\Subscription $subscription): mint\ISubscription
    {
        return (new mint\Subscription(
                $subscription['id'],
                $subscription['customer'],
                $subscription->plan['id']
            ))
            ->setLocalId($subscription->metadata['localId'])
            ->setTrialStart($subscription['trial_start'])
            ->setTrialEnd($subscription['trial_end'])
            ->setPeriodStart($subscription['current_period_start'])
            ->setPeriodEnd($subscription['current_period_end'])
            ->setStartDate($subscription['start'])
            ->setEndDate($subscription['ended_at'])
            ->setCancelDate($subscription['canceled_at'], $subscription['cancel_at_period_end']);
    }



    // Helpers
    protected function _prepareSource(?mint\ICreditCardReference $card)
    {
        if ($card instanceof mint\ICreditCard) {
            $source = $this->_cardToArray($card);
            $source['object'] = 'card';
            return $source;
        } elseif ($card instanceof mint\ICreditCardToken) {
            return $card->getToken();
        } else {
            return null;
        }
    }

    protected function _cardToArray(mint\ICreditCard $card): array
    {
        $output = [];

        $output['number'] = $card->getNumber();
        $output['exp_month'] = $card->getExpiryMonth();
        $output['exp_year'] = $card->getExpiryYear();

        if (null !== ($cvc = $card->getCvc())) {
            $output['cvc'] = $cvc;
        }

        if (null !== ($name = $card->getName())) {
            $output['name'] = $name;
        }

        if ($address = $card->getBillingAddress()) {
            $output['address_line1'] = $address->getMainStreetLine();
            $output['address_line2'] = $address->getExtendedStreetLine();
            $output['address_city'] = $address->getLocality();
            $output['address_state'] = $address->getRegion();
            $output['address_zip'] = $address->getPostalCode();
            $output['address_country'] = $address->getCountryName();
        }

        return $output;
    }

    protected function _normalizeDate(?core\time\IDate $date): ?int
    {
        if (!$date) {
            return null;
        }

        return $date->toTimestamp();
    }

    protected function _getCacheKeyPrefix()
    {
        return $this->_apiKey.'-';
    }

    protected function _getCachedValue(string $key, callable $generator, string $eType=null)
    {
        $cache = Stripe_Cache::getInstance();
        $key = $this->_getCacheKeyPrefix().$key;
        $output = $cache->get($key);

        if ($output === null) {
            $cache->set($key, $output = $this->_execute($generator, $eType));
        }

        return $output;
    }

    protected function _execute(callable $func, string $eType=null)
    {
        try {
            return $func();
        } catch (StripePHP\Error\Base $e) {
            $data = $e->getJsonBody()['error'] ?? [];
            $types = ['Api'];

            if (!empty($eType)) {
                $types[] = $eType;
            }

            if ($e->getHttpStatus() == 404) {
                $types[] = 'NotFound';
            }

            if ($e instanceof StripePHP\Error\Api ||
                $e instanceof StripePHP\Error\ApiConnection ||
                $e instanceof StripePHP\Error\Authentication ||
                $e instanceof StripePHP\Error\InvalidRequest) {
                $types[] = 'Implementation';
            } elseif ($e instanceof StripePHP\Error\Card) {
                $types[] = 'Card';

                switch ($data['code'] ?? null) {
                    case 'invalid_number':
                    case 'incorrect_number':
                        $types[] = 'CardNumber';
                        break;

                    case 'invalid_expiry_month':
                    case 'invalid_expiry_year':
                    case 'expired_card':
                        $types[] = 'CardExpiry';
                        break;

                    case 'invalid_cvc':
                    case 'incorrect_cvc':
                        $types[] = 'CardCvc';
                        break;

                    case 'invalid_swipe_data':
                        // hmm
                        break;

                    case 'incorrect_zip':
                        $types[] = 'CardAddress';
                        break;

                    case 'card_declined':
                        // meh, already covered
                        break;

                    case 'missing':
                        $types[] = 'NotFound';
                        $types[] = 'CardMissing';
                        break;

                    case 'processing_error':
                        break;
                }
            } elseif ($e instanceof StripePHP\Error\RateLimit) {
                $types[] = 'RateLimit';
            }

            throw Exceptional::{implode(',', array_unique($types))}([
                'message' => $e->getMessage(),
                'previous' => $e,
                'data' => $data
            ]);
        }
    }
}

class Stripe_Cache extends core\cache\Base
{
}

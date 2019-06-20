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

use Stripe;

class StripeApi extends Base implements
    mint\ICaptureProviderGateway,
    mint\IRefundProviderGateway,
    mint\ICustomerTrackingGateway,
    mint\ICustomerTrackingCaptureProviderGateway //,
    //mint\ICardStoreGateway,
    //mint\ISubscriptionProviderGateway,
    //mint\ISubscriptionPlanControllerGateway
{
    use mint\TCaptureProviderGateway;
    use mint\TRefundProviderGateway;
    use mint\TCustomerTrackingGateway;
    //use mint\TSubscriptionProviderGateway;
    //use mint\TSubscriptionPlanControllerGateway;

    protected $_apiKey;

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
            throw core\Error::{'ESetup'}(
                'Stripe API key not set in config'
            );
        }

        $this->_apiKey = $key;
        parent::__construct($settings);
    }

    // Testing
    public function isTesting(): bool
    {
        return stristr($this->_apiKey, '_test_');
    }



    // Ips
    public function getApiIps(): ?array
    {
        return $this->_getCachedValue('apiIps', function () {
            try {
                $data = json_decode(file_get_contents('https://stripe.com/files/ips/ips_api.json'), true);
                return $data['API'] ?? null;
            } catch (\Exception $e) {
                return null;
            }
        }, 'EIp');
    }

    public function getWebhookIps(): ?array
    {
        return $this->_getCachedValue('webhookIps', function () {
            try {
                $data = json_decode(file_get_contents('https://stripe.com/files/ips/ips_webhooks.json'), true);
                return $data['WEBHOOKS'] ?? null;
            } catch (\Exception $e) {
                return null;
            }
        }, 'EIp');
    }



    // Currency
    public function getSupportedCurrencies(): array
    {
        return $this->_getCachedValue('supportedCurrencies', function () {
            $account = Stripe\Account::retrieve(null, ['api_key' => $this->_apiKey]);
            $spec = Stripe\CountrySpec::retrieve($account['country'], ['api_key' => $this->_apiKey]);
            return array_map('strtoupper', $spec['supported_payment_currencies']);
        }, 'ECurrency');
    }



    // Direct charge
    public function submitStandaloneCharge(mint\IStandaloneChargeRequest $charge): string
    {
        return $this->_execute(function () use ($charge) {
            return Stripe\Charge::create([
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
        }, 'ECharge');
    }

    public function submitCustomerCharge(mint\ICustomerChargeRequest $charge): string
    {
        return $this->_execute(function () use ($charge) {
            return Stripe\Charge::create([
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
        }, 'ECharge');
    }



    // Authorize / capture
    public function authorizeStandaloneCharge(mint\IStandaloneChargeRequest $charge): string
    {
        return $this->_execute(function () use ($charge) {
            return Stripe\Charge::create([
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
        }, 'ECharge');
    }

    public function authorizeCustomerCharge(mint\ICustomerChargeRequest $charge): string
    {
        return $this->_execute(function () use ($charge) {
            return Stripe\Charge::create([
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
        }, 'ECharge');
    }

    public function captureCharge(mint\IChargeCapture $charge): string
    {
        return $this->_execute(function () use ($charge) {
            $charge = Stripe\Charge::retrieve($charge->getId(), ['api_key' => $this->_apiKey]);
            $charge->capture();
            return $charge['id'];
        }, 'ECharge,ECapture');
    }



    // Refund
    public function refundCharge(mint\IChargeRefund $refund): string
    {
        return $this->_execute(function () use ($refund) {
            return Stripe\Refund::create([
                'charge' => $refund->getId(),
                'amount' => $refund->getAmount()->getIntegerAmount()
            ], [
                'api_key' => $this->_apiKey
            ])['charge'];
        }, 'ECharge,ERefund');
    }



    // Customers
    public function fetchCustomer(string $customerId): mint\ICustomer
    {
        return $this->_execute(function () use ($customerId) {
            $customer = Stripe\Customer::retrieve($customerId, ['api_key' => $this->_apiKey]);

            if ($customer['deleted']) {
                throw core\Error::{'EApi,ECustomer,ENotFound'}([
                    'message' => 'Customer has been deleted',
                    'data' => $customer
                ]);
            }

            return $this->_wrapCustomer($customer);
        }, 'ECustomer');
    }

    public function addCustomer(mint\ICustomer $customer): mint\ICustomer
    {
        return $this->_execute(function () use ($customer) {
            $customer = Stripe\Customer::create([
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
        }, 'ECustomer');
    }

    public function updateCustomer(mint\ICustomer $customer): mint\ICustomer
    {
        if ($customer->getId() === null) {
            throw core\Error::EArgument([
                'message' => 'Customer Id not set',
                'data' => $customer
            ]);
        }

        return $this->_execute(function () use ($customer) {
            $customer = Stripe\Customer::update($customer->getId(), [
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
        }, 'ECustomer');
    }

    public function deleteCustomer(string $customerId)
    {
        $this->_execute(function () use ($customerId) {
            $customer = Stripe\Customer::retrieve($customerId, ['api_key' => $this->_apiKey]);
            $customer->delete();
        }, 'ECustomer');

        return $this;
    }

    protected function _wrapCustomer(Stripe\Customer $customer): mint\ICustomer
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



    // Subscriptions
    protected function _wrapSubscription(Stripe\Subscription $subscription): mint\ISubscription
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
        if (!$card) {
            return null;
        } elseif ($card instanceof mint\ICreditCard) {
            $source = $this->_cardToArray($card);
            $source['object'] = 'card';
        } else {
            $source = $card->getToken();
        }

        return $source;
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

    protected function _getCacheKeyPrefix()
    {
        return $this->_apiKey.'-';
    }

    protected function _getCachedValue(string $key, callable $generator, string $eType=null)
    {
        $cache = Stripe2_Cache::getInstance();
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
        } catch (Stripe\Error\Base $e) {
            $data = $e->getJsonBody()['error'] ?? [];
            $types = ['EApi'];

            if (!empty($eType)) {
                $types[] = $eType;
            }

            if ($e->getHttpStatus() == 404) {
                $types[] = 'ENotFound';
            }

            if ($e instanceof Stripe\Error\Api ||
                $e instanceof Stripe\Error\ApiConnection ||
                $e instanceof Stripe\Error\Authentication ||
                $e instanceof Stripe\Error\InvalidRequest) {
                $types[] = 'EImplementation';
            } elseif ($e instanceof Stripe\Error\Card) {
                $types[] = 'ECard';

                switch ($data['code'] ?? null) {
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
            } elseif ($e instanceof Stripe\Error\RateLimit) {
                $types[] = 'ERateLimit';
            }

            throw core\Error::{implode(',', array_unique($types))}([
                'message' => $e->getMessage(),
                'previous' => $e,
                'data' => $data
            ]);
        }
    }
}

class StripeApi_Cache extends core\cache\Base
{
}

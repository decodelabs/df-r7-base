<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\payment\stripe;

use df;
use df\core;
use df\spur;
use df\link;
use df\mint;
use df\user;

class Mediator implements IMediator, core\IDumpable {

    const API_URL = 'https://api.stripe.com/v1/';

    protected $_httpClient;
    protected $_apiKey;
    protected $_activeUrl;
    protected $_defaultCurrency = 'USD';

    public function __construct($apiKey) {
        $this->_httpClient = new link\http\Client();
        $this->setApiKey($apiKey);
    }


// Client
    public function getHttpClient() {
        return $this->_httpClient;
    }


// Api key
    public function setApiKey($key) {
        $this->_apiKey = $key;
        $this->_activeUrl = null;
        return $this;
    }

    public function getApiKey() {
        return $this->_apiKey;
    }



// Curreny
    public function setDefaultCurrency($code) {
        $this->_defaultCurrency = $code;
        return $this;
    }

    public function getDefaultCurrency() {
        return $this->_defaultCurrency;
    }



// Helpers
    public static function cardReferenceToArray(mint\ICreditCardReference $card) {
        $output = [];

        if($card instanceof mint\ICreditCard) {
            $output['number'] = $card->getNumber();
            $output['exp_month'] = $card->getExpiryMonth();
            $output['exp_year'] = $card->getExpiryYear();

            if(null !== ($verification = $card->getVerificationCode())) {
                $output['cvc'] = $verification;
            }

            if(null !== ($name = $card->getName())) {
                $output['name'] = $name;
            }

            if($address = $card->getBillingAddress()) {
                $output['address_line1'] = $address->getMainStreetLine();
                $output['address_line2'] = $address->getExtendedStreetLine();
                $output['address_city'] = $address->getLocality();
                $output['address_state'] = $address->getRegion();
                $output['address_zip'] = $address->getPostalCode();
                $output['address_country'] = $address->getCountryName();
            }
        } else if($card instanceof mint\ICreditCardToken) {
            core\stub('token', $card);
        } else {
            core\stub($card);
        }

        return $output;
    }

    protected function _createListInputArray($limit, $offset, $filter) {
        $input = [];

        if($limit != 10 && $limit > 0) {
            $input['count'] = (int)$limit;
        }

        if($offset >= 0) {
            $input['offset'] = (int)$offset;
        }

        if(is_array($filter)) {
            $filter = array_intersect_key($filter, array_flip(['gt', 'gte', 'lt', 'lte']));

            if(!empty($filter)) {
                foreach($filter as $key => $value) {
                    $filter[$key] = core\time\Date::factory($value)->toTimestamp();
                }

                $input['created'] = $filter;
            }
        } else if($filter !== null) {
            $input['created'] = core\time\Date::factory($filter)->toTimestamp();
        }

        return $input;
    }


// Charges
    public function newChargeRequest($amount, mint\ICreditCardReference $card=null, $description=null, $emailAddress=null) {
        return new ChargeRequest($this, $amount, $card, $description, $emailAddress);
    }

    public function createCharge(IChargeRequest $request, $returnRaw=false) {
        $data = $this->callServer('post', 'charges', $request->getSubmitArray());

        if($returnRaw) {
            return $data;
        }

        return new Charge($this, $data);
    }

    public function fetchCharge($id, $returnRaw=false) {
        $data = $this->callServer('get', 'charges/'.$id);

        if($returnRaw) {
            return $data;
        }

        return new Charge($this, $data);
    }

    public function refundCharge($id, $amount=null, $refundApplicationFee=null, $returnRaw=false) {
        if($id instanceof ICharge) {
            $id = $id->getId();
        }

        $input = [];

        if($amount !== null) {
            $amount = mint\Currency::factory($amount, $this->_defaultCurrency);
            $input['amount'] = $amount->getIntegerAmount();
        }

        if($refundApplicationFee !== null) {
            $input['refund_application_fee'] = $refundApplicationFee ? 'true' : 'false';
        }

        $data = $this->callServer('post', 'charges/'.$id.'/refund', $input);

        if($returnRaw) {
            return $data;
        }

        return new Charge($this, $data);
    }

    public function captureCharge($id, $amount=null, $applicationFee=null, $returnRaw=false) {
        if($id instanceof ICharge) {
            $id = $id->getId();
        }

        $input = [];

        if($amount !== null) {
            $amount = mint\Currency::factory($amount, $this->_defaultCurrency);
            $input['amount'] = $amount->getIntegerAmount();
        }

        if($applicationFee !== null) {
            $applicationFee = mint\Currency::factory($applicationFee, $this->_defaultCurrency);
            $input['application_fee'] = $application->getIntegerAmount();
        }

        $data = $this->callServer('post', 'charges/'.$id.'/capture', $input);

        if($returnRaw) {
            return $data;
        }

        return new Charge($this, $data);
    }

    public function fetchChargeList($limit=10, $offset=0, $filter=null, $customerId=null, $returnRaw=false) {
        $input = $this->_createListInputArray($limit, $offset, $filter);

        if($customerId !== null) {
            $input['customer'] = $customerId;
        }

        $data = $this->callServer('get', 'charges', $input);

        if($returnRaw) {
            return $data;
        }

        $rows = [];

        foreach($data->data as $row) {
            $rows[] = new Charge($this, $row);
        }

        return new core\collection\PageableQueue($rows, $limit, $offset, $data['count']);
    }



// Customers
    public function newCustomerRequest($emailAddress=null, mint\ICreditCardReference $card=null, $description=null, $balance=null) {
        return new CustomerRequest($this, $emailAddress, $card, $description, $balance);
    }

    public function createCustomer(ICustomerRequest $request, $returnRaw=false) {
        $request->setSubmitAction('create');
        $data = $this->callServer('post', 'customers', $request->getSubmitArray());

        if($returnRaw) {
            return $data;
        }

        return new Customer($this, $data);
    }

    public function fetchCustomer($id, $returnRaw=null) {
        $data = $this->callServer('get', 'customers/'.$id);

        if($returnRaw) {
            return $data;
        }

        return new Customer($this, $data);
    }

    public function updateCustomer(ICustomerRequest $request, $returnRaw=false) {
        $request->setSubmitAction('update');
        $data = $this->callServer('post', 'customers/'.$request->getId(), $request->getSubmitArray());

        if($returnRaw) {
            return $data;
        }

        return new Customer($this, $data);
    }

    public function deleteCustomer($id) {
        if($id instanceof ICustomer) {
            $id = $id->getId();
        }

        $data = $this->callServer('delete', 'customers/'.$id);
        return $this;
    }

    public function fetchCustomerList($limit=10, $offset=0, $filter=null, $returnRaw=false) {
        $input = $this->_createListInputArray($limit, $offset, $filter);
        $data = $this->callServer('get', 'customers', $input);

        if($returnRaw) {
            return $data;
        }

        $rows = [];

        foreach($data->data as $row) {
            $rows[] = new Customer($this, $row);
        }

        return new core\collection\PageableQueue($rows, $limit, $offset, $data['count']);
    }

    public function endDiscount($id) {
        if($id instanceof ICustomer) {
            $id = $id->getId();
        }

        $this->callServer('delete', 'customers/'.$id.'/discount');
        return $this;
    }



// Cards
    public function createCard($customer, mint\ICreditCard $card, $returnRaw=false) {
        if($customer instanceof ICustomer) {
            $customer = $customer->getId();
        }

        $data = $this->callServer('post', 'customers/'.$customer.'/cards', [
            'card' => $this->cardReferenceToArray($card)
        ]);

        if($returnRaw) {
            return $data;
        }

        return new CreditCard($this, $data);
    }

    public function fetchCard($customer, $id, $returnRaw=false) {
        if($customer instanceof ICustomer) {
            $customer = $customer->getId();
        }

        $data = $this->callServer('get', 'customers/'.$customer.'/cards/'.$id);

        if($returnRaw) {
            return $data;
        }

        return new CreditCard($this, $data);
    }

    public function updateCard($customer, $id, mint\ICreditCard $card, $returnRaw=false) {
        if($customer instanceof ICustomer) {
            $customer = $customer->getId();
        }

        $input = $this->cardReferenceToArray($card);
        unset($input['cvc'], $input['number']);

        $data = $this->callServer('post', 'customers/'.$customer.'/cards/'.$id, $input);

        if($returnRaw) {
            return $data;
        }

        return new CreditCard($this, $data);
    }

    public function deleteCard($customer, $id) {
        if($customer instanceof ICustomer) {
            $customer = $customer->getId();
        }

        if($id instanceof ICreditCard) {
            $id = $id->getId();
        }

        $this->callServer('delete', 'customers/'.$customer.'/cards/'.$id);
        return $this;
    }

    public function fetchCardList($customer, $limit=10, $offset=0, $returnRaw=false) {
        if($customer instanceof ICustomer) {
            $customer = $customer->getId();
        }

        $input = $this->_createListInputArray($limit, $offset, null);
        $data = $this->callServer('get', 'customers/'.$customer.'/cards', $input);

        if($returnRaw) {
            return $data;
        }

        $rows = [];

        foreach($data->data as $row) {
            $rows[] = new CreditCard($this, $row);
        }

        return new core\collection\PageableQueue($rows, $limit, $offset, $data['count']);
    }



// Plans
    public function newPlanRequest($id, $name, $amount, $intervalQuantity=1, $intervalUnit='month') {
        return (new Plan($this))
            ->setSubmitAction('create')
            ->setId($id)
            ->setName($name)
            ->setAmount($amount)
            ->setInterval($intervalQuantity, $intervalUnit);
    }

    public function createPlan(IPlan $plan, $returnRaw=false) {
        $data = $this->callServer('post', 'plans', $plan->getSubmitArray());

        if($returnRaw) {
            return $data;
        }

        return new Plan($this, $data);
    }

    public function fetchPlan($id, $returnRaw=false) {
        $data = $this->callServer('get', 'plans/'.$id);

        if($returnRaw) {
            return $data;
        }

        return new Plan($this, $data);
    }

    public function renamePlan($id, $newName, $returnRaw=false) {
        if($id instanceof IPlan) {
            $id = $id->getId();
        }

        $data = $this->callServer('post', 'plans/'.$id, [
            'name' => $newName
        ]);

        if($returnRaw) {
            return $data;
        }

        return new Plan($this, $data);
    }

    public function deletePlan($id) {
        if($id instanceof IPlan) {
            $id = $id->getId();
        }

        $this->callServer('delete', 'plans/'.$id);
        return $this;        
    }

    public function fetchPlanList($limit=10, $offset=0, $returnRaw=false) {
        $input = $this->_createListInputArray($limit, $offset, null);
        $data = $this->callServer('get', 'plans', $input);

        if($returnRaw) {
            return $data;
        }

        $rows = [];

        foreach($data->data as $row) {
            $rows[] = new Plan($this, $row);
        }

        return new core\collection\PageableQueue($rows, $limit, $offset, $data['count']);
    }


// Subscriptions
    public function newSubscriptionRequest($customerId, $planId, mint\ICreditCardReference $card=null, $quantity=1) {
        return new SubscriptionRequest($this, $customerId, $planId, $card, $quantity);
    }

    public function updateSubscription(ISubscriptionRequest $request, $returnRaw=false) {
        $data = $this->callServer('post', 'customers/'.$request->getCustomerId().'/subscription', $request->getSubmitArray());

        if($returnRaw) {
            return $data;
        }

        return new Subscription($this, $data);
    }

    public function cancelSubscription($customerId, $atPeriodEnd=false, $returnRaw=false) {
        $data = $this->callServer('delete', 'customers/'.$customerId.'/subscription', [
            'at_period_end' => $atPeriodEnd ? 'true' : 'false'
        ]);

        if($returnRaw) {
            return $data;
        }

        return new Subscription($this, $data);
    }


// Coupons
    public function newCouponRequest($id, $application, $amount=null, $percent=null) {
        return (new Coupon($this))
            ->setId($id)
            ->setApplication($application)
            ->setAmount($amount)
            ->setPercent($percent);
    }

    public function createCoupon(ICoupon $coupon, $returnRaw=false) {
        $coupon->setSubmitAction('create');
        $data = $this->callServer('post', 'coupons', $coupon->getSubmitArray());

        if($returnRaw) {
            return $data;
        }

        return new Coupon($this, $data);
    }

    public function fetchCoupon($id, $returnRaw=false) {
        $data = $this->callServer('get', 'coupons/'.$id);

        if($returnRaw) {
            return $data;
        }

        return new Coupon($this, $data);
    }

    public function deleteCoupon($id) {
        if($id instanceof ICoupon) {
            $id = $id->getId();
        }

        $this->callServer('delete', 'coupons/'.$id);
        return $this;
    }

    public function fetchCouponList($limit=10, $offset=0, $returnRaw=false) {
        $input = $this->_createListInputArray($limit, $offset, null);
        $data = $this->callServer('get', 'coupons', $input);

        if($returnRaw) {
            return $data;
        }

        $rows = [];

        foreach($data->data as $row) {
            $rows[] = new Coupon($this, $row);
        }

        return new core\collection\PageableQueue($rows, $limit, $offset, $data['count']);
    }



// Account
    public function getAccountDetails() {
        return new AccountDetails($this->callServer('get', 'account'));
    }


// IO
    public function callServer($method, $path, array $data=[]) {
        if(!$this->_activeUrl) {
            $this->_activeUrl = link\http\Url::factory(self::API_URL);
        }

        $url = clone $this->_activeUrl;
        $url->path->shouldAddTrailingSlash(false)->push($path);

        $request = link\http\request\Base::factory($url);
        $request->setMethod($method);
        $request->setSecureTransport('sslv3');
        $request->getHeaders()->set('Authorization', 'Bearer '.$this->_apiKey);

        if(!empty($data)) {
            if($method == 'post') {
                $request->setPostData($data);
                $request->getHeaders()->set('content-type', 'application/x-www-form-urlencoded');
            } else {
                $url->setQuery($data);
            }
        }

        $this->_httpClient->setMaxRetries(0);
        $response = $this->_httpClient->sendRequest($request);
        $data = $response->getJsonContent();

        if(!$response->isOk()) {
            if($response->getHeaders()->getStatusCode() >= 500) {
                throw new ApiImplementationError($data->error->toArray());
            } else {
                throw new ApiDataError($data->error->toArray());
            }
        }

        return $data;
    }


// Dump
    public function getDumpProperties() {
        return [
            'apiKey' => $this->_apiKey,
            'url' => $this->_activeUrl
        ];
    }
}
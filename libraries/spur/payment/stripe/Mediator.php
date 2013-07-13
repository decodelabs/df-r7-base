<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\payment\stripe;

use df;
use df\core;
use df\spur;
use df\halo;
use df\mint;
    
class Mediator implements IMediator, core\IDumpable {

    const API_URL = 'https://api.stripe.com/v1/';

    protected $_httpClient;
    protected $_apiKey;
    protected $_activeUrl;

    public function __construct($apiKey) {
        $this->_httpClient = new halo\protocol\http\Client();
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


// Charges
    public function newChargeRequest($amount, mint\ICreditCardReference $card, $description=null) {
        return new ChargeRequest($amount, $card, $description);
    }

    public function submitCharge(IChargeRequest $request, $returnRaw=false) {
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
            $amount = mint\Currency::factory($amount);
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
            $amount = mint\Currency::factory($amount);
            $input['amount'] = $amount->getIntegerAmount();
        }

        if($applicationFee !== null) {
            $applicationFee = mint\Currency::factory($applicationFee);
            $input['application_fee'] = $application->getIntegerAmount();
        }

        $data = $this->callServer('post', 'charges/'.$id.'/capture', $input);

        if($returnRaw) {
            return $data;
        }

        return new Charge($this, $data);
    }

    public function fetchChargeList($limit=10, $offset=0, $filter=null, $customerId=null, $returnRaw=false) {
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


// IO
    public function callServer($method, $path, array $data=array()) {
        if(!$this->_activeUrl) {
            $this->_activeUrl = halo\protocol\http\Url::factory(self::API_URL);
        }

        $url = clone $this->_activeUrl;
        $url->path->shouldAddTrailingSlash(false)->push($path);

        $request = halo\protocol\http\request\Base::factory($url);
        $request->setMethod($method);
        $request->getHeaders()->set('Authorization', 'Bearer '.$this->_apiKey);

        if(!empty($data)) {
            if($method == 'post') {
                $request->setPostData($data);
                $request->getHeaders()->set('content-type', 'application/x-www-form-urlencoded');
            } else if($method == 'get') {
                $url->setQuery($data);
            }
        }

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
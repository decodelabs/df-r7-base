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
    
class Mediator implements IMediator {

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
        return $this;
    }

    public function getApiKey() {
        return $this->_apiKey;
    }


// Charges
    public function newCharge($amount, mint\ICreditCardReference $card, $description=null) {
        return new Charge($amount, $card, $description);
    }

    public function submitCharge(ICharge $charge) {
        core\stub($charge);
    }


// IO
    public function callServer($path, array $data=array(), $method='post') {
        if(!$this->_activeUrl) {
            $this->_activeUrl = halo\protocol\http\Url::factory(self::API_URL);
        }

        core\dump($this->_activeUrl);
    }
}
<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur;

use df;
use df\core;
use df\spur;
use df\link;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}

class ApiError extends RuntimeException implements core\IDumpable {

    protected $_data;

    public function __construct($message, $data, $httpCode=500) {
        parent::__construct($message, $httpCode);
        $this->_data = $data;
    }

    public function getData() {
        return $this->_data;
    }

    public function getDumpProperties() {
        return $this->_data;
    }
}

class ApiDataError extends ApiError {}
class ApiImplementationError extends ApiError {}


// Interfaces
interface IHttpMediator {
    public function setHttpClient(link\http\peer\IClient $client);
    public function getHttpClient();

    public function requestRaw($method, $path, array $data=[], array $headers=[]);
    public function requestJson($method, $path, array $data=[], array $headers=[]);
    public function createUrl($path);
    public function createRequest($method, $path, array $data=[], array $headers=[]);
    public function sendRequest(link\http\IRequest $request);
}

trait THttpMediator {

    protected $_httpClient;

    public function setHttpClient(link\http\peer\IClient $client) {
        $this->_httpClient = $client;
        return $this;
    }

    public function getHttpClient() {
        if(!$this->_httpClient) {
            $this->_httpClient = new link\http\peer\Client();
        }

        return $this->_httpClient;
    }


    public function requestRaw($method, $path, array $data=[], array $headers=[]) {
        return $this->sendRequest($this->createRequest(
            $method, $path, $data, $headers
        ));
    }

    public function requestJson($method, $path, array $data=[], array $headers=[]) {
        return $this->sendRequest($this->createRequest(
                $method, $path, $data, $headers
            ))
            ->getJsonContent();
    }

    public function createRequest($method, $path, array $data=[], array $headers=[]) {
        $url = $this->createUrl($path);
        $request = link\http\request\Base::factory($url);
        $request->setMethod($method);

        if(!empty($data)) {
            if($method == 'post') {
                $request->setPostData($data);
                $request->headers->set('content-type', 'application/x-www-form-urlencoded');
            } else {
                $request->url->query->import($data);
            }
        }

        if(!empty($headers)) {
            $request->getHeaders()->import($headers);
        }

        return $request;
    }

    public function sendRequest(link\http\IRequest $request) {
        $new = $this->_prepareRequest($request);

        if($new instanceof link\http\IRequest) {
            $request = $new;
        }

        $this->getHttpClient();

        $this->_httpClient->setMaxRetries(0);
        $response = $this->_httpClient->sendRequest($request);

        if(!$response->isOk()) {
            $message = $this->_extractResponseError($response);

            if($message instanceof \Exception) {
                throw $message;
            }

            if($response->getHeaders()->getStatusCode() >= 500) {
                throw new ApiImplementationError($message, $data->toArray());
            } else {
                throw new ApiDataError($message, $data->toArray());
            }
        }

        return $response;
    }


    public function createUrl($path) {
        return link\http\Url::factory($path);
    }

    protected function _prepareRequest(link\http\IRequest $request) {}

    protected function _extractResponseError(link\http\IResponse $response) {
        try {
            $data = $response->getJsonContent();
        } catch(\Exception $e) {
            $data = new core\collection\Tree();
        }

        return $data['message'];
    }
}

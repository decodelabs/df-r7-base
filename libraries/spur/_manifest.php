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

    public function __construct($message, array $data) {
        parent::__construct($message);
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
                $request->url->setQuery($data);
            }
        }

        if(!empty($headers)) {
            $request->getHeaders()->import($headers);
        }

        $this->_prepareRequest($request);
        return $request;
    }

    public function sendRequest(link\http\IRequest $request) {
        $this->getHttpClient();

        $this->_httpClient->setMaxRetries(0);
        $response = $this->_httpClient->sendRequest($request);

        if(!$response->isOk()) {
            try {
                $data = $response->getJsonContent();
            } catch(\Exception $e) {
                $data = new core\collection\Tree();
            }

            $message = $this->_extractErrorMessage($data);

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

    protected function _extractErrorMessage(core\collection\ITree $data) {
        return $data['message'];
    }
}

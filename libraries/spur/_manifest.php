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
    public function callServer($method, $path, array $data=[], $returnResponse=false);
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

    public function callServer($method, $path, array $data=[], $returnResponse=false) {
        $this->getHttpClient();

        $url = $this->_createUrl($path);
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

        $this->_httpClient->setMaxRetries(0);
        $response = $this->_httpClient->sendRequest($request);

        if(!$response->isOk()) {
            $data = $response->getJsonContent();
            $message = $this->_extractErrorMessage($data);

            if($response->getHeaders()->getStatusCode() >= 500) {
                throw new ApiImplementationError($message, $data->toArray());
            } else {
                throw new ApiDataError($message, $data->toArray());
            }
        }
        
        if($returnResponse) {
            return $response;
        }

        return $this->_handleResponse($response);
    }

    protected function _createUrl($path) {
        return $path;
    }

    protected function _prepareRequest(link\http\IRequest $request) {
        // noop
    }

    protected function _handleResponse(link\http\IResponse $response) {
        return $response->getJsonContent();
    }

    protected function _extractErrorMessage(core\collection\ITree $data) {
        return $data['message'];
    }
}

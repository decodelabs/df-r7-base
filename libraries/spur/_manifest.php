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


// Interfaces
interface IHttpMediator {
    public function setHttpClient(link\http\IClient $client);
    public function getHttpClient();

    public function requestRaw($method, $path, array $data=[], array $headers=[]);
    public function requestJson($method, $path, array $data=[], array $headers=[]);
    public function createUrl($path);
    public function createRequest($method, $path, array $data=[], array $headers=[]);
    public function sendRequest(link\http\IRequest $request);
}

trait THttpMediator {

    protected $_httpClient;

    public function setHttpClient(link\http\IClient $client) {
        $this->_httpClient = $client;
        return $this;
    }

    public function getHttpClient() {
        if(!$this->_httpClient) {
            $this->_httpClient = new link\http\Client();
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

        try {
            $response = $this->getHttpClient()->sendRequest($request);
        } catch(\Throwable $e) {
            throw core\Error::{'EImplementation,ETransport,EApi'}([
                'message' => $e->getMessage(),
                'previous' => $e
            ]);
        }

        if(!$this->_isResponseOk($response)) {
            $message = $this->_extractResponseError($response);

            if($message instanceof \Throwable) {
                throw $message;
            }

            $code = $response->getHeaders()->getStatusCode();

            if($code >= 500) {
                throw core\Error::{'EImplementation,spur/EImplementation'}([
                    'message' => $message,
                    'data' => $response->getContent(),
                    'code' => $code
                ]);
            } else {
                throw core\Error::{'EApi,spur\EApi'}([
                    'message' => $message,
                    'data' => $this->_normalizeErrorData($response->getContent()),
                    'code' => $code
                ]);
            }
        }




        return $response;
    }

    protected function _normalizeErrorData($data) {
        return $data;
    }


    public function createUrl($path) {
        return link\http\Url::factory($path);
    }

    protected function _prepareRequest(link\http\IRequest $request) {}

    protected function _isResponseOk(link\http\IResponse $response) {
        return $response->isOk();
    }

    protected function _extractResponseError(link\http\IResponse $response) {
        try {
            $data = $response->getJsonContent();
        } catch(\Throwable $e) {
            $data = new core\collection\Tree();
        }

        return $data['message'];
    }
}

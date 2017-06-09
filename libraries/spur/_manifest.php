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
    public function getHttpClient(): link\http\IClient;

    public function requestRaw(string $method, string $path, array $data=[], array $headers=[]): link\http\IResponse;
    public function requestJson(string $method, string $path, array $data=[], array $headers=[]): core\collection\ITree;
    public function createUrl(string $path): link\http\IUrl;
    public function createRequest(string $method, string $path, array $data=[], array $headers=[]): link\http\IRequest;
    public function sendRequest(link\http\IRequest $request): link\http\IResponse;
}

trait THttpMediator {

    protected $_httpClient;

    public function setHttpClient(link\http\IClient $client) {
        $this->_httpClient = $client;
        return $this;
    }

    public function getHttpClient(): link\http\IClient {
        if(!$this->_httpClient) {
            $this->_httpClient = new link\http\Client();
        }

        return $this->_httpClient;
    }


    public function requestRaw(string $method, string $path, array $data=[], array $headers=[]): link\http\IResponse {
        return $this->sendRequest($this->createRequest(
            $method, $path, $data, $headers
        ));
    }

    public function requestJson(string $method, string $path, array $data=[], array $headers=[]): core\collection\ITree {
        return $this->sendRequest($this->createRequest(
                $method, $path, $data, $headers
            ))
            ->getJsonContent();
    }

    public function createRequest(string $method, string $path, array $data=[], array $headers=[]): link\http\IRequest {
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

    public function sendRequest(link\http\IRequest $request): link\http\IResponse {
        $request = $this->_prepareRequest($request);

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


    public function createUrl(string $path): link\http\IUrl {
        return link\http\Url::factory($path);
    }

    protected function _prepareRequest(link\http\IRequest $request): link\http\IRequest {
        return $request;
    }

    protected function _isResponseOk(link\http\IResponse $response): bool {
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





// Data object
interface IDataObject extends core\collection\ITree {
    public function setType(string $type);
    public function getType(): string;
}


class DataObject extends core\collection\Tree implements IDataObject {

    protected const PROPAGATE_TYPE = false;

    protected $_type;

    public function __construct(string $type, core\collection\ITree $data, $callback=null) {
        $this->setType($type);

        if($callback) {
            core\lang\Callback::call($callback, $data);
        }

        $this->_collection = $data->_collection;
    }

    public function setType(string $type) {
        $this->_type = $type;
        return $this;
    }

    public function getType(): string {
        return $this->_type;
    }


// Serialize
    protected function _getSerializeValues() {
        $output = parent::_getSerializeValues();
        $output['ty'] = $this->_type;

        return $output;
    }

    protected function _setUnserializedValues(array $values) {
        parent::_setUnserializedValues($values);
        $this->_type = $values['ty'] ?? 'object';
    }

// Dump
    public function getDumpProperties() {
        $output = parent::getDumpProperties();

        array_unshift(
            $output,
            new core\debug\dumper\Property('type', $this->_type, 'private')
        );

        return $output;
    }
}



// List
interface IDataList extends core\IArrayProvider, \IteratorAggregate {
    public function getTotal(): int;
    public function hasMore(): bool;

    public function setFilter(IFilter $filter);
    public function getFilter(): IFilter;
}



// Filter
interface IFilter extends core\IArrayProvider {
    public static function normalize(IFilter &$filter=null, callable $callback=null, array $extra=null): array;

    public function setLimit(?int $limit);
    public function getLimit(): ?int;
}

trait TFilter {

    protected $_limit = null;

    public static function normalize(IFilter &$filter=null, callable $callback=null, array $extra=null): array {
        if(!$filter) {
            $filter = new static;
        }

        if($callback) {
            core\lang\Callback::call($callback, $filter);
        }

        $output = $filter->toArray();

        if($extra !== null) {
            $output = array_merge($output, $extra);
        }

        return $output;
    }

    public function setLimit(?int $limit) {
        $this->_limit = $limit;
        return $this;
    }

    public function getLimit(): ?int {
        return $this->_limit;
    }
}

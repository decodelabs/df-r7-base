<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\migrate;

use df;
use df\core;
use df\spur;
use df\link;
use df\arch;

class Handler implements IHandler {

    protected $_key;
    protected $_url;
    protected $_router;
    protected $_context;
    protected $_httpPool;

    protected $_asyncBatchSize = 0;
    protected $_asyncBatchLimit = 10;

    public function __construct($url) {
        $this->_context = new core\SharedContext();

        $this->_key = $this->_context->data->hexHash(
            $this->_context->application->getPassKey()
        );

        $this->_httpPool = (new link\http\Client())->newPool();
        $this->_sayHello($url);
    }

    public function getKey() {
        return $this->_key;
    }

    public function getUrl() {
        return $this->_url;
    }

    public function getRouter() {
        return $this->_router;
    }

    protected function _sayHello($url) {
        $url = link\http\Url::factory($url);
        $url->path->push('~devtools/migrate/hello')->shouldAddTrailingSlash(false);
        $url->query->key = $this->_key;

        $response = $this->_httpPool->getClient()->get($url);
        $content = $response->getJsonContent();

        if(!$content->data->nodes->contains('media')) {
            throw core\Error::{'EApi,EForbidden'}([
                'message' => 'Target app does not support media migration',
                'data' => $content->data->nodes->toArray(),
                'http' => 403
            ]);
        }

        $this->_url = new link\http\Url($content->data['baseUrl']);
        $this->_router = new core\application\http\Router($this->_url);
    }


    public function setAsyncBatchLimit($limit) {
        $limit = (int)$limit;

        if($limit < 1) {
            $limit = 1;
        }

        $this->_asyncBatchLimit = $limit;
        return $this;
    }

    public function getAsyncBatchLimit() {
        return $this->_asyncBatchLimit;
    }


    public function createRequest($method, $request, array $data=null, $responseFilePath=null) {
        if(is_string($request)) {
            $request = new arch\Request($request);
        }

        if(($request instanceof arch\IRequest
        || $request instanceof link\http\IUrl)) {
            if($data !== null) {
                $request->getQuery()->import($data);
            }

            $request->getQuery()->key = $this->_key;
        }

        if($request instanceof arch\IRequest) {
            $request = $this->_router->requestToUrl($request);
        }

        if(!$request instanceof link\http\IRequest) {
            if(!$request instanceof link\http\IUrl) {
                throw core\Error::EBadRequest([
                    'message' => 'Invalid request'
                ]);
            }

            $request = new link\http\request\Base($request);
        }

        if($responseFilePath !== null) {
            $request->options->setDownloadFilePath($responseFilePath);
        }

        $request->setMethod($method);
        return $request;
    }

    public function call(link\http\IRequest $request) {
        $response = $this->_httpPool->getClient()->sendRequest($request);

        if($response->isError()) {
            $content = $response->getJsonContent();
            throw core\Error::EApi('Migration failed: '.$content['error']);
        }

        return $response;
    }

    public function callAsync(link\http\IRequest $request, $callback) {
        $this->_httpPool->promiseResponse($request)->then($callback);
        return $this;
    }

    public function sync() {
        $this->_httpPool->sync();
        return $this;
    }
}
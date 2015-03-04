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
    protected $_httpClient;

    protected $_asyncBatchSize = 0;
    protected $_asyncBatchLimit = 10;

    public function __construct($url) {
        $this->_context = new core\SharedContext();

        $this->_key = bin2hex($this->_context->data->hash(
            $this->_context->application->getPassKey()
        ));

        $this->_httpClient = new link\http\peer\Client();
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

        $response = $this->_httpClient->get($url);
        $content = $response->getJsonContent();

        if(!$content->data->actions->contains('media')) {
            $this->_context->throwError(
                403, 
                'Target app does not support media migration', 
                $content->data->actions->toArray()
            );
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
                $this->_context->throwError(500, 'Invalid request');
            }

            $request = new link\http\request\Base($request);
        }

        if($responseFilePath !== null) {
            $request->setResponseFilePath($responseFilePath);
        }

        $request->setMethod($method);
        return $request;
    }

    public function call(link\http\IRequest $request) {
        $response = $this->_httpClient->sendRequest($request);

        if($response->isError()) {
            $content = $response->getJsonContent();
            $this->throwError(500, 'Migration failed: '.$content['error']);
        }

        return $response;
    }

    public function callAsync(link\http\IRequest $request, $callback) {
        $this->_httpClient->addRequest($request, $callback);
        $this->_asyncBatchSize++;

        if($this->_asyncBatchSize >= 10) {
            $this->_asyncBatchSize = 0;
            $this->_httpClient->run();
        }

        return $this;
    }

    public function executeAsync() {
        if($this->_asyncBatchSize) {
            $this->_httpClient->run();
            $this->_asyncBatchSize = 0;
        }

        return $this;
    }
}
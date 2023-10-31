<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\spur\migrate;

use DecodeLabs\Exceptional;
use DecodeLabs\R7\Legacy;
use df;
use df\arch;
use df\core;

use df\flex;
use df\link;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\Request as HttpRequest;

class Handler implements IHandler
{
    protected $_key;
    protected $_url;
    protected $_router;
    protected $_context;
    protected $_httpClient;

    protected $_asyncBatchSize = 0;
    protected $_asyncBatchLimit = 10;

    public function __construct($url)
    {
        $this->_context = new core\SharedContext();

        $this->_key = Legacy::hexHash(
            Legacy::getPassKey()
        );

        $this->_httpClient = new HttpClient();
        $this->_sayHello($url);
    }

    public function getKey()
    {
        return $this->_key;
    }

    public function getUrl()
    {
        return $this->_url;
    }

    public function getRouter()
    {
        return $this->_router;
    }

    protected function _sayHello($url)
    {
        $url = link\http\Url::factory($url);
        $url->path->push('~devtools/migrate/hello')->shouldAddTrailingSlash(false);
        $url->query->key = $this->_key;

        $response = $this->_httpClient->get((string)$url, [
            'http_errors' => false,
            'verify' => false
        ]);

        $content = flex\Json::stringToTree((string)$response->getBody());

        if (!$content->data->nodes->contains('media')) {
            throw Exceptional::{'Api,Forbidden'}([
                'message' => 'Target app does not support media migration',
                'data' => $content->data->nodes->toArray(),
                'http' => 403
            ]);
        }

        $this->_url = new link\http\Url($content->data['baseUrl']);

        if ($url->hasUsername()) {
            $this->_url->setUsername($url->getUsername());
        }
        if ($url->hasPassword()) {
            $this->_url->setPassword($url->getPassword());
        }

        $this->_router = new core\app\http\Router($this->_url);
    }


    public function setAsyncBatchLimit($limit)
    {
        $limit = (int)$limit;

        if ($limit < 1) {
            $limit = 1;
        }

        $this->_asyncBatchLimit = $limit;
        return $this;
    }

    public function getAsyncBatchLimit()
    {
        return $this->_asyncBatchLimit;
    }


    public function createRequest($method, $request, array $data = null)
    {
        if (is_string($request)) {
            $request = new arch\Request($request);
        }

        if (($request instanceof arch\IRequest
        || $request instanceof link\http\IUrl)) {
            if ($data !== null) {
                $request->getQuery()->import($data);
            }

            $request->getQuery()->key = $this->_key;
        }

        if ($request instanceof arch\IRequest) {
            $request = $this->_router->requestToUrl($request);
        }

        if (!$request instanceof link\http\IRequest) {
            if (!$request instanceof link\http\IUrl) {
                throw Exceptional::BadRequest([
                    'message' => 'Invalid request'
                ]);
            }

            $request = new link\http\request\Base($request);
        }

        if ($this->_url->hasUsername()) {
            $request->getUrl()->setUsername($this->_url->getUsername());
        }

        if ($this->_url->hasPassword()) {
            $request->getUrl()->setPassword($this->_url->getPassword());
        }

        $request->setMethod($method);
        //$request->getHeaders()->set('x-df-self', Legacy::$http->getDfSelfKey());

        return $request;
    }

    public function call(link\http\IRequest $request)
    {
        $psrRequest = $this->_prepareRequest($request);
        return $this->_httpClient->send($psrRequest, [
            'verify' => false
        ]);
    }

    public function callAsync(link\http\IRequest $request, callable $callback, ?callable $progress = null)
    {
        $psrRequest = $this->_prepareRequest($request);
        return $this->_httpClient->sendAsync($psrRequest, [
            'verify' => false,
            'progress' => $progress
        ])->then($callback);
    }

    protected function _prepareRequest(link\http\IRequest $request)
    {
        $request->prepareHeaders();
        $url = clone $request->getUrl();
        //$url->setUsername(null);
        //$url->setPassword(null);

        return new HttpRequest(
            $request->getMethod(),
            (string)$url,
            $request->getHeaders()->toArray(),
            $request->getBodyDataString()
        );
    }

    public function sync()
    {
        return $this;
    }
}

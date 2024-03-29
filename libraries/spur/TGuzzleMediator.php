<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur;

use DecodeLabs\Exceptional;
use df\core;
use df\flex;

use df\link;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\Request as HttpRequest;
use Psr\Http\Message\ResponseInterface;

trait TGuzzleMediator
{
    protected $_httpClient;

    public function setHttpClient(HttpClient $client)
    {
        $this->_httpClient = $client;
        return $this;
    }

    public function getHttpClient(): HttpClient
    {
        if (!$this->_httpClient) {
            $this->_httpClient = new HttpClient();
        }

        return $this->_httpClient;
    }


    public function requestRaw(string $method, string $path, array $data = [], array $headers = []): ResponseInterface
    {
        return $this->sendRequest($this->createRequest(
            $method,
            $path,
            $data,
            $headers
        ));
    }

    public function requestJson(string $method, string $path, array $data = [], array $headers = []): core\collection\ITree
    {
        $response = $this->sendRequest($this->createRequest(
            $method,
            $path,
            $data,
            $headers
        ));

        return flex\Json::stringToTree((string)$response->getBody());
    }

    public function createRequest(string $method, string $path, array $data = [], array $headers = []): link\http\IRequest
    {
        $url = $this->createUrl($path);
        $request = link\http\request\Base::factory($url);
        $request->setMethod($method);

        if (!empty($data)) {
            if ($method == 'post') {
                $request->setPostData($data);
                $request->headers->set('content-type', 'application/x-www-form-urlencoded');
            } else {
                $request->url->query->import($data);
            }
        }

        if (!empty($headers)) {
            $request->getHeaders()->import($headers);
        }

        return $request;
    }

    public function sendRequest(link\http\IRequest $request): ResponseInterface
    {
        $request = $this->_prepareRequest($request);
        $request->prepareHeaders();

        $psrRequest = new HttpRequest(
            $request->getMethod(),
            (string)$request->getUrl(),
            $request->getHeaders()->toArray(),
            $request->getBodyDataString()
        );

        try {
            $response = $this->getHttpClient()->send($psrRequest, [
                'http_errors' => false
            ]);
        } catch (\Throwable $e) {
            throw Exceptional::{'Implementation,Transport,Api'}([
                'message' => $e->getMessage(),
                'previous' => $e
            ]);
        }

        if (!$this->_isResponseOk($response)) {
            $message = $this->_extractResponseError($response);

            if ($message instanceof \Throwable) {
                throw $message;
            }

            $code = $response->getStatusCode();

            if ($code >= 500) {
                throw Exceptional::{'Implementation'}([
                    'message' => $message,
                    'data' => (string)$response->getBody(),
                    'code' => $code
                ]);
            } else {
                throw Exceptional::{'Api'}([
                    'message' => $message,
                    'data' => $this->_normalizeErrorData((string)$response->getBody()),
                    'code' => $code
                ]);
            }
        }

        return $response;
    }

    protected function _normalizeErrorData($data)
    {
        return $data;
    }


    public function createUrl(string $path): link\http\IUrl
    {
        return link\http\Url::factory($path);
    }

    protected function _prepareRequest(link\http\IRequest $request): link\http\IRequest
    {
        return $request;
    }

    protected function _isResponseOk(ResponseInterface $response): bool
    {
        return $response->getStatusCode() < 300;
    }

    protected function _extractResponseError(ResponseInterface $response)
    {
        try {
            $data = flex\Json::stringToTree((string)$response->getBody());
        } catch (\Throwable $e) {
            $data = new core\collection\Tree();
        }

        return $data['message'];
    }
}

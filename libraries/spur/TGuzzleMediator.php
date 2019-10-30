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
use df\flex;

use DecodeLabs\Glitch;
use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

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


    public function requestRaw(string $method, string $path, array $data=[], array $headers=[]): ResponseInterface
    {
        return $this->sendRequest($this->createRequest(
            $method, $path, $data, $headers
        ));
    }

    public function requestJson(string $method, string $path, array $data=[], array $headers=[]): core\collection\ITree
    {
        $response = $this->sendRequest($this->createRequest(
            $method, $path, $data, $headers
        ));

        return flex\Json::stringToTree((string)$response->getBody());
    }

    public function createRequest(string $method, string $path, array $data=[], array $headers=[]): link\http\IRequest
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
            throw Glitch::{'EImplementation,ETransport,EApi'}([
                'message' => $e->getMessage(),
                'previous' => $e
            ]);
        }

        if (!$this->_isResponseOk($response)) {
            $message = $this->_extractResponseError($response);

            if ($message instanceof \Throwable) {
                throw $message;
            }

            $code = $response->getHeaders()->getStatusCode();

            if ($code >= 500) {
                throw Glitch::{'EImplementation'}([
                    'message' => $message,
                    'data' => $response->getContent(),
                    'code' => $code
                ]);
            } else {
                throw Glitch::{'EApi'}([
                    'message' => $message,
                    'data' => $this->_normalizeErrorData($response->getContent()),
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

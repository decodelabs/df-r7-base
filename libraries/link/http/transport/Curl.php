<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\http\transport;

use df;
use df\core;
use df\link;

use DecodeLabs\Glitch;
use DecodeLabs\Atlas;
use DecodeLabs\Atlas\Mode;

class Curl implements link\http\IAsyncTransport
{
    protected $_batch = [];
    protected $_multiHandle;
    protected $_multiActive = false;

    public function promiseResponse(link\http\IRequest $request, link\http\IClient $client)
    {
        return core\lang\Promise::defer(function ($input, $promise) use ($request, $client) {
            $this->_prepareRequest($request, $client);
            $handle = new Curl_Handle($request, $client, $promise);

            if (!curl_exec($handle->resource)) {
                throw Glitch::ERuntime(curl_error($handle->resource), [
                    'code' => curl_errno($handle->resource)
                ]);
            }

            curl_close($handle->resource);
            unset($handle->resource);

            $handle->response->getContentFileStream()->setPosition(0);
            return $handle->response;
        });
    }



    public function addBatchRequest(link\http\IRequest $request, link\http\IClient $client, core\lang\IPromise $promise)
    {
        $this->_prepareRequest($request, $client);
        $handle = new Curl_Handle($request, $client, $promise);

        $this->_batch[$handle->getId()] = $handle;

        if (!$this->_multiHandle) {
            $this->_multiHandle = curl_multi_init();
        }

        curl_multi_add_handle($this->_multiHandle, $handle->resource);

        return $this;
    }

    public function syncBatch(link\http\IClient $client)
    {
        while (!empty($this->_batch)) {
            if ($this->_multiActive) {
                $res = curl_multi_select($this->_multiHandle, 1);

                if ($res === -1) {
                    usleep(100);
                }
            }

            do {
                $res = curl_multi_exec($this->_multiHandle, $this->_multiActive);
            } while ($res === \CURLM_CALL_MULTI_PERFORM);

            while ($info = curl_multi_info_read($this->_multiHandle)) {
                $id = (int)$info['handle'];
                curl_multi_remove_handle($this->_multiHandle, $info['handle']);

                if (!isset($this->_batch[$id])) {
                    continue;
                }

                $handle = $this->_batch[$id];
                unset($this->_batch[$id]);

                if ($info['result']) {
                    $handle->promise->deliverError(
                        Glitch::ERuntime(
                            curl_error($handle->resource),
                            curl_errno($handle->resource)
                        )
                    );
                } else {
                    $handle->response->getContentFileStream()->setPosition(0);
                    $handle->promise->deliver($handle->response);
                }

                curl_close($handle->resource);
                unset($handle->resource);
            }

            if ($res != \CURLM_OK) {
                break;
            }
        }

        $this->_multiActive = false;
        $this->__destruct();

        return $this;
    }

    public function __destruct()
    {
        if ($this->_multiHandle) {
            curl_multi_close($this->_multiHandle);
            $this->_multiHandle = null;
        }
    }


    protected function _prepareRequest(link\http\IRequest $request, link\http\IClient $client)
    {
        $client->prepareRequest($request);
    }
}

class Curl_Handle
{
    public $resource;
    public $request;
    public $headers = [];
    public $response;
    public $promise;

    public function __construct(link\http\IRequest $request, link\http\IClient $client, core\lang\IPromise $promise)
    {
        $this->request = $request;
        $this->promise = $promise;
        $conf = $this->_createConf($client, $promise);

        $this->resource = curl_init();
        curl_setopt_array($this->resource, $conf);
    }

    public function getId(): string
    {
        return (string)((int)$this->resource);
    }

    protected function _createConf(link\http\IClient $client, core\lang\IPromise $promise)
    {
        // Basic details
        $options = $this->request->options;

        $output = [
            \CURLOPT_CUSTOMREQUEST  => strtoupper($this->request->method),
            \CURLOPT_URL            => (string)$this->request->url,
            \CURLOPT_RETURNTRANSFER => false,
            \CURLOPT_HEADER         => false,
            \CURLOPT_TIMEOUT_MS => $options->timeout !== null ? (int)($options->timeout * 1000) : 60000,
            \CURLOPT_CONNECTTIMEOUT_MS => $options->connectTimeout !== null ? (int)($options->connectTimeout * 1000) : 60000
        ];

        if (defined('CURLOPT_PROTOCOLS')) {
            $output[\CURLOPT_PROTOCOLS] = \CURLPROTO_HTTP | \CURLPROTO_HTTPS;
        }

        switch ($this->request->headers->getHttpVersion()) {
            case 2.0:
                $output[\CURLOPT_HTTP_VERSION] = \CURL_HTTP_VERSION_2_0;
                break;

            case 1.1:
                $output[\CURLOPT_HTTP_VERSION] = \CURL_HTTP_VERSION_1_1;
                break;

            default:
                $output[\CURLOPT_HTTP_VERSION] = \CURL_HTTP_VERSION_1_0;
        }


        // Method specific
        if ($this->request->isMethod('head')) {
            $output[\CURLOPT_NOBODY] = true;
        }


        // Redirects
        if ($options->maxRedirects) {
            $output[\CURLOPT_FOLLOWLOCATION] = true;
            $output[\CURLOPT_MAXREDIRS] = $options->maxRedirects;
        }

        // Auth
        if ($options->username) {
            switch ($options->authType) {
                case 'basic':
                    $output[\CURLOPT_HTTPAUTH] = \CURLAUTH_BASIC;
                    break;

                case 'digest':
                    $output[\CURLOPT_HTTPAUTH] = \CURLAUTH_DIGEST;
                    break;

                case 'ntlm':
                    $output[\CURLOPT_HTTPAUTH] = \CURLAUTH_NTLM;
                    break;

                default:
                    $output[\CURLOPT_HTTPAUTH] = \CURLAUTH_ANY;
                    break;
            }

            $output[\CURLOPT_USERPWD] = $options->username.':'.$options->password;
        }


        // SSL
        if ($options->verifySsl) {
            $output[\CURLOPT_SSL_VERIFYHOST] = 2;
            $output[\CURLOPT_SSL_VERIFYPEER] = true;
        } else {
            $output[\CURLOPT_SSL_VERIFYHOST] = 0;
            $output[\CURLOPT_SSL_VERIFYPEER] = false;
        }

        if ($options->caBundlePath) {
            $output[\CURLOPT_CAINFO] = $options->caBundlePath;
        } else {
            try {
                $output[\CURLOPT_CAINFO] = link\http\Client::getDefaultCaBundlePath();
            } catch (\Throwable $e) {
            }
        }

        if ($options->certPath) {
            $output[\CURLOPT_SSLCERT] = $options->certPath;

            if ($options->certPassword) {
                $output[\CURLOPT_SSLCERTPASSWD] = $options->certPassword;
            }
        }

        if ($options->sslKeyPath) {
            $output[\CURLOPT_SSLKEY] = $options->sslKeyPath;

            if ($options->sslKeyPassword) {
                $output[\CURLOPT_SSLKEYPASSWD] = $options->sslKeyPassword;
            }
        }


        // Body data
        $size = null;

        if ($this->request->hasBodyData()) {
            $size = $this->request->headers->get('content-length');
            $body = $this->request->getBodyDataFile();

            if (!$body->isOpen()) {
                $body->open(Mode::READ_ONLY);
            }

            $output[\CURLOPT_UPLOAD] = true;

            if ($size !== null) {
                $output[\CURLOPT_INFILESIZE] = $size;
                // remove size from headers?
            }

            $output[\CURLOPT_READFUNCTION] = function ($resource, $inFile, $length) use ($body) {
                return $body->read($length);
            };
        }


        // Headers
        foreach ($this->request->headers as $key => $value) {
            $output[\CURLOPT_HTTPHEADER][] = $key.': '.$value;
        }

        if ($this->request->isMethod('put', 'post')
        && !$this->request->headers->has('content-length')) {
            $output[\CURLOPT_HTTPHEADER][] = 'Content-Length: 0';
        }

        if (!$this->request->headers->has('accept')) {
            $output[\CURLOPT_HTTPHEADER][] = 'Accept:';
        }

        if (!$this->request->headers->has('expect')) {
            $output[\CURLOPT_HTTPHEADER][] = 'Expect:';
        }

        $output[\CURLOPT_HEADERFUNCTION] = function ($resource, $header) use ($client) {
            $length = strlen($header);
            $header = trim($header);

            if (!empty($header)) {
                $this->headers[] = $header;
            } else {
                $this->response = new link\http\response\Stream(
                    null, null,
                    link\http\response\HeaderCollection::fromResponseArray($this->headers)
                );

                $client->prepareResponse($this->response, $this->request);

                if ($this->response->isRedirect()) {
                    $this->promise->emit('redirect', [
                        'url' => $this->response->headers->get('location'),
                        'header' => $this->response->headers
                    ]);
                }

                $this->headers = null;
            }

            return $length;
        };


        if (!$this->request->isMethod('head')) {
            // Progress
            $output[\CURLOPT_NOPROGRESS] = false;
            $output[\CURLOPT_PROGRESSFUNCTION] = function (...$args) use ($promise) {
                if (is_resource($args[0])) {
                    array_shift($args);
                }

                list($totalDown, $currentDown, $totalUp, $currentUp) = $args;
                $promise->setProgress($currentDown + $currentUp, $totalDown + $totalUp);
            };

            // Response data
            $output[\CURLOPT_WRITEFUNCTION] = function ($resource, $data) {
                $this->response->getContentFileStream()->write($data);
                return strlen($data);
            };
        }

        return $output;
    }
}

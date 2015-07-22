<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\http\transport;

use df;
use df\core;
use df\link;

class Streams implements link\http\ITransport {
    
    private $_headerStack = null;

    public function promiseResponse(link\http\IRequest $request, link\http\IClient $client) {
        return core\lang\Promise::defer(function($promise) use($request, $client) {
            $maxRedirects = null;

            while(true) {
                $response = $this->_sendRequest($request, $client, $promise);

                if(!$response->isRedirect()) {
                    break;
                }

                if($maxRedirects === null) {
                    $maxRedirects = $request->options->maxRedirects;
                }

                if(!$maxRedirects) {
                    break;
                }
                
                $request = clone $request;
                $request->setUrl($url = $response->headers->get('location'));

                $promise->emit('redirect', ['url' => $url, 'header' => $response->headers]);
                $maxRedirects--;
            }

            return $response;
        });
    }

    protected function _sendRequest(link\http\IRequest $request, link\http\IClient $client, core\lang\IPromise $promise) {
        $this->_prepareRequest($request, $client);
        $stream = $this->_createStream($request, $promise);

        $response = new link\http\response\String(
            $stream, null, 
            link\http\response\HeaderCollection::fromResponseArray(
                $this->_headerStack
            )
        );

        $this->_headerStack = null;
        return $client->prepareResponse($response, $request);
    }

    protected function _prepareRequest(link\http\IRequest $request, link\http\IClient $client) {
        $client->prepareRequest($request);
        $request->headers->remove('expect');

        if($request->headers->getHttpVersion() == '1.1'
        && !$request->headers->has('connection')) {
            $request->headers->set('connection', 'close');
        }
    }

    protected function _createStream(link\http\IRequest $request, core\lang\IPromise $promise) {
        $this->_headerStack = null;
        $context = $this->_createContextOptions($request);

        $context = stream_context_create($context, [
            'notification' => function($id, $severity, $message, $code, $current, $total) use($promise, $request, &$http_response_header) {
                if($this->_headerStack === null) {
                    $this->_headerStack = &$http_response_header;
                }

                switch($id) {
                    case STREAM_NOTIFY_CONNECT:
                        $promise->emit('connect', ['request' => $request]);
                        break;

                    case STREAM_NOTIFY_AUTH_REQUIRED:
                    case STREAM_NOTIFY_AUTH_RESULT:
                        break;

                    case STREAM_NOTIFY_MIME_TYPE_IS:
                        $promise->emit('type', ['type' => $message]);
                        break;

                    case STREAM_NOTIFY_FILE_SIZE_IS:
                        //$promise->setProgress(null, $total);
                        break;

                    case STREAM_NOTIFY_REDIRECTED:
                        if($this->_headerStack) {
                            $headers = link\http\response\HeaderCollection::fromResponseArray($this->_headerStack);
                            $this->_headerStack = [];
                        } else {
                            $headers = null;
                        }

                        $promise->emit('redirect', ['url' => $message, 'headers' => $headers]);
                        break;

                    case STREAM_NOTIFY_PROGRESS:
                        $promise->setProgress($current, $total);
                        break;

                    case STREAM_NOTIFY_FAILURE:
                    case STREAM_NOTIFY_COMPLETED:
                    case STREAM_NOTIFY_RESOLVE:
                        break;
                }
            }
        ]);

        $pointer = fopen($request->url, core\fs\Mode::READ_ONLY, null, $context);
        return new core\io\Stream($pointer);
    }

    protected function _createContextOptions(link\http\IRequest $request) {
        $output = [
            'http' => [
                'method' => strtoupper($request->getMethod()),
                'header' => $request->headers->toString(),
                'protocol_version' => $request->headers->getHttpVersion(),
                'ignore_errors' => true,
                'follow_location' => 0,
                'max_redirects' => 0
            ],
            'ssl' => [
                'verify_peer' => $request->options->verifySsl,
                'allow_self_signed' => $request->options->allowSelfSigned
            ]
        ];

        if($request->options->timeout) {
            $output['http']['timeout'] = $request->options->timeout;
        }

        $body = $request->getBodyData();

        if(strlen($body)) {
            $output['http']['content'];

            if(!$request->headers->has('content-type')) {
                $output['http']['header'] .= 'Content-Type:';
            }
        }
        
        if($request->options->verifySsl) {
            if($request->options->caBundlePath) {
                $output['ssl']['cafile'] = $request->options->caBundlePath;
            } else if(PHP_VERSION_ID < 50600) {
                $output['ssl']['cafile'] = link\http\Client::getDefaultCaBundlePath();
            }
        }

        if($request->options->certPath) {
            $output['ssl']['local_cert'] = $request->options->certPath;

            if($request->options->certPassword !== null) {
                $output['ssl']['passphrase'] = $request->options->certPassword;
            }
        }

        // TODO: add proxy

        return $output;
    }
}
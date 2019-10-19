<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\http\transport;

use df;
use df\core;
use df\link;

use DecodeLabs\Atlas;
use DecodeLabs\Atlas\Mode;

class Stream implements link\http\ITransport
{
    private $_headerStack = null;
    private $_headers;

    public function promiseResponse(link\http\IRequest $request, link\http\IClient $client)
    {
        return core\lang\Promise::defer(function ($input, $promise) use ($request, $client) {
            $maxRedirects = null;

            while (true) {
                $response = $this->_sendRequest($request, $client, $promise);

                if (!$response->isRedirect()) {
                    break;
                }

                if ($maxRedirects === null) {
                    $maxRedirects = $request->options->maxRedirects;
                }

                if (!$maxRedirects) {
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

    protected function _sendRequest(link\http\IRequest $request, link\http\IClient $client, core\lang\IPromise $promise)
    {
        $this->_prepareRequest($request, $client);
        $stream = $this->_createStream($request, $promise);

        $response = new link\http\response\Stream(
            null,
            null,
            $this->_headers
        );

        $client->prepareResponse($response, $request);
        $this->_headerStack = null;

        $response->transferContentFileStream($stream);
        $stream->close();

        return $response;
    }

    protected function _prepareRequest(link\http\IRequest $request, link\http\IClient $client)
    {
        $client->prepareRequest($request);
        $request->headers->remove('expect');

        if ($request->headers->getHttpVersion() == '1.1'
        && !$request->headers->has('connection')) {
            $request->headers->set('connection', 'close');
        }
    }

    protected function _createStream(link\http\IRequest $request, core\lang\IPromise $promise)
    {
        $this->_headerStack = null;
        $context = $this->_createContextOptions($request);

        $context = stream_context_create($context, [
            'notification' => function ($id, $severity, $message, $code, $current, $total) use ($promise, $request, &$http_response_header) {
                if ($this->_headerStack === null) {
                    $this->_headerStack = &$http_response_header;
                }

                switch ($id) {
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
                        if ($this->_headerStack) {
                            $this->_headers = link\http\response\HeaderCollection::fromResponseArray($this->_headerStack);
                            $this->_headerStack = [];
                        } else {
                            $this->_headers = null;
                        }

                        $promise->emit('redirect', ['url' => $message, 'headers' => $this->_headers]);
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

        $pointer = fopen($request->url, Mode::READ_ONLY, null, $context);
        $this->_headers = link\http\response\HeaderCollection::fromResponseArray($this->_headerStack);

        if ($this->_headers->get('transfer-encoding') == 'chunked') {
            stream_filter_append($pointer, 'Streams_ChunkFilter', STREAM_FILTER_READ);
        }

        return new core\io\Stream($pointer);
    }

    protected function _createContextOptions(link\http\IRequest $request)
    {
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
                'verify_peer_name' => $request->options->verifySsl,
                'allow_self_signed' => $request->options->allowSelfSigned
            ]
        ];

        if ($request->options->timeout) {
            $output['http']['timeout'] = $request->options->timeout;
        }

        $body = $request->getBodyDataString();

        if (strlen($body)) {
            $output['http']['content'] = $body;

            if (!$request->headers->has('content-type')) {
                $output['http']['header'] .= 'Content-Type:';
            }
        }

        if ($request->options->verifySsl) {
            if ($request->options->caBundlePath) {
                $output['ssl']['cafile'] = $request->options->caBundlePath;
            } elseif (PHP_VERSION_ID < 50600) {
                $output['ssl']['cafile'] = link\http\Client::getDefaultCaBundlePath();
            }
        }

        if ($request->options->certPath) {
            $output['ssl']['local_cert'] = $request->options->certPath;

            if ($request->options->certPassword !== null) {
                $output['ssl']['passphrase'] = $request->options->certPassword;
            }
        }

        // TODO: add proxy

        return $output;
    }
}


class Streams_ChunkFilter extends \php_user_filter
{
    protected $_remaining = 0;

    public function filter($in, $out, &$consumed, $closing)
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            $outbuffer = '';
            $offset = 0;

            while ($offset < $bucket->datalen) {
                if ($this->_remaining === 0) {
                    $firstLine = strpos($bucket->data, "\r\n", $offset);
                    $descriptor = substr($bucket->data, $offset, $firstLine - $offset);
                    $length = current(explode(';', $descriptor, 2));
                    $length = trim($length);

                    if (!ctype_xdigit($length)) {
                        return \PSFS_ERR_FATAL;
                    }

                    $this->_remaining = hexdec($length);
                    $offset = $firstLine + 2;

                    if ($this->_remaining === 0) {
                        break;
                    }
                }

                $nibble = substr($bucket->data, $offset, $this->_remaining);
                $size = strlen($nibble);
                $offset += $size;

                if ($size === $this->_remaining) {
                    $offset += 2;
                }

                $this->_remaining -= $size;
                $outbuffer .= $nibble;
            }

            $consumed += $bucket->datalen;
            $bucket->data = $outbuffer;
            stream_bucket_append($out, $bucket);
        }

        return \PSFS_PASS_ON;
    }
}

stream_filter_register('Streams_ChunkFilter', 'df\\link\\http\\transport\\Streams_ChunkFilter');

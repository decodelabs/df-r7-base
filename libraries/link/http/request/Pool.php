<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\http\request;

use df;
use df\core;
use df\link;

class Pool implements link\http\IRequestPool
{
    const MAX_SIZE = 50;

    protected $_batchSize = 10;
    protected $_client;

    protected $_entries = [];

    public function __construct(link\http\IClient $client)
    {
        $this->_client = $client;
    }

    public function getClient()
    {
        return $this->_client;
    }

    public function getTransport()
    {
        return $this->_client->getTransport();
    }


    public function setBatchSize($size)
    {
        $size = (int)$size;

        if ($size < 2) {
            $size = 2;
        }

        $this->_batchSize = $size;
        return $this;
    }

    public function getBatchSize()
    {
        return $this->_batchSize;
    }




    public function get($url, $callback=null)
    {
        return $this->promiseResponse(
            $this->newGetRequest($url, $callback)
        );
    }

    public function getFile($url, $destination, $fileName=null, $callback=null)
    {
        return $this->promiseResponse(
            $this->newGetFileRequest($url, $destination, $fileName, $callback)
        );
    }

    public function post($url, $data, $callback=null)
    {
        return $this->promiseResponse(
            $this->newPostRequest($url, $data, $callback)
        );
    }

    public function put($url, $data, $callback=null)
    {
        return $this->promiseResponse(
            $this->newPutRequest($url, $data, $callback)
        );
    }

    public function delete($url, $callback=null)
    {
        return $this->promiseResponse(
            $this->newDeleteRequest($url, $callback)
        );
    }

    public function head($url, $callback=null)
    {
        return $this->promiseResponse(
            $this->newHeadRequest($url, $callback)
        );
    }

    public function options($url, $callback=null)
    {
        return $this->promiseResponse(
            $this->newOptionsRequest($url, $callback)
        );
    }

    public function patch($url, $data, $callback=null)
    {
        return $this->promiseResponse(
            $this->newPatchRequest($url, $data, $callback)
        );
    }



    public function newRequest($url, $method='get', $callback=null, $body=null)
    {
        return $this->_client->newRequest($url, $method, $callback, $body);
    }

    public function newGetRequest($url, $callback=null)
    {
        return $this->_client->newGetRequest($url, $callback);
    }

    public function newGetFileRequest($url, $destination, $fileName=null, $callback=null)
    {
        return $this->_client->newGetFileRequest($url, $destination, $fileName, $callback);
    }

    public function newPostRequest($url, $data, $callback=null)
    {
        return $this->_client->newPostRequest($url, $data, $callback);
    }

    public function newPutRequest($url, $data, $callback=null)
    {
        return $this->_client->newPutRequest($url, $data, $callback);
    }

    public function newDeleteRequest($url, $callback=null)
    {
        return $this->_client->newDeleteRequest($url, $callback);
    }

    public function newHeadRequest($url, $callback=null)
    {
        return $this->_client->newHeadRequest($url, $callback);
    }

    public function newOptionsRequest($url, $callback=null)
    {
        return $this->_client->newOptionsRequest($url, $callback);
    }

    public function newPatchRequest($url, $data, $callback=null)
    {
        return $this->_client->newPatchRequest($url, $data, $callback);
    }




    public function promiseResponse(link\http\IRequest $request)
    {
        if (count($this->_entries) == self::MAX_SIZE) {
            $this->syncBatch();
        }

        $this->_entries[] = [
            'request' => $request,
            'promise' => $promise = core\lang\Promise::defer(function () use ($request) {
                return $this->_client->promiseResponse($request);
            })
        ];

        return $promise;
    }


    public function sync()
    {
        while (!empty($this->_entries)) {
            $this->syncBatch();
        }

        return $this;
    }

    public function syncBatch()
    {
        $size = $this->_batchSize;
        $total = count($this->_entries);

        if ($size > $total) {
            $size = $total;
        }

        $batch = [];

        for ($i = 0; $i < $size; $i++) {
            if (!$entry = array_shift($this->_entries)) {
                break;
            }

            $batch[] = $entry;
        }

        if (empty($batch)) {
            return $this;
        }

        $transport = $this->_client->getTransport();

        if ($transport instanceof link\http\IAsyncTransport) {
            foreach ($batch as $entry) {
                $transport->addBatchRequest($entry['request'], $this->_client, $entry['promise']);
            }

            $transport->syncBatch($this->_client);
        } else {
            foreach ($batch as $entry) {
                $entry['promise']->sync();
            }
        }

        return $this;
    }

    public function cancel()
    {
        while (!empty($this->_entries)) {
            $entry = array_shift($this->_entries);
            $entry['promise']->cancel();

            // TODO: cancel already running
        }

        return $this;
    }
}

<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\http\request;

use df;
use df\core;
use df\link;

class Pool implements link\http\IRequestPool {
    
    const MAX_SIZE = 50;

    protected $_batchSize = 10;
    protected $_client;

    protected $_promises = [];

    public function __construct(link\http\IClient $client) {
        $this->_client = $client;
    }

    public function getClient() {
        return $this->_client;
    }

    public function getTransport() {
        return $this->_client->getTransport();
    }


    public function setBatchSize($size) {
        $size = (int)$size;

        if($size < 2) {
            $size = 2;
        }

        $this->_batchSize = $size;
        return $this;
    }

    public function getBatchSize() {
        return $this->_batchSize;
    }




    public function get($url, $headers=null, $cookies=null) {
        return $this->_enqueue(function() use($url, $headers, $cookies) {
            return $this->_client->promise($url, $headers, $cookies);
        });
    }

    public function getFile($url, $destination, $fileName=null, $headers=null, $cookies=null) {
        return $this->_enqueue(function() use($url, $destination, $fileName, $headers, $cookies) {
            return $this->_client->promiseFile($url, $destination, $fileName, $headers, $cookies);
        });
    }

    public function post($url, $data, $headers=null, $cookies=null) {
        return $this->_enqueue(function() use($url, $data, $headers, $cookies) {
            return $this->_client->promisePost($url, $data, $headers, $cookies);
        });
    }

    public function put($url, $data, $headers=null, $cookies=null) {
        return $this->_enqueue(function() use($url, $data, $headers, $cookies) {
            return $this->_client->promisePut($url, $data, $headers, $cookies);
        });
    }

    public function delete($url, $headers=null, $cookies=null) {
        return $this->_enqueue(function() use($url, $headers, $cookies) {
            return $this->_client->promiseDelete($url, $headers, $cookies);
        });
    }

    public function head($url, $headers=null, $cookies=null) {
        return $this->_enqueue(function() use($url, $headers, $cookies) {
            return $this->_client->promiseHead($url, $headers, $cookies);
        });
    }

    public function options($url, $headers=null, $cookies=null) {
        return $this->_enqueue(function() use($url, $headers, $cookies) {
            return $this->_client->promiseOptions($url, $headers, $cookies);
        });
    }

    public function patch($url, $data, $headers=null, $cookies=null) {
        return $this->_enqueue(function() use($url, $data, $headers, $cookies) {
            return $this->_client->promisePatch($url, $data, $headers, $cookies);
        });
    }


    public function newRequest($url, $method='get', $headers=null, $cookies=null, $body=null) {
        return $this->_client->newRequest($url, $method, $headers, $cookies, $body);
    }



    public function promiseResponse(link\http\IRequest $request) {
        return $this->_enqueue(function() use($request) {
            return $this->_client->promiseResponse($request);
        });
    }

    protected function _enqueue(Callable $factory) {
        if(count($this->_promises) == self::MAX_SIZE) {
            $this->sync();
        }

        return $this->_promises[] = core\lang\Promise::defer($factory);
    }


    public function sync() {
        $size = $this->_batchSize;
        $total = count($this->_promises);

        if($size > $total) {
            $size = $total;
        }

        for($i = 0; $i < $size; $i++) {
            $this->_syncPromise();
        }

        return $this;
    }

    public function cancel() {
        while(!empty($this->_promises)) {
            $promise = array_shift($this->_promises);
            $promise->cancel();

            // TODO: cancel already running
        }

        return $this;
    }

    protected function _syncPromise() {
        if(empty($this->_promises)) {
            return;
        }

        $promise = array_shift($this->_promises);

        $promise->then(function($response) {
            $this->_syncPromise();
        })->begin();
    }
}
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
    
    protected $_client;

    public function __construct(link\http\IClient $client) {
        $this->_client = $client;
    }

    public function getTransport() {
        return $this->_client->getTransport();
    }
}
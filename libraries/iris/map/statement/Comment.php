<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\iris\map\statement;

use df;
use df\core;
use df\iris;
    
class Comment extends iris\map\Node implements iris\map\IStatement, core\IDumpable {

    protected $_body;

    public function __construct(iris\lexer\ILocationProvider $locationProvider, $body) {
        parent::__construct($locationProvider);

        $this->setBody($body);
    }

    public function setBody($body) {
        $this->_body = (string)$body;
        return $this;
    }

    public function getBody() {
        return $this->_body;
    }


// Dump
    public function getDumpProperties() {
        return trim($this->_body);
    }
}
<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\mail\amazonSes;

use df;
use df\core;
use df\spur;
use df\flow;
    

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class LogicException extends \LogicException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}

class ApiException extends RuntimeException implements core\IDumpable {

    protected $_type;
    protected $_apiCode;
    protected $_requestId;

    public function __construct(\SimpleXMLElement $xml) {
        $this->_type = (string)$xml->Error->Type;
        $this->_apiCode = (string)$xml->Error->Code;
        $this->_requestId = (string)$xml->RequestId;

        parent::__construct((string)$xml->Error->Message, 500);
    }

    public function getType() {
        return $this->_type;
    }

    public function getApiCode() {
        return $this->_apiCode;
    }

    public function getRequestId() {
        return $this->_requestId;
    }

// Dump
    public function getDumpProperties() {
        return [
            'type' => $this->_type,
            'code' => $this->_apiCode,
            'requestId' => $this->_requestId
        ];
    }
}


// Interfaces
interface IMediator {
    public function getHttpClient();

    public function setUrl($url);
    public function getUrl();
    public function setAccessKey($key);
    public function getAccessKey();
    public function setSecretKey($key);
    public function getSecretKey();

    public function getVerifiedAddresses();
    public function deleteVerifiedAddress($address);
    public function getSendQuota();
    public function getSendStatistics();

    public function sendMessage(flow\mail\IMessage $message);
    public function sendRawMessage(flow\mail\IMessage $message);

    public function callServer($method, array $data=[]);
}
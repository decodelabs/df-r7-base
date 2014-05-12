<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\cdn\amazonS3;

use df;
use df\core;
use df\spur;
use df\link;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}

class ApiException extends RuntimeException {

    public $apiCode;

    public function __construct($apiCode, $message, $httpCode=500) {
        $this->apiCode = $apiCode;
        parent::__construct($message, $httpCode);
    }

    public function getApiCode() {
        return $this->apiCode;
    }
}


// Interfaces
interface IMediator {
    public function getHttpClient();
    public function setAccessKey($key);
    public function getAccessKey();
    public function setSecretKey($key);
    public function getSecretKey();
    public function shouldUseSsl($flag=null);

    public function getBucketList();


    public function callServer(link\http\IRequest $request);
}

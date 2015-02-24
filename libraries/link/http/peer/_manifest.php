<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\http\peer;

use df;
use df\core;
use df\link;
use df\halo;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}
class UnexpectedValueException extends \UnexpectedValueException implements IException {}


// Interfaces
interface IClient extends link\peer\IClient {
    public function shouldFollowRedirects($flag=null);
    public function setMaxRetries($retries);
    public function getMaxRetries();

    public function addRequest($request, $callback);
    public function sendRequest($request);

    public function get($url, $headers=null, $cookies=null);
    public function getFile($url, $file, $headers=null, $cookies=null);
    public function post($url, $data, $headers=null, $cookies=null);
}
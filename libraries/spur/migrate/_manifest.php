<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\migrate;

use df;
use df\core;
use df\spur;
use df\link;
    

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}



// Interfaces
interface IHandler {
    public function getKey();
    public function getUrl();
    public function getRouter();

    public function setAsyncBatchLimit($limit);
    public function getAsyncBatchLimit();

    public function createRequest($method, $request, array $data=null, $responseFilePath=null);
    public function call(link\http\IRequest $request);
    public function callAsync(link\http\IRequest $request, $callback);
    public function sync();
}
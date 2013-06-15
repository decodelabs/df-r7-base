<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\mail\mailChimp;

use df;
use df\core;
use df\spur;

    
// Exceptions
interface IException {}
class BadMethodCallException extends \BadMethodCallException implements IException {}
class RuntimeException extends \RuntimeException implements IException {}


// Interfaces
interface IMediator {
    public function getHttpClient();
    public function isSecure($flag=null);

// Api key
    public function setApiKey($key);
    public function getApiKey();
    public function getDataCenterId();

// IO
    public function __call($method, array $args);
    public function callServer($method, array $args=array());
}
<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\protocol\pop3;

use df;
use df\core;
use df\halo;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class LogicException extends \LogicException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}
class UnexpectedValueException extends \UnexpectedValueException implements IException {}


// Interfaces    
interface IMediator {

    public function connect($dsn);
    public function sendRequest($request, $multiLine=false);

    public function getCapabilities();
    public function getStatus();
    public function login($user, $password);
    public function getSizeList();
    public function getUniqueIdList();
    public function getTop($key, $lines=0);
    public function getSize($key);
    public function getUniqueId($key);
    public function getMail($key);
    public function deleteMail($key);
    public function rollback();
    public function noOp();
    public function quit();
}

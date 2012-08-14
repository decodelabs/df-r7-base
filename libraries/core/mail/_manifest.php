<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\mail;

use df;
use df\core;
    
// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}


// Interfaces
interface IAddress extends core\IStringProvider {
	public function setAddress($address);
	public function getAddress();
	public function setName($name);
	public function getName();
	public function isValid();
}

interface IMessage extends core\mime\IMultiPart {

}

interface ITransport {
	public function send(IMessage $message);
}
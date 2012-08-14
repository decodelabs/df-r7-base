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
	public function setSubject($subject);
	public function getSubject();

	public function setBodyHtml($content);
	public function getBodyHtml();
	public function setBodyText($content);
	public function getBodyText();

	public function isPrivate($flag=null);

	public function setFromAddress($address, $name=null);
	public function getFromAddress();
	public function isFromAddressSet();
	public function isFromAddressValid();

	public function addToAddress($address, $name=null);
	public function getToAddresses();
	public function countToAddresses();
	public function hasToAddress($address);
	public function hasToAddresses();
	public function clearToAddresses();

	public function addCCAddress($address, $name=null);
	public function getCCAddresses();
	public function countCCAddresses();
	public function hasCCAddress($address);
	public function hasCCAddresses();
	public function clearCCAddresses();

	public function addBCCAddress($address, $name=null);
	public function getBCCAddresses();
	public function countBCCAddresses();
	public function hasBCCAddress($address);
	public function hasBCCAddresses();
	public function clearBCCAddresses();

	public function setReplyToAddress($address=null);
	public function getReplyToAddress();

	public function send(ITransport $transport=null);
}

interface ITransport {
	public function send(IMessage $message);
}
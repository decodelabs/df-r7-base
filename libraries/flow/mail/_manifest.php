<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flow\mail;

use df;
use df\core;
use df\flow;
    
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

interface IMessage extends flow\mime\IMultiPart {
    public function setSubject($subject);
    public function getSubject();

    public function setBodyHtml($content);
    public function getBodyHtml();
    public function setBodyText($content);
    public function getBodyText();

    public function addFileAttachment($path, $fileName=null, $contentType=null);
    public function addStringAttachment($string, $fileName, $contentType=null);

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

    public function setReturnPath($address=null);
    public function getReturnPath();

    public function setJournalName($name);
    public function getJournalName();
    public function setJournalDuration(core\time\IDuration $duration=null);
    public function getJournalDuration();
    public function shouldJournal($flag=null);

    public function send(ITransport $transport=null);
}

interface ITransport {
    public static function getName();
    public static function getDescription();
    public function send(IMessage $message);
}



interface IMailModel {
    public function captureMail(IMessage $message);
    public function journalMail(IMessage $message);
}

interface IMailRecord {
    public function getId();
    public function getFromAddress();
    public function getToAddresses();
    public function getSubject();
    public function getBodyString();
    public function getDate();
    public function isPrivate();
    public function toMessage();
}
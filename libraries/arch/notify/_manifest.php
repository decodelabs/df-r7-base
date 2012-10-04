<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\notify;

use df;
use df\core;
use df\arch;
use df\user;
    

// Exceptions
interface IException extends arch\IException {}
class RuntimeException extends \RuntimeException implements IException {}


// Interfaces
interface IManager extends core\IApplicationAware, core\IRegistryObject {

    public function setMessageLimit($limit);
    public function getMessageLimit();

    public function newMessage($id, $message=null, $type=null);
    public function flushQueue();
    public function isFlushed();

// Constant
    public function setConstantMessage(IMessage $message);
    public function getConstantMessage($id);
    public function getConstantMessages();
    public function removeConstantMessage($id);
    public function clearConstantMessages();
    
// Queued
    public function queueMessage(IMessage $message, $instantIfSpace=false);
    public function setInstantMessage(IMessage $message);
    public function getInstantMessages();
    public function removeQueuedMessage($id);
}

interface IQueue {}


class Queue implements IQueue {

    public $constant = array();
    public $queued = array();
    public $instant = array();
}


interface IMessage {
    const INFO = 'info';
    const SUCCESS = 'success';
    const ERROR = 'error';
    const WARNING = 'warning';
    const DEBUG = 'debug';

    public function getId();
    public function setType($type);
    public function getType();
    public function isDebug();

    public function isDisplayed($flag=null);
    public function setMessage($message);
    public function getMessage();
    public function setDescription($description);
    public function getDescription();
    
    public function setLink($link, $text=null);
    public function getLink();
    public function setLinkText($text);
    public function getLinkText();
    public function clearLink();
}
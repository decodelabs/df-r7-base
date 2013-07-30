<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flow;

use df;
use df\core;
use df\flow;


    
// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}



// Interfaces
interface IManager extends core\IManager {

// Notification
    public function newNotification($subject, $body, $to=null, $from=null);
    public function sendNotification(INotification $notification);

// Flash
    public function setFlashLimit($limit);
    public function getFlashLimit();

    public function newFlashMessage($id, $message=null, $type=null);
    public function processFlashQueue();

    public function flash($id, $message=null, $type=null);
    public function flashNow($id, $message=null, $type=null);
    public function flashAlways($id, $message=null, $type=null);

    public function addConstantFlash(IFlashMessage $message);
    public function getConstantFlash($id);
    public function getConstantFlashes();
    public function removeConstantFlash($id);
    public function clearConstantFlashes();
    
    public function queueFlash(IFlashMessage $message, $instantIfSpace=false);
    public function addInstantFlash(IFlashMessage $message);
    public function getInstantFlashes();
    public function removeQueuedFlash($id);
}


interface IFlashMessage {

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



class FlashQueue {
    public $limit = 15;
    public $constant = array();
    public $queued = array();
    public $instant = array();
}


interface INotification {

    public function setSubject($subject);
    public function getSubject();
    public function setBody($body);
    public function getBody();

    public function setTo($to);
    public function addTo($to);
    public function addToEmail($email);
    public function getToEmails();
    public function clearToEmails();
    public function addToUser($id);
    public function getToUsers();
    public function clearToUsers();
    public function clearTo();

    public function setFromEmail($email=null);
    public function getFromEmail();
}
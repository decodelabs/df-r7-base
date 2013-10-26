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



class FlashQueue implements \Serializable {

    public $limit = 15;
    public $constant = array();
    public $queued = array();
    public $instant = array();

    public function serialize() {
        $data = ['l' => $this->limit];

        if(!empty($this->constant)) {
            $data['c'] = $this->constant;
        }

        if(!empty($this->queued)) {
            $data['q'] = $this->queued;
        }

        if(!empty($this->instant)) {
            $data['i'] = $this->instant;
        }

        return serialize($data);
    }

    public function unserialize($data) {
        $data = unserialize($data);
        $this->limit = $data['l'];

        if(isset($data['c'])) {
            $this->constant = $data['c'];
        }

        if(isset($data['q'])) {
            $this->queued = $data['q'];
        }

        if(isset($data['i'])) {
            $this->instant = $data['i'];
        }
    }
}


interface INotification {

    public function setSubject($subject);
    public function getSubject();
    public function setBody($body);
    public function getBody();

    public function setTo($to);
    public function addTo($to);
    public function clearTo();
    public function hasRecipients();
    public function shouldFilterClient($flag=null);

    public function addToEmail($email, $name=null);
    public function getToEmails();
    public function removeToEmail($email);
    public function clearToEmails();
    
    public function addToUser($id);
    public function getToUsers();
    public function getToUserIds();
    public function removeToUser($id);
    public function clearToUsers();

    public function setFromEmail($email=null, $name=null);
    public function getFromEmail();
}
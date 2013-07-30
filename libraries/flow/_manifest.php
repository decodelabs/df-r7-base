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
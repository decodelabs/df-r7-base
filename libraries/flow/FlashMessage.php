<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flow;

use df;
use df\core;
use df\flow;
use df\user;
    
class FlashMessage implements IFlashMessage {

    protected $_id;
    protected $_isDisplayed = false;
    protected $_displayCount = 0;
    protected $_type = IFlashMessage::INFO;
    protected $_message;
    protected $_description;
    protected $_link;
    protected $_linkText;

    public static function factory($id, $message=null, $type=null) {
        if($id instanceof IFlashMessage) {
            return $id;
        } else if($message instanceof IFlashMessage) {
            return $message;
        }

        return new self($id, $message, $type);
    }

    public function __construct($id, $message=null, $type=null) {
        if($message === null) {
            $message = $id;
            $id = null;
        }

        if($id === null) {
            $id = md5($message);
        }

        $this->_id = $id;
        $this->setMessage($message);

        if($type !== null) {
            $this->setType($type);
        }
    }

    public function getId() {
        return $this->_id;
    }

    public function setType($type) {
        switch($type) {
            case IFlashMessage::INFO:
            case IFlashMessage::SUCCESS:
            case IFlashMessage::ERROR:
            case IFlashMessage::WARNING:
            case IFlashMessage::DEBUG:
                break;

            default: 
                $type = IFlashMessage::INFO;
                break;
        }

        $this->_type = $type;
        return $this;
    }

    public function getType() {
        return $this->_type;
    }

    public function isDebug() {
        return $this->_type === IFlashMessage::DEBUG;
    }


    public function isDisplayed($flag=null) {
        if($flag !== null) {
            $this->_isDisplayed = (bool)$flag;
            return $this;
        }
        
        return $this->_isDisplayed;
    }
    
    public function canDisplayAgain() {
        return $this->_displayCount > 0;
    }
    
    public function resetDisplayState() {
        $this->_isDisplayed = false;
        $this->_displayCount--;
        return $this;
    }
    
    public function setDisplayCount($count) {
        $this->_displayCount = (int)$count;
        return $this;
    }
    
    public function getDisplayCount() {
        return $this->_displayCount;
    }
    
    public function setMessage($message) {
        $this->_message = trim((string)$message);
        return $this;
    }
    
    public function getMessage() {
        return $this->_message;
    }
    
    public function setDescription($description) {
        $this->_description = trim((string)$description);
        return $this;
    }
    
    public function getDescription() {
        return $this->_description;
    }

    public function setLink($link, $text=null) {
        if($link === null) {
            $this->_link = null;
        } else {
            $this->_link = $link;
        }
        
        $this->_linkText = $text;
        return $this;
    }
    
    public function getLink() {
        return $this->_link;
    }
    
    public function setLinkText($text) {
        $this->_linkText = $text;
        return $this;
    }
    
    public function getLinkText() {
        return $this->_linkText;
    }
    
    public function hasLink() {
        return $this->_link !== null;
    }
    
    public function clearLink() {
        $this->_link = null;
        $this->_linkText = null;
        return $this;
    }
}
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
use df\flex;
    
class Notification implements INotification {

    protected $_subject;
    protected $_body;
    protected $_bodyType = INotification::SIMPLE_TAGS;
    protected $_toEmails = [];
    protected $_toUsers = [];
    protected $_toAdmin = false;
    protected $_from;
    protected $_filterClient = false;
    protected $_isPrivate = false;
    protected $_journalName;
    protected $_journalDuration;
    protected $_journalObjectId1;
    protected $_journalObjectId2;
    protected $_shouldJournal = true;
    protected $_forceSend = false;

    public function __construct($subject, $body, $to=null, $from=null, $forceSend=false) {
        $this->setSubject($subject);
        $this->setBody($body);

        if($to === true) {
            $this->_toAdmin = true;
        } else if($to !== null) {
            $this->addTo($to);
        }

        if($from !== null) {
            $this->setFromEmail($from);
        }

        $this->_forceSend = (bool)$forceSend;
    }


// Subject
    public function setSubject($subject) {
        $this->_subject = $subject;
        return $this;
    }

    public function getSubject() {
        return $this->_subject;
    }


// Body
    public function setBody($body) {
        $this->_body = $body;
        return $this;
    }

    public function getBody() {
        return $this->_body;
    }

    public function setBodyType($type) {
        switch($type) {
            case INotification::TEXT:
            case INotification::SIMPLE_TAGS:
            case INotification::HTML:
                $this->_bodyType = $type;
                break;

            default:
                $this->_bodyType = INotification::SIMPLE_TAGS;
                break;
        }

        return $this;
    }

    public function getBodyType() {
        return $this->_bodyType;
    }

    public function getBodyHtml() {
        switch($this->_bodyType) {
            case INotification::TEXT:
                $text = htmlspecialchars((string)$this->_body, ENT_QUOTES, 'UTF-8');
                $text = str_replace("\n", "\n".'<br />', $text);
                return $text;

            case INotification::SIMPLE_TAGS:
                $parser = new flex\simpleTags\Parser($this->_body);
                return $parser->toHtml();
            
            case INotification::HTML:
                return $this->_body;
        }
    }


// To
    public function shouldSendToAdmin($flag=null) {
        if($flag !== null) {
            $this->_toAdmin = (bool)$flag;
            return $this;
        }

        return $this->_toAdmin;
    }

    public function setTo($to) {
        $this->clearTo();
        return $this->addTo($to);
    }

    public function addTo($to) {
        if(!is_array($to) || isset($to['id'])) {
            $to = [$to];
        }

        foreach($to as $user) {
            if(is_numeric($user)) {
                $this->addToUser((int)$user);
                continue;
            }

            if(is_string($user)) {
                $user = flow\mail\Address::fromString($user);
            }

            if($user instanceof flow\mail\IAddress) {
                $this->addToEmail($user);
                continue;
            }

            if($user instanceof user\IClientDataObject) {
                $this->addToUser($user);
                continue;
            }

            if(is_array($user) || $user instanceof \ArrayAccess) {
                if(isset($user['id'])) {
                    $this->addToUser((int)$user['id']);
                    continue;
                } else if(isset($user['email'])) {
                    $this->addToEmail(flow\mail\Address::fromString($user['email']));
                    continue;
                }
            }
        }

        return $this;
    }

    public function clearTo() {
        return $this->clearToEmails()->clearToUsers();
    }

    public function hasRecipients() {
        return !empty($this->_toEmails) || !empty($this->_toUsers);
    }

    public function shouldFilterClient($flag=null) {
        if($flag !== null) {
            $this->_filterClient = (bool)$flag;
            return $this;
        }

        return $this->_filterClient;
    }

    public function shouldForceSend($flag=null) {
        if($flag !== null) {
            $this->_forceSend = (bool)$flag;
            return $this;
        }

        return $this->_forceSend;
    }



    public function addToEmail($email, $name=null) {
        $email = flow\mail\Address::factory($email, null);
        $this->_toEmails[$email->getAddress()] = $email->getName();
        return $this;
    }

    public function getToEmails() {
        return $this->_toEmails;
    }   

    public function removeToEmail($email) {
        $email = flow\mail\Address::factory($email);
        unset($this->_toEmails[$email->getAddress()]);
        return $this;
    }

    public function clearToEmails() {
        $this->_toEmails = [];
        return $this;
    }

    public function addToUser($id) {
        if($id instanceof user\IClientDataObject) {
            $this->_toUsers[$id->getId()] = $id;
        } else {
            if(is_array($id) || $id instanceof \ArrayAccess) {
                $id = $id['id'];
            }

            if(is_numeric($id)) {
                $this->_toUsers[(int)$id] = null;
            }
        }

        return $this;
    }

    public function getToUsers() {
        return $this->_toUsers;
    }

    public function getToUserIds() {
        return array_keys($this->_toUsers);
    }

    public function removeToUser($id) {
        unset($this->_toUsers[(int)$id]);
        return $this;
    }

    public function clearToUsers() {
        $this->_toUsers = [];
        return $this;
    }



// From
    public function setFromEmail($email=null, $name=null) {
        if($email !== null) {
            $email = flow\mail\Address::factory($email, $name);
        }

        $this->_from = $email;
        return $this;
    }

    public function getFromEmail() {
        return $this->_from;
    }


    public function isPrivate($flag=null) {
        if($flag !== null) {
            $this->_isPrivate = (bool)$flag;
            return $this;
        }

        return $this->_isPrivate;
    }


// Journal
    public function setJournalName($name) {
        $this->_journalName = $name;
        return $this;
    }

    public function getJournalName() {
        return $this->_journalName;
    }

    public function setJournalDuration(core\time\IDuration $duration=null) {
        $this->_journalDuration = $duration;
        return $this;
    }

    public function getJournalDuration() {
        if($this->_journalDuration) {
            return $this->_journalDuration;
        }

        return core\time\Duration::fromWeeks(52);
    }

    public function setJournalObjectId1($id) {
        $this->_journalObjectId1 = $id;
        return $this;
    }

    public function getJournalObjectId1() {
        return $this->_journalObjectId1;
    }

    public function setJournalObjectId2($id) {
        $this->_journalObjectId2 = $id;
        return $this;
    }

    public function getJournalObjectId2() {
        return $this->_journalObjectId2;
    }


    public function shouldJournal($flag=null) {
        if($flag !== null) {
            $this->_shouldJournal = (bool)$flag;
            return $this;
        }

        return $this->_shouldJournal && $this->_journalName !== null;
    }
}
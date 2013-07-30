<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flow;

use df;
use df\core;
use df\flow;
    
class Notification implements INotification {

    protected $_subject;
    protected $_body;
    protected $_toEmails = array();
    protected $_toUsers = array();
    protected $_from;

    public function __construct($subject, $body, $to=null, $from=null) {
        $this->setSubject($subject);
        $this->setBody($body);

        if($to !== null) {
            $this->addTo($to);
        }

        if($from !== null) {
            $this->setFromEmail($from);
        }
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


// To
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

    public function addToEmail($email) {
        $email = flow\mail\Address::factory($email);
        $this->_toEmails[$email->getAddress()] = $email->getName();
        return $this;
    }

    public function getToEmails() {
        return $this->_toEmails;
    }   

    public function clearToEmails() {
        $this->_toEmails = array();
        return $this;
    }

    public function addToUser($id) {
        if(is_array($id) || $id instanceof \ArrayAccess) {
            $id = $id['id'];
        }

        if(is_numeric($id)) {
            $this->_toUsers[(int)$id] = null;
        }

        return $this;
    }

    public function getToUsers() {
        return array_keys($this->_toUsers);
    }

    public function clearToUsers() {
        $this->_toUsers = array();
        return $this;
    }

    public function clearTo() {
        return $this->clearToEmails()->clearToUsers();
    }

    public function hasRecipients() {
        return !empty($this->_toEmails) || !empty($this->_toUsers);
    }



// From
    public function setFromEmail($email=null) {
        $this->_from = flow\mail\Address::factory($email);
        return $this;
    }

    public function getFromEmail() {
        return $this->_from;
    }

}
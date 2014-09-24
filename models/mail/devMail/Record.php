<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\mail\devMail;

use df;
use df\core;
use df\axis;
use df\opal;
use df\flow;

class Record extends opal\record\Base implements flow\mail\IDevMailRecord {
    
    public function getId() {
        return $this['id'];
    }
    
    public function getFromAddress() {
        return flow\mail\Address::factory($this['from']);
    }

    public function getToAddresses() {
        return flow\mail\Message::parseAddressList($this['to']);
    }

    public function getSubject() {
        return $this['subject'];
    }

    public function getBodyString() {
        return $this['body'];
    }

    public function getDate() {
        return $this['date'];
    }

    public function isPrivate() {
        return $this['isPrivate'];
    }

    public function toMessage() {
        return flow\mail\Message::fromString($this['body']);
    }
}

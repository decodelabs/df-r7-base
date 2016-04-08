<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\mail\capture;

use df;
use df\core;
use df\axis;
use df\opal;
use df\flow;

class Record extends opal\record\Base implements flow\mail\IMailRecord {

    public function getId() {
        return $this['id'];
    }

    public function getFromAddress() {
        return flow\mail\Address::factory($this['from']);
    }

    public function getToAddresses() {
        return flow\mail\AddressList::factory($this['to']);
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

    public function toMessage() {
        return flow\mime\MultiPart::fromString($this['body']);
    }
}

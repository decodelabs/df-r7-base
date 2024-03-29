<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\mail\capture;

use df\flow;
use df\opal;

class Record extends opal\record\Base implements flow\mail\IMailRecord
{
    public function getId(): ?string
    {
        if (null !== ($id = $this['id'])) {
            return (string)$id;
        } else {
            return null;
        }
    }

    public function getFromAddress()
    {
        return flow\mail\Address::factory($this['from']);
    }

    public function getToAddresses()
    {
        return flow\mail\AddressList::factory($this['to']);
    }

    public function getSubject()
    {
        return $this['subject'];
    }

    public function getBodyString()
    {
        return $this['body'];
    }

    public function getDate()
    {
        return $this['date'];
    }

    public function toMessage()
    {
        return flow\mime\MultiPart::fromString($this['body']);
    }
}

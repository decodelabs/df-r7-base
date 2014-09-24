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

class Unit extends axis\unit\table\Base {
    
    protected function _onCreate(axis\schema\ISchema $schema) {
        $schema->addField('id', 'AutoId', 8);
        $schema->addField('from', 'String', 128);
        $schema->addField('to', 'BigString', opal\schema\IFieldSize::MEDIUM);
        $schema->addField('subject', 'String', 255);
        $schema->addField('body', 'BigString', opal\schema\IFieldSize::HUGE);
        $schema->addField('date', 'DateTime');
        $schema->addField('isPrivate', 'Boolean');
    }

    public function applyPagination(opal\query\IPaginator $paginator) {
        $paginator
            ->setOrderableFields('from', 'to', 'subject', 'date', 'isPrivate')
            ->setDefaultOrder('date DESC');

        return $this;
    }

    public function store(flow\mail\IMessage $message) {
        $to = [];

        foreach($message->getToAddresses() as $address) {
            $to[] = (string)$address;
        }

        foreach($message->getCCAddresses() as $address) {
            $to[] = (string)$address;
        }

        foreach($message->getBCCAddresses() as $address) {
            $to[] = (string)$address;
        }


        return $this->newRecord([
                'from' => (string)$message->getFromAddress(),
                'to' => implode(',', array_unique($to)),
                'subject' => $message->getSubject(),
                'body' => (string)$message,
                'date' => 'now',
                'isPrivate' => $message->isPrivate()
            ])
            ->save();
    }
}

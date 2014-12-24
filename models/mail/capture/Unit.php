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

class Unit extends axis\unit\table\Base {
    
    protected static $_defaultSearchFields = [
        'subject' => 10,
        'body' => 1
    ];

    protected function _onCreate(axis\schema\ISchema $schema) {
        $schema->addPrimaryField('id', 'Guid');
        $schema->addField('from', 'String', 128);
        $schema->addField('to', 'BigString', 'medium');
        $schema->addField('subject', 'String', 255);
        $schema->addField('body', 'BigString', 'huge');
        $schema->addField('isPrivate', 'Boolean');

        $schema->addField('date', 'Timestamp');
        $schema->addField('readDate', 'DateTime')
            ->isNullable(true);
        $schema->addField('environmentMode', 'Enum', 'core/EnvironmentMode')
            ->setDefaultValue('development');
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
                'isPrivate' => $message->isPrivate(),
                'environmentMode' => $this->context->application->getEnvironmentMode()
            ])
            ->save();
    }
}

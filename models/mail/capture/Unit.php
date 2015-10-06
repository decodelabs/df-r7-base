<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\mail\capture;

use df;
use df\core;
use df\axis;
use df\flow;

class Unit extends axis\unit\table\Base {

    protected $_defaultSearchFields = [
        'subject' => 10,
        'body' => 1
    ];

    protected $_defaultOrderableFields = [
        'from', 'to', 'subject', 'date', 'isPrivate', 'environmentMode'
    ];

    protected $_defaultOrder = 'date DESC';

    protected function createSchema($schema) {
        $schema->addPrimaryField('id', 'Guid');
        $schema->addField('from', 'Text', 128);
        $schema->addField('to', 'Text', 'medium');
        $schema->addField('subject', 'Text', 255);
        $schema->addField('body', 'Text', 'huge');
        $schema->addField('isPrivate', 'Boolean');

        $schema->addField('date', 'Timestamp');
        $schema->addField('readDate', 'DateTime')
            ->isNullable(true);
        $schema->addField('environmentMode', 'Enum', 'core/EnvironmentMode')
            ->setDefaultValue('development');
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

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

    const SEARCH_FIELDS = [
        'subject' => 10,
        'body' => 1
    ];

    const ORDERABLE_FIELDS = [
        'from', 'to', 'subject', 'date', 'environmentMode'
    ];

    const DEFAULT_ORDER = 'date DESC';

    protected function createSchema($schema) {
        $schema->addPrimaryField('id', 'Guid');
        $schema->addField('from', 'Text', 128);
        $schema->addField('to', 'Text', 'medium');
        $schema->addField('subject', 'Text', 255);
        $schema->addField('body', 'Text', 'huge');

        $schema->addField('date', 'Timestamp');
        $schema->addField('readDate', 'Date:Time')
            ->isNullable(true);
        $schema->addField('environmentMode', 'Enum', 'core/environment/Mode')
            ->setDefaultValue('development');
    }

    public function store(flow\mime\IMultiPart $message) {
        $headers = $message->getHeaders();
        $to = new flow\mail\AddressList();

        $to->import(
            $headers->get('to'),
            $headers->get('cc'),
            $headers->get('bcc')
        );

        $from = flow\mail\Address::factory($headers->get('from'));

        return $this->newRecord([
                'from' => (string)$from,
                'to' => (string)$to,
                'subject' => $headers->get('subject'),
                'body' => (string)$message,
                'environmentMode' => $this->context->application->getEnvironmentMode()
            ])
            ->save();
    }
}

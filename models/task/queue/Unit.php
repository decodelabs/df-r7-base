<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\task\queue;

use df;
use df\core;
use df\apex;
use df\axis;

class Unit extends axis\unit\table\Base {

    const BROADCAST_HOOK_EVENTS = false;

    const ORDERABLE_FIELDS = [
        'request', 'environmentMode', 'priority', 'queueDate', 'lockDate'
    ];

    const DEFAULT_ORDER = 'queueDate DESC';

    protected function createSchema($schema) {
        $schema->addPrimaryField('id', 'Guid');
        $schema->addField('request', 'Text', 1024);
        $schema->addField('environmentMode', 'Enum', 'core/EnvironmentMode')
            ->isNullable(true);

        $schema->addField('priority', 'Enum', 'core/unit/Priority');

        $schema->addIndexedField('queueDate', 'Timestamp');

        $schema->addField('lockDate', 'Date:Time')
            ->isNullable(true);
        $schema->addField('lockId', 'Guid')
            ->isNullable(true);
    }
}
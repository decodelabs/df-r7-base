<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\task\schedule;

use df\axis;

class Unit extends axis\unit\Table
{
    public const BROADCAST_HOOK_EVENTS = false;

    public const ORDERABLE_FIELDS = [
        'request', 'priority', 'creationDate', 'lastRun', 'isLive', 'isAuto'
    ];

    public const DEFAULT_ORDER = ['lastRun DESC', 'request ASC'];

    protected function createSchema($schema)
    {
        $schema->addPrimaryField('id', 'Guid');
        $schema->addField('request', 'Text', 1024);
        $schema->addField('priority', 'Enum', 'core/unit/Priority');

        $schema->addField('creationDate', 'Timestamp');
        $schema->addField('lastRun', 'Date:Time')
            ->isNullable(true);

        $schema->addField('minute', 'Text', 128)
            ->setDefaultValue('*');
        $schema->addField('hour', 'Text', 128)
            ->setDefaultValue('*');
        $schema->addField('day', 'Text', 128)
            ->setDefaultValue('*');
        $schema->addField('month', 'Text', 128)
            ->setDefaultValue('*');
        $schema->addField('weekday', 'Text', 128)
            ->setDefaultValue('*');

        $schema->addField('isLive', 'Boolean')
            ->setDefaultValue(true);
        $schema->addField('isAuto', 'Boolean')
            ->setDefaultValue(true);
    }
}

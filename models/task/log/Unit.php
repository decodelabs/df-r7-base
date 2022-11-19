<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\task\log;

use df\axis;

class Unit extends axis\unit\Table
{
    public const BROADCAST_HOOK_EVENTS = false;

    public const ORDERABLE_FIELDS = [
        'request', 'environmentMode', 'startDate',
        'lastActivity', 'runTime', 'status'
    ];

    public const DEFAULT_ORDER = 'startDate DESC';

    protected function createSchema($schema)
    {
        $schema->addPrimaryField('id', 'Guid');
        $schema->addField('request', 'Text', 1024);
        $schema->addField('environmentMode', 'Enum', 'core/environment/Mode');

        $schema->addField('startDate', 'Timestamp');
        $schema->addField('lastActivity', 'Date:Time')
            ->isNullable(true);

        $schema->addField('runTime', 'Duration')
            ->isNullable(true);

        $schema->addField('output', 'Text', 'huge')
            ->isNullable(true);
        $schema->addField('errorOutput', 'Text', 'huge')
            ->isNullable(true);

        $schema->addField('status', 'Enum', 'axis://task/Status')
            ->isNullable(true);
    }
}

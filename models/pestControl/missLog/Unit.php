<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\pestControl\missLog;

use df;
use df\core;
use df\apex;
use df\axis;

class Unit extends axis\unit\Table
{
    const SEARCH_FIELDS = [
        'request' => 4,
        'message' => 1
    ];

    protected function createSchema($schema)
    {
        $schema->addPrimaryField('id', 'Guid');
        $schema->addIndexedField('date', 'Timestamp');

        $schema->addField('miss', 'ManyToOne', 'miss', 'missLogs');

        $schema->addField('url', 'text', 'medium')
            ->isNullable(true);
        $schema->addField('referrer', 'Text', 'medium')
            ->isNullable(true);
        $schema->addField('message', 'Text', 'medium')
            ->isNullable(true);

        $schema->addField('userAgent', 'One', 'user/agent')
            ->isNullable(true);

        $schema->addField('user', 'One', 'user/client')
            ->isNullable(true);

        $schema->addField('isProduction', 'Boolean')
            ->setDefaultValue(true);
        $schema->addField('isArchived', 'Boolean');
    }
}

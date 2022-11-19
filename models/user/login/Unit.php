<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\models\user\login;

use df\axis;

class Unit extends axis\unit\Table
{
    public const ORDERABLE_FIELDS = [
        'date', 'identity', 'ip'
    ];

    public const DEFAULT_ORDER = 'date DESC';

    protected function createSchema($schema)
    {
        $schema->addPrimaryField('id', 'Guid');

        $schema->addField('date', 'Timestamp');

        $schema->addIndexedField('identity', 'Text', 1024);
        $schema->addField('user', 'One', 'user/client')
            ->isNullable(true);

        $schema->addField('ip', 'Text', 64);
        $schema->addField('agent', 'Text', 1024)
            ->isNullable(true);

        $schema->addField('authenticated', 'Boolean');
    }
}

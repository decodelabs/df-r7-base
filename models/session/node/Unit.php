<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\session\node;

use df\axis;

class Unit extends axis\unit\Table
{
    public const BROADCAST_HOOK_EVENTS = false;

    protected function createSchema($schema)
    {
        $schema->addField('bucket', 'Text', 255);
        $schema->addField('key', 'Text', 255);

        $schema->addIndexedField('descriptor', 'ManyToOne', 'descriptor', 'nodes');

        $schema->addField('value', 'Binary', 'huge');
        $schema->addField('creationTime', 'Timestamp');
        $schema->addField('updateTime', 'Timestamp')
            ->shouldTimestampAsDefault(false);

        $schema->addPrimaryIndex('primary', ['bucket', 'key', 'descriptor']);
    }
}

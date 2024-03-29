<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\axis\schema;

use df\axis;

class Unit extends axis\unit\Table
{
    public const BROADCAST_HOOK_EVENTS = false;

    protected function createSchema($schema)
    {
        $schema->addPrimaryField('unitId', 'Text', 255);
        $schema->addField('storeName', 'Text', 128);
        $schema->addField('version', 'Number', 2);
        $schema->addField('schema', 'Binary', 'medium');
        $schema->addIndexedField('timestamp', 'Timestamp');
    }
}

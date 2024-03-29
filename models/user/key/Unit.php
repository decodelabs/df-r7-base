<?php

namespace df\apex\models\user\key;

use df\axis;

class Unit extends axis\unit\Table
{
    protected function createSchema($schema)
    {
        $schema->addPrimaryField('id', 'Guid');
        $schema->addField('role', 'ManyToOne', 'role', 'keys');

        $schema->addIndexedField('domain', 'Text', 32);
        $schema->addField('pattern', 'Text', 128);
        $schema->addField('allow', 'Boolean')
            ->setDefaultValue(true);
    }
}

<?php

namespace df\apex\models\user\key;

use df\core;
use df\axis;

class Unit extends axis\unit\table\Base {
    
    protected function createSchema($schema) {
        $schema->addPrimaryField('id', 'Guid');
        $schema->addField('role', 'ManyToOne', 'role', 'keys');

        $schema->addIndexedField('domain', 'String', 32);
        $schema->addField('pattern', 'String', 128);
        $schema->addField('allow', 'Boolean')
            ->setDefaultValue(true);
    }
}

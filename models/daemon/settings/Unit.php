<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\daemon\settings;

use df;
use df\core;
use df\apex;
use df\axis;

class Unit extends axis\unit\table\Base {
    
    protected function createSchema($schema) {
        $schema->addPrimaryField('name', 'String', 64);
        $schema->addField('isEnabled', 'Boolean')
            ->setDefaultValue(true);
        $schema->addField('user', 'String', 64)
            ->isNullable(true);
        $schema->addField('group', 'String', 64)
            ->isNullable(true);
        $schema->addField('options', 'Json')
            ->isNullable(true);
    }
}
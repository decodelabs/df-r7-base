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

    const BROADCAST_HOOK_EVENTS = false;

    protected function createSchema($schema) {
        $schema->addPrimaryField('name', 'Text', 64);
        $schema->addField('isEnabled', 'Boolean')
            ->setDefaultValue(true);
        $schema->addField('user', 'Text', 64)
            ->isNullable(true);
        $schema->addField('group', 'Text', 64)
            ->isNullable(true);
        $schema->addField('options', 'Json')
            ->isNullable(true);
    }
}
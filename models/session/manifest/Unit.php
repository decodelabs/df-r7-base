<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\session\manifest;

use df;
use df\core;
use df\apex;
use df\axis;
use df\user;

class Unit extends axis\unit\table\Base {

    const BROADCAST_HOOK_EVENTS = false;

    protected function createSchema($schema) {
        $schema->addPrimaryField('internalId', 'Binary', 20)
            ->isConstantLength(true);
        $schema->addUniqueField('externalId', 'Binary', 20)
            ->isConstantLength(true);
        $schema->addUniqueField('transitionId', 'Binary', 20)
            ->isConstantLength(true)
            ->isNullable(true);
        $schema->addField('userId', 'Number', 8)
            ->isNullable(true);

        $schema->addField('startTime', 'Number');
        $schema->addField('transitionTime', 'Number', 8)
            ->isNullable(true);
        $schema->addIndexedField('accessTime', 'Number', 8);
        //$schema->addField('userId', 'Text', 64)->isNullable(true);
    }
}
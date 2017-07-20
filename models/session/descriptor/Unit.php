<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\session\descriptor;

use df;
use df\core;
use df\apex;
use df\axis;
use df\user;

class Unit extends axis\unit\Table {

    const BROADCAST_HOOK_EVENTS = false;

    protected function createSchema($schema) {
        $schema->addPrimaryField('id', 'Binary', 20)
            ->isConstantLength(true);

        $schema->addUniqueField('publicKey', 'Binary', 20)
            ->isConstantLength(true);
        $schema->addUniqueField('transitionKey', 'Binary', 20)
            ->isConstantLength(true)
            ->isNullable(true);

        $schema->addField('user', 'One', 'user/client')
            ->isNullable(true);

        $schema->addField('nodes', 'OneToMany', 'node', 'descriptor');

        $schema->addField('startTime', 'Timestamp');
        $schema->addField('transitionTime', 'Timestamp')
            ->isNullable(true)
            ->shouldTimestampAsDefault(false);
        $schema->addIndexedField('accessTime', 'Timestamp')
            ->shouldTimestampAsDefault(false);
    }
}
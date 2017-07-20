<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\session\stub;

use df;
use df\core;
use df\apex;
use df\axis;
use df\flex;

class Unit extends axis\unit\Table {

    const BROADCAST_HOOK_EVENTS = false;

    protected function createSchema($schema) {
        $schema->addPrimaryField('key', 'Binary', 20);
        $schema->addField('sessionId', 'Binary', 20)
            ->isNullable(true);
        $schema->addField('date', 'Timestamp');
    }

    public function generateKey() {
        $key = flex\Generator::sessionId(true);
        $this->insert(['key' => $key])->execute();
        return $key;
    }
}
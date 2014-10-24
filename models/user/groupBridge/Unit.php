<?php

namespace df\apex\models\user\groupBridge;

use df\core;
use df\axis;

class Unit extends axis\unit\table\ManyBridge {
    
    const DOMINANT_UNIT = 'client';
    const DOMINANT_FIELD = 'groups';
    
    protected function _onCreate(axis\schema\ISchema $schema) {
        $schema->addField('isLeader', 'Boolean');
    }
}

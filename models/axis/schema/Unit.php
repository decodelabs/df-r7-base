<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\axis\schema;

use df;
use df\core;
use df\apex;
use df\axis;

class Unit extends axis\unit\table\Base {
    
    protected function createSchema($schema) {
        $schema->addPrimaryField('unitId', 'String', 255);
        $schema->addField('storeName', 'String', 128);
        $schema->addField('version', 'Integer', 2);
        $schema->addField('schema', 'BigBinary', 16);
        $schema->addIndexedField('timestamp', 'Timestamp');
    }
}
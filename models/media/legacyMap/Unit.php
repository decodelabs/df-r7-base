<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\media\legacyMap;

use df;
use df\core;
use df\apex;
use df\axis;

class Unit extends axis\unit\Table {

    protected function createSchema($schema) {
        $schema->addField('old', 'Number', 8);
        $schema->addField('new', 'Guid');
    }
}
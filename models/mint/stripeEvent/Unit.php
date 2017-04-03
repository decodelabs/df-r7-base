<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\mint\stripeEvent;

use df;
use df\core;
use df\apex;
use df\axis;

class Unit extends axis\unit\table\Base {

    const ORDERABLE_FIELDS = ['name', 'date', 'success'];
    const DEFAULT_ORDER = 'date DESC';

    protected function createSchema($schema) {
        $schema->addPrimaryField('id', 'Guid');

        $schema->addField('name', 'Text', 64);
        $schema->addField('stripeId', 'Text', 64);

        $schema->addField('date', 'Timestamp');
        $schema->addField('success', 'Boolean');

        $schema->addField('data', 'Json');
    }
}